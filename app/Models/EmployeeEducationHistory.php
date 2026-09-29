<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'jenjang_pendidikan',
        'jurusan',
        'instansi_pendidikan',
        'tahun_lulus',
        'pembiayaan',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
