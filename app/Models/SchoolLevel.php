<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolLevel extends Model
{
    protected $fillable = ['name', 'order'];

    public function units()
    {
        return $this->hasMany(SchoolUnit::class);
    }
}
