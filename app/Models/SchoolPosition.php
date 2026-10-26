<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPosition extends Model
{
    protected $fillable = ['name'];

    public function units()
    {
        return $this->belongsToMany(SchoolUnit::class, 'school_unit_positions')
            ->withPivot('id', 'order')
            ->withTimestamps();
    }
}
