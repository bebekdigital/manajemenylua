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
        'jenjang_kepegawaian',
        'tmt_kenaikan_jenjang',
        'tst_kenaikan_jenjang',
        'tst_jenjang',
        'status_keaktifan',
        'jenis_kepegawaian',
        'keterangan_tidak_aktif',
        'keaktifan_dapodik',
        'unit_keaktifan_dapodik',
        'unit_kerja',
        'jabatan',
    ];

    protected function casts(): array
    {
        return [
            'tmt_kenaikan_jenjang' => 'date',
            'tst_kenaikan_jenjang' => 'date',
            'tst_jenjang' => 'date',
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
