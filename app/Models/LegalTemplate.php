<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalTemplate extends Model
{
    protected $fillable = [
        'document_type',
        'name',
        'version',
        'content',
        'wizard_schema',
        'is_active',
        'required_condition',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'wizard_schema' => 'array',
        'required_condition' => 'array',
    ];

    public static function getActiveTemplate(string $documentType)
    {
        return self::where('document_type', $documentType)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();
    }
}
