<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Models\ConsentPurpose;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controlador del panel de administración para el catálogo de fines legales.
 *
 * Expone los fines legales activos para alimentar el generador de políticas
 * (wizard). Este endpoint es consumido por el frontend del panel administrativo
 * al configurar los propósitos de tratamiento en las plantillas legales.
 */
class ConsentPurposeController extends Controller
{
    use ApiResponse;

    /**
     * Retorna el catálogo de fines legales activos ordenados por display_order.
     *
     * Selecciona explícitamente solo los campos necesarios para el wizard:
     * id, slug, category, label, description, requires_consent.
     */
    public function index(): JsonResponse
    {
        $purposes = ConsentPurpose::where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'slug', 'category', 'label', 'description', 'requires_consent']);

        return $this->success('Catálogo de fines legales.', $purposes);
    }
}
