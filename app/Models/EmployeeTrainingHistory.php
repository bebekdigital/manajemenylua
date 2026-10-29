<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTrainingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'bidang',
        'keahlian',
        'instansi_penyelenggara',
        'tahun',
        'pembiayaan',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
