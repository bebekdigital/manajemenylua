<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationLevel extends Model
{
    protected $fillable = [
        'level',
        'name',
        'subtitle',
    ];

    protected $casts = [
        'level' => 'integer',
    ];
}
