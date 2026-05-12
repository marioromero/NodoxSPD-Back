<?php

namespace App\Http\Controllers;

use App\Models\LegalTemplate;
use Illuminate\Http\Request;

class LegalTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Obtenemos la empresa y sus respuestas del módulo de políticas
        $company = $request->user()->company;
        $triageAnswers = $company->legal_settings['policies'] ?? [];

        // 2. Traemos las plantillas, ASEGURANDO traer la columna 'required_condition'
        $templates = LegalTemplate::where('is_active', true)
            ->select('id', 'name', 'document_type', 'wizard_schema', 'required_condition')
            ->get()
            ->filter(function ($template) use ($triageAnswers) {

                // Si la plantilla no exige ninguna condición, la mostramos siempre
                if (empty($template->required_condition)) {
                    return true;
                }

                // Según el plan de Kilocode, required_condition es un array.
                // Buscamos la llave (ej: 'has_employees')
                $conditionKey = $template->required_condition['key'] ?? $template->required_condition[0] ?? null;

                // Si la llave existe, revisamos si la empresa respondió 'true' en el Triage
                if ($conditionKey) {
                    return isset($triageAnswers[$conditionKey]) && $triageAnswers[$conditionKey] === true;
                }

                // Si exige condición pero no se cumplió, la ocultamos
                return false;

            })->values(); // values() reordena los índices del array para el JSON

        return response()->json([
            'status' => true,
            'message' => 'Plantillas legales filtradas según triage.',
            'data' => $templates,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LegalTemplate $legalTemplate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LegalTemplate $legalTemplate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LegalTemplate $legalTemplate)
    {
        //
    }
}
