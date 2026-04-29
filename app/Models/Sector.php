<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    /**
     * Catálogo estático de rubros/sectores económicos.
     */
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Relación con BusinessActivity
     */
    public function businessActivities(): HasMany
    {
        return $this->hasMany(BusinessActivity::class);
    }

    /**
     * Relación con Company (via pivot)
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }
}
