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

    public function programs()
    {
        return $this->hasMany(SchoolProgram::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'name', 'name');
    }

    public function positions()
    {
        return $this->hasManyThrough(
            DepartmentPosition::class,
            Department::class,
            'name',
            'department_id',
            'name',
            'id'
        )->orderBy('level')->orderBy('order')->orderBy('name');
    }
}
