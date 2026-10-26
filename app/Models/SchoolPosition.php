<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPosition extends Model
{
    protected $fillable = ['school_unit_id', 'name', 'order'];

    public function unit()
    {
        return $this->belongsTo(SchoolUnit::class, 'school_unit_id');
    }
}
