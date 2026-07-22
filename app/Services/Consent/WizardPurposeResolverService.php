<?php

namespace App\Services\Consent;

use App\Models\CompanyPolicy;
use App\Models\ConsentPurpose;
use Illuminate\Support\Collection;

/**
 * Servicio core que resuelve qué fines legales (purposes) están activos
 * para una empresa basándose en las respuestas de su wizard de políticas.
 *
 * Recorre el wizard_schema del template legal y evalúa las respuestas
 * almacenadas en wizard_data para determinar qué slugs de ConsentPurpose
 * están activos. El resultado se consulta desde la BD ordenado por display_order.
 */
class WizardPurposeResolverService
{
    /**
     * Resuelve los fines legales activos para una política publicada.
     *
     * @return Collection<int, ConsentPurpose>
     */
    public function resolve(CompanyPolicy $policy): Collection
    {
        $wizardData = $policy->wizard_data;
        $wizardSchema = $policy->legalTemplate->wizard_schema;

        // El propósito técnico/necesario siempre está activo (base legal: interés legítimo).
        $activeSlugs = ['necessary_technical'];

        // Iterar sobre cada paso y cada campo del esquema del wizard.
        foreach ($wizardSchema['steps'] ?? [] as $step) {
            foreach ($step['fields'] ?? [] as $field) {
                $activeSlugs = $this->collectFromField($field, $wizardData, $activeSlugs);
            }
        }

        // Deduplicar y reindexar el arreglo de slugs.
        $activeSlugs = array_values(array_unique($activeSlugs));

        // Consultar los modelos ConsentPurpose activos desde la BD, ordenados por display_order.
        return ConsentPurpose::whereIn('slug', $activeSlugs)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Aplica las 3 reglas de recolección de slugs sobre un campo del wizard.
     *
     * Regla 1 (Booleanos): Si el campo es boolean, el valor es true, y tiene legal_purposes, merge.
     * Regla 2 (Multiselect): Si el campo es multiselect, itera las opciones seleccionadas y mergea sus legal_purposes.
     * Regla 3 (Custom/Otras herramientas): Si el campo tiene requires_purpose_selection, busca {$key}_purposes en wizard_data.
     */
    private function collectFromField(array $field, array $wizardData, array $activeSlugs): array
    {
        $key = $field['key'];
        $value = $wizardData[$key] ?? null;

        // Regla 1: Booleanos con legal_purposes directo en la raíz del field.
        if ($field['type'] === 'boolean' && $value === true && isset($field['legal_purposes'])) {
            $activeSlugs = array_merge($activeSlugs, $field['legal_purposes']);
        }

        // Regla 2: Multiselect — iterar opciones seleccionadas y extraer legal_purposes de cada una.
        if ($field['type'] === 'multiselect' && is_array($value) && isset($field['options'])) {
            foreach ($value as $selectedOption) {
                if (isset($field['options'][$selectedOption]['legal_purposes'])) {
                    $activeSlugs = array_merge($activeSlugs, $field['options'][$selectedOption]['legal_purposes']);
                }
            }
        }

        // Regla 3: Campos con requires_purpose_selection — buscar {$key}_purposes en wizard_data.
        if (isset($field['requires_purpose_selection']) && $field['requires_purpose_selection'] === true) {
            $purposeKey = "{$key}_purposes";
            if (isset($wizardData[$purposeKey]) && is_array($wizardData[$purposeKey])) {
                $activeSlugs = array_merge($activeSlugs, $wizardData[$purposeKey]);
            }
        }

        return $activeSlugs;
    }
}
