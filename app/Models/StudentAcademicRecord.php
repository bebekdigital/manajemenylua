<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAcademicRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'classroom_id',
        'jenjang',
        'tingkat',
        'student_status',
        'desil',
        'status_pip',
        'pip_keterangan',
        'status_kip',
        'no_kip',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'desil' => 'integer',
            'status_pip' => 'boolean',
            'status_kip' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
