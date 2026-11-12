<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolUnit extends Model
{
    protected $fillable = ['name'];

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(SchoolLevel::class, 'school_level_unit')
            ->withTimestamps();
    }

    public function level()
    {
        return $this->levels()->first();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(SchoolProgram::class, 'school_unit_program')
            ->withTimestamps();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'name', 'name');
    }

    public function positions(): HasManyThrough
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
