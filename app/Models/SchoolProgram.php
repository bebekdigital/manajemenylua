<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolProgram extends Model
{
    protected $fillable = ['name'];

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(SchoolUnit::class, 'school_unit_program')
            ->withTimestamps();
    }
}
