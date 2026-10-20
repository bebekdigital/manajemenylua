<?php

namespace App\Models;

use Carbon\Carbon;
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
        'gelar_depan',
        'gelar_belakang',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'tmt_pengangkatan',
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
        'kabupaten_tinggal',
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
            'tmt_pengangkatan' => 'date',
            'jumlah_anak' => 'integer',
        ];
    }

    /**
     * Get dynamic Masa Kerja calculated from tmt_pengangkatan.
     */
    public function getMasaKerjaAttribute(): ?string
    {
        if (! $this->tmt_pengangkatan) {
            return null;
        }

        $diff = Carbon::parse($this->tmt_pengangkatan)->diff(now());
        $parts = [];
        if ($diff->y > 0) {
            $parts[] = "{$diff->y} Tahun";
        }
        if ($diff->m > 0) {
            $parts[] = "{$diff->m} Bulan";
        }

        return empty($parts) ? 'Kurang dari 1 bulan' : implode(' ', $parts);
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

    /**
     * Get full name formatted with gelar depan and gelar belakang.
     */
    public function getNamaLengkapGelarAttribute(): string
    {
        $depan = $this->gelar_depan ? trim($this->gelar_depan).' ' : '';
        $belakang = $this->gelar_belakang ? ', '.trim($this->gelar_belakang) : '';

        return trim($depan.$this->nama.$belakang);
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
