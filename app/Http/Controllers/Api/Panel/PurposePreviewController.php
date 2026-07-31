<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PreviewPurposesRequest;
use App\Models\LegalTemplate;
use App\Services\Consent\WizardPurposeResolverService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controlador del panel para preview en tiempo real de fines legales.
 *
 * Permite al frontend mostrar qué purposes están activos mientras el
 * usuario completa el wizard, sin necesidad de guardar la política.
 */
class PurposePreviewController extends Controller
{
    use ApiResponse;

    /**
     * Resuelve los purposes activos a partir de respuestas parciales del wizard.
     *
     * Flujo:
     * 1. Valida document_type y wizard_data.
     * 2. Obtiene el template legal activo para ese document_type.
     * 3. Ejecuta WizardPurposeResolverService::resolveFromData con los datos parciales.
     * 4. Retorna los purposes activos con los mismos campos que el catálogo.
     */
    public function preview(PreviewPurposesRequest $request, WizardPurposeResolverService $resolver): JsonResponse
    {
        $template = LegalTemplate::where('document_type', $request->input('document_type'))
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return $this->error('No hay una plantilla activa para este tipo de documento.', null, 404);
        }

        $activePurposes = $resolver->resolveFromData(
            $request->input('wizard_data'),
            $template->wizard_schema,
        );

        return $this->success(
            'Fines legales activos según las respuestas del wizard.',
            $activePurposes->map(fn ($p) => [
                'slug' => $p->slug,
                'label' => $p->label,
                'description' => $p->description,
                'requires_consent' => $p->requires_consent,
                'legal_basis' => $p->legal_basis,
            ]),
        );
    }
}
