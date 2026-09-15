<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'ayah_nama',
        'ayah_nik',
        'ayah_tahun_lahir',
        'ayah_pendidikan',
        'ayah_pekerjaan',
        'ayah_penghasilan',
        'ayah_status',
        'ibu_nama',
        'ibu_nik',
        'ibu_tahun_lahir',
        'ibu_pendidikan',
        'ibu_pekerjaan',
        'ibu_penghasilan',
        'ibu_status',
        'has_bisnis',
        'jenis_bisnis',
        'wali_nama',
        'wali_nik',
        'wali_tahun_lahir',
        'wali_hubungan',
        'wali_pendidikan',
        'wali_pekerjaan',
        'wali_penghasilan',
    ];

    protected function casts(): array
    {
        return [
            'ayah_tahun_lahir' => 'integer',
            'ibu_tahun_lahir' => 'integer',
            'wali_tahun_lahir' => 'integer',
            'has_bisnis' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
