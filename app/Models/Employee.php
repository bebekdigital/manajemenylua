<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nipy',
        'nama',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'no_wa',
        'email',
        'jalan_domisili',
        'rt_rw_domisili',
        'dusun_domisili',
        'desa_domisili',
        'kecamatan_domisili',
        'kab_domisili',
        'provinsi_domisili',
        'jalan_tinggal',
        'rt_rw_tinggal',
        'dusun_tinggal',
        'desa_tinggal',
        'kecamatan_tinggal',
        'provinsi_tinggal',
        'status_rumah',
        'kepemilikan_bpjs',
        'penanggung_bpjs',
        'skill',
        'status_pernikahan',
        'nama_suami_istri',
        'ttl_suami_istri',
        'pekerjaan_suami_istri',
        'tanggal_menikah',
        'jumlah_anak',
        'nama_ibu',
        'nama_ayah',
        'alamat_orangtua',
        'kontak_darurat',
        'hubungan_kontak_darurat',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_menikah' => 'date',
            'jumlah_anak' => 'integer',
        ];
    }

    /**
     * Get TTL formatted string.
     */
    public function getTtlAttribute(): string
    {
        if (! $this->tempat_lahir && ! $this->tanggal_lahir) {
            return '-';
        }

        $tanggal = $this->tanggal_lahir
            ? $this->tanggal_lahir->translatedFormat('d F Y')
            : '';

        return trim("{$this->tempat_lahir}, {$tanggal}", ', ');
    }

    public function educationHistories(): HasMany
    {
        return $this->hasMany(EmployeeEducationHistory::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(EmployeeRecord::class);
    }

    public function currentRecord(): HasOne
    {
        $activeYear = AcademicYear::current();

        return $this->hasOne(EmployeeRecord::class)
            ->where('academic_year_id', $activeYear?->id);
    }
}
