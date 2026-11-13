<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'semester',
        'start_date',
        'end_date',

    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',

        ];
    }

    /**
     * Get the current active academic year.
     */
    public static function current(): ?self
    {
        $today = now()->toDateString();
        $active = static::whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($active) {
            return $active;
        }

        return static::orderByDesc('start_date')->first();
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }
}
