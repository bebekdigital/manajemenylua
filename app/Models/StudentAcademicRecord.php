<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAcademicRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_nisn',
        'academic_year_id',
        'classroom_id',
        'tingkat',
        'program',
        'student_status',
        'ket_tidak_aktif',
        'tanggal_tidak_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'tanggal_tidak_aktif' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_nisn', 'nisn');
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
