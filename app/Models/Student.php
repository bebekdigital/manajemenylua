<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'nisn';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nisn',
        'nama',
        'nipd',
        'nik',
        'no_kk',
        'jenjang',
        'unit',
        'angkatan',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'no_wa',
        'no_wa_2',
        'sekolah_asal',
        'status_keluarga',
        'anak_ke',
        'diterima_di_jenjang',
        'tanggal_diterima',
        'info_psb',
        'status_registrasi',
        'jalan',
        'rt_rw',
        'rt',
        'rw',
        'dusun',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'jenis_tinggal',
        'alat_transportasi',
        'email',
        'status_kip',
        'no_kip',
        'nama_di_kip',
        'status_pip',
        'pip_keterangan',
        'desil',
        'kebutuhan_khusus',
        'jarak_rumah',
        'foto',
        'ayah_nama',
        'ayah_tahun_lahir',
        'ayah_pendidikan',
        'ayah_pekerjaan',
        'ayah_penghasilan',
        'ayah_nik',
        'ayah_status',
        'ibu_nama',
        'ibu_tahun_lahir',
        'ibu_pendidikan',
        'ibu_pekerjaan',
        'ibu_penghasilan',
        'ibu_nik',
        'ibu_status',
        'wali_nama',
        'wali_tahun_lahir',
        'wali_pendidikan',
        'wali_pekerjaan',
        'wali_penghasilan',
        'wali_nik',
        'wali_hubungan',
        'has_bisnis',
        'jenis_bisnis',
    ];

    /**
     * Get the publicly accessible URL for the student's photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        // Ekstrak nama file dari path (karena $this->foto berisi 'student-photos/filename.ext')
        $filename = basename($this->foto);

        return route('students.photo', ['filename' => $filename]);
    }

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

    public function siblings(): HasMany
    {
        return $this->hasMany(StudentSibling::class, 'student_nisn', 'nisn');
    }

    /**
     * All academic records across all Tahun Ajaran.
     */
    public function academicRecords(): HasMany
    {
        return $this->hasMany(StudentAcademicRecord::class, 'student_nisn', 'nisn');
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
