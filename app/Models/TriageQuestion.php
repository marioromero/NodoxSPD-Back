<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriageQuestion extends Model
{
    protected $fillable = [
        'module_slug',
        'key',
        'label',
        'description',
        'type',
        'options',
        'required_condition',
        'order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'required_condition' => 'array',
        'is_active' => 'boolean',
    ];
}
