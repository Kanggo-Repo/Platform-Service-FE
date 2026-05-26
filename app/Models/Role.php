<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
        'is_deletable',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_deletable' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
