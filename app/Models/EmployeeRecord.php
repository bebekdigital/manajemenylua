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
        'jenjang_kepegawaian',
        'no_sk',
        'tmt_kenaikan_jenjang',
        'tst_kenaikan_jenjang',
    ];

    protected function casts(): array
    {
        return [
            'tmt_kenaikan_jenjang' => 'date',
            'tst_kenaikan_jenjang' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
