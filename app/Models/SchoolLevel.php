<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolLevel extends Model
{
    protected $fillable = ['name', 'order'];

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(SchoolUnit::class, 'school_level_unit')
            ->withTimestamps();
    }
}
