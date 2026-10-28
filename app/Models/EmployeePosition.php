<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'unit_kerja',
        'jabatan',
        'jenis_kepegawaian',
        'no_sk',
        'tmt_jabatan',
        'tst_jabatan',
    ];

    protected function casts(): array
    {
        return [
            'tmt_jabatan' => 'date',
            'tst_jabatan' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
