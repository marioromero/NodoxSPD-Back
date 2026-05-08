<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLegalSettingsRequest;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }

    /**
     * Update legal settings for a specific module.
     */
    public function updateLegalSettings(UpdateLegalSettingsRequest $request)
    {
        // Debug: Log what we're receiving
        \Log::debug('UpdateLegalSettings - All input:', $request->all());
        \Log::debug('UpdateLegalSettings - Module slug:', $request->input('module_slug'));
        \Log::debug('UpdateLegalSettings - Answers:', $request->input('answers'));

        $company = $request->user()->company;

        $module = $request->validated('module_slug');
        $answers = $request->validated('answers');

        $currentSettings = $company->legal_settings ?? [];

        // Guardamos las respuestas DENTRO de la llave del módulo correspondiente
        $currentSettings[$module] = array_merge($currentSettings[$module] ?? [], $answers);

        $company->update([
            'legal_settings' => $currentSettings,]);

        return response()->json([
            'status' => true,
            'message' => 'Triage completado. Tu ecosistema legal ha sido configurado.',
            'data' => [
                'legal_settings' => $company->legal_settings,
            ],
        ]);
    }
}
