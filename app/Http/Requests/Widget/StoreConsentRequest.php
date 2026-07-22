<?php

namespace App\Http\Requests\Widget;

use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Services\Consent\WizardPurposeResolverService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Validación de la recepción de consentimientos desde el Trust Widget.
 *
 * Valida que el payload enviado por el widget contenga un visitor_uuid válido,
 * un company_public_uuid que exista en BD, un timestamp ISO-8601 dentro de la
 * ventana temporal permitida, y un array de purposes donde cada slug sea un
 * fin legal activo para la política de cookies de esa empresa.
 *
 * Las reglas de purposes se construyen dinámicamente consultando el catálogo
 * de fines activos via WizardPurposeResolverService.
 */
class StoreConsentRequest extends FormRequest
{
    /**
     * Endpoint público: el widget embebido no requiere autenticación.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación base + reglas dinámicas por fin legal activo.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'visitor_uuid' => ['required', 'uuid'],
            'company_public_uuid' => ['required', 'uuid', 'exists:companies,public_uuid'],
            'timestamp' => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'purposes' => ['required', 'array'],
        ];

        $dynamicPurposeRules = $this->buildDynamicPurposeRules();

        return array_merge($rules, $dynamicPurposeRules);
    }

    /**
     * Validación adicional: el timestamp no puede estar más de 5 minutos
     * en el futuro ni más de 1 hora en el pasado.
     *
     * Esto previene ataques de replay y timestamps manipulados.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $timestamp = $this->input('timestamp');

            if (! $timestamp) {
                return;
            }

            try {
                $consentTime = Carbon::createFromFormat(
                    'Y-m-d\TH:i:s\Z',
                    $timestamp,
                    'UTC',
                );
            } catch (\Exception) {
                return;
            }

            $now = now('UTC');
            $maxFuture = $now->copy()->addMinutes(5);
            $maxPast = $now->copy()->subHour();

            if ($consentTime->isAfter($maxFuture)) {
                $validator->errors()->add(
                    'timestamp',
                    'El timestamp no puede estar más de 5 minutos en el futuro.',
                );
            }

            if ($consentTime->isBefore($maxPast)) {
                $validator->errors()->add(
                    'timestamp',
                    'El timestamp no puede ser anterior a 1 hora atrás.',
                );
            }
        });
    }

    /**
     * Construye reglas dinámicas para cada fin legal activo de la empresa.
     *
     * Resuelve la empresa por company_public_uuid, obtiene su política de
     * cookies publicada, y usa WizardPurposeResolverService para determinar
     * qué slugs están activos. Por cada slug activo, agrega una regla
     * "purposes.{slug}" => ['sometimes', 'boolean'].
     *
     * @return array<string, array<string>>
     */
    private function buildDynamicPurposeRules(): array
    {
        $companyUuid = $this->input('company_public_uuid');

        if (! $companyUuid) {
            return [];
        }

        $company = Company::where('public_uuid', $companyUuid)->first();

        if (! $company) {
            return [];
        }

        $policy = CompanyPolicy::where('company_id', $company->id)
            ->where('document_type', 'cookie_policy')
            ->where('status', 'published')
            ->latest('published_at')
            ->first();

        if (! $policy) {
            Log::info('StoreConsentRequest: empresa sin política de cookies publicada', [
                'public_uuid' => $companyUuid,
            ]);

            return [];
        }

        $policy->load('template');

        /** @var WizardPurposeResolverService $resolver */
        $resolver = app(WizardPurposeResolverService::class);
        $activePurposes = $resolver->resolve($policy);

        $rules = [];

        foreach ($activePurposes as $purpose) {
            $rules["purposes.{$purpose->slug}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
