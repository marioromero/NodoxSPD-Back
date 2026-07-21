<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo universal de fines legales para el sistema de consentimientos.
 *
 * Define los propósitos de tratamiento de datos que el Trust Widget muestra
 * a los visitantes. Cada propósito tiene una base legal (consent, legitimate_interest,
 * contractual, legal_obligation) que determina si requiere toggle en el widget
 * o si está siempre activo.
 */
class ConsentPurpose extends Model
{
    /** @var array<string> Atributos asignables masivamente. */
    protected $fillable = [
        'slug',
        'category',
        'label',
        'description',
        'legal_basis',
        'requires_consent',
        'default_value',
        'widget_action',
        'display_order',
        'is_active',
    ];

    /** @var array<string, string> Casts para forzar tipos nativos. */
    protected $casts = [
        'requires_consent' => 'boolean',
        'default_value' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];
}
