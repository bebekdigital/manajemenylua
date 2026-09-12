<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nisn',
        'nama',
        'nipd',
        'nik',
        'no_kk',
        'unit',
        'program',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'no_wa',
        'sekolah_asal',
        'status_keluarga',
        'anak_ke',
        'diterima_di_jenjang',
        'tanggal_diterima',
        'jalan',
        'rt_rw',
        'dusun',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_diterima' => 'date',
            'anak_ke' => 'integer',
        ];
    }

    /**
     * Get the TTL (Tempat, Tanggal Lahir) formatted string.
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

    public function family(): HasOne
    {
        return $this->hasOne(StudentFamily::class);
    }

    public function siblings(): HasMany
    {
        return $this->hasMany(StudentSibling::class);
    }

    /**
     * All academic records across all Tahun Ajaran.
     */
    public function academicRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }

    /**
     * Academic record for the currently active Tahun Ajaran.
     */
    public function currentRecord(): HasOne
    {
        $activeYear = AcademicYear::current();

        return $this->hasOne(StudentAcademicRecord::class)
            ->where('academic_year_id', $activeYear?->id);
    }
}
