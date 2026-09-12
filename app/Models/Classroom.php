<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'unit',
        'jenjang',
        'grade',
        'name',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get full display name, e.g. "SMPIT - Kelas 7A".
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->unit} - Kelas {$this->name}";
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }
}
