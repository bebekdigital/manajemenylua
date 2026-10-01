<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolProgram extends Model
{
    protected $fillable = ['school_unit_id', 'name'];

    public function unit()
    {
        return $this->belongsTo(SchoolUnit::class, 'school_unit_id');
    }
}
