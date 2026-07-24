<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateWidgetConfigRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controlador del panel para la personalización visual del Trust Widget.
 *
 * Permite a la Pyme actualizar su configuración del widget (color primario,
 * logo, textos del banner, etiquetas de botones) realizando un merge profundo
 * (deep merge) con el JSON existente en company.widget_config.
 *
 * Nota arquitectónica: widget_config es un JSON estructurado con nodos anidados
 * (ej: legal_texts). El merge debe ser recursivo para no destruir llaves
 * hermanas que otros procesos puedan haber escrito (ej: legal_texts.policy_url).
 */
class CompanyWidgetConfigController extends Controller
{
    use ApiResponse;

    /**
     * Actualiza la configuración del widget con deep merge.
     *
     * Usa array_replace_recursive para fusionar los datos validados con el
     * JSON existente, preservando llaves anidadas que no vengan en el request.
     * Los valores null se filtran antes del merge para no sobrescribir
     * configuraciones existentes con nulos.
     */
    public function update(UpdateWidgetConfigRequest $request): JsonResponse
    {
        $company = $request->user()->company;

        $currentConfig = (array) ($company->widget_config ?? []);

        $validated = array_filter($request->validated(), fn ($value): bool => ! is_null($value));

        $newConfig = array_replace_recursive($currentConfig, $validated);

        $company->update([
            'widget_config' => $newConfig,
        ]);

        return $this->success(
            'Configuración del widget actualizada.',
            $company->fresh()->widget_config,
        );
    }
}
