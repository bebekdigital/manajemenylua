<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentPosition extends Model
{
    protected $fillable = [
        'department_id',
        'parent_id',
        'name',
        'level',
        'order',
    ];

    protected $casts = [
        'level' => 'integer',
        'order' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<DepartmentPosition, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DepartmentPosition::class, 'parent_id');
    }

    /**
     * @return HasMany<DepartmentPosition, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(DepartmentPosition::class, 'parent_id')->orderBy('order')->orderBy('name');
    }
}
