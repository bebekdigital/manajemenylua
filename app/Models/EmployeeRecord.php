<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'academic_year_id',
        'status_keaktifan',
        'jenis_kepegawaian',
        'keterangan_tidak_aktif',
        'jenjang_kepegawaian',
        'tmt',
        'tst_jenjang',
        'masa_kerja',
        'keaktifan_dapodik',
        'unit_keaktifan_dapodik',
        'unit_kerja',
        'jabatan',
    ];

    protected function casts(): array
    {
        return [
            'tmt' => 'date',
            'tst' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
