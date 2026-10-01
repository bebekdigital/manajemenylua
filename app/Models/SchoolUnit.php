<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolUnit extends Model
{
    protected $fillable = ['school_level_id', 'name'];

    public function level()
    {
        return $this->belongsTo(SchoolLevel::class, 'school_level_id');
    }
}
