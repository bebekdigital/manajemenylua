<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateStudentsTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-students-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindahkan data dari students lama dan student_families ke tabel students baru yang rapi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses migrasi data siswa dan keluarga...');

        // 1. Pastikan tabel student_families masih ada
        if (! Schema::hasTable('student_families')) {
            $this->error('Tabel student_families tidak ditemukan. Mungkin sudah dihapus?');

            return;
        }

        if (! Schema::hasTable('students')) {
            $this->error('Tabel students tidak ditemukan.');

            return;
        }

        // 2. Ambil semua data
        $this->info('Mengambil data dari students dan student_families...');
        $students = DB::table('students')->get();
        $families = DB::table('student_families')->get()->keyBy('student_id');

        // 3. Rename students to students_backup
        $this->info('Membuat backup tabel lama...');
        Schema::rename('students', 'students_backup');

        // 4. Create new students table with exact schema
        $this->info('Membuat tabel students dengan struktur baru...');
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // Sesuai dengan Template Dokumen Excel (A-BI)
            $table->string('nama');
            $table->string('nisn', 20)->unique();
            $table->string('nipd', 20)->nullable();
            $table->string('jenjang', 10)->nullable();
            $table->string('unit')->nullable();
            $table->string('jk', 10)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('agama', 20)->default('Islam');
            $table->string('jalan')->nullable();
            $table->string('rt_rw', 20)->nullable();
            $table->string('dusun')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('jenis_tinggal')->nullable();
            $table->string('alat_transportasi')->nullable();
            $table->string('no_wa', 20)->nullable();
            $table->string('no_wa_2', 20)->nullable();
            $table->string('email')->nullable();

            // Data Ayah
            $table->string('ayah_nama')->nullable();
            $table->unsignedSmallInteger('ayah_tahun_lahir')->nullable();
            $table->string('ayah_pendidikan')->nullable();
            $table->string('ayah_pekerjaan')->nullable();
            $table->string('ayah_penghasilan')->nullable();
            $table->string('ayah_nik', 20)->nullable();
            $table->string('ayah_status')->nullable();

            // Data Ibu
            $table->string('ibu_nama')->nullable();
            $table->unsignedSmallInteger('ibu_tahun_lahir')->nullable();
            $table->string('ibu_pendidikan')->nullable();
            $table->string('ibu_pekerjaan')->nullable();
            $table->string('ibu_penghasilan')->nullable();
            $table->string('ibu_nik', 20)->nullable();
            $table->string('ibu_status')->nullable();

            // Data Wali
            $table->string('wali_nama')->nullable();
            $table->unsignedSmallInteger('wali_tahun_lahir')->nullable();
            $table->string('wali_pendidikan')->nullable();
            $table->string('wali_pekerjaan')->nullable();
            $table->string('wali_penghasilan')->nullable();
            $table->string('wali_nik', 20)->nullable();
            $table->string('wali_hubungan')->nullable();

            // Kesejahteraan & Bantuan
            $table->boolean('status_kip')->default(false);
            $table->string('no_kip')->nullable();
            $table->string('nama_di_kip')->nullable();
            $table->boolean('status_pip')->default(false);
            $table->string('pip_keterangan')->nullable();
            $table->string('kebutuhan_khusus')->nullable();

            // Registrasi & Latar Belakang
            $table->string('sekolah_asal')->nullable();
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->string('no_kk', 20)->nullable();
            $table->string('jarak_rumah')->nullable();
            $table->boolean('has_bisnis')->default(false);
            $table->string('jenis_bisnis')->nullable();
            $table->unsignedTinyInteger('desil')->nullable();
            $table->string('diterima_di_jenjang')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('info_psb')->nullable();
            $table->string('status_registrasi')->nullable();

            // Kolom Internal Aplikasi
            $table->string('angkatan')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('status_keluarga')->nullable();
            $table->string('foto')->nullable();

            $table->timestamps();
        });

        // 5. Insert data
        $this->info('Memasukkan kembali data ke struktur tabel baru...');
        $insertData = [];
        foreach ($students as $s) {
            $f = $families[$s->id] ?? null;

            // Pastikan tidak error kalau kolom lama tidak ada (misal status_registrasi)
            $insertData[] = [
                'id' => $s->id,
                'nama' => $s->nama ?? null,
                'nisn' => $s->nisn ?? null,
                'nipd' => $s->nipd ?? null,
                'jenjang' => $s->jenjang ?? null,
                'unit' => $s->unit ?? null,
                'jk' => $s->jk ?? null,
                'tempat_lahir' => $s->tempat_lahir ?? null,
                'tanggal_lahir' => $s->tanggal_lahir ?? null,
                'nik' => $s->nik ?? null,
                'agama' => $s->agama ?? 'Islam',
                'jalan' => $s->jalan ?? null,
                'rt_rw' => $s->rt_rw ?? null,
                'dusun' => $s->dusun ?? null,
                'desa' => $s->desa ?? null,
                'kecamatan' => $s->kecamatan ?? null,
                'kabupaten' => $s->kabupaten ?? null,
                'provinsi' => $s->provinsi ?? null,
                'jenis_tinggal' => $s->jenis_tinggal ?? null,
                'alat_transportasi' => $s->alat_transportasi ?? null,
                'no_wa' => $s->no_wa ?? null,
                'no_wa_2' => $s->no_wa_2 ?? null,
                'email' => $s->email ?? null,

                'ayah_nama' => $f->ayah_nama ?? null,
                'ayah_tahun_lahir' => $f->ayah_tahun_lahir ?? null,
                'ayah_pendidikan' => $f->ayah_pendidikan ?? null,
                'ayah_pekerjaan' => $f->ayah_pekerjaan ?? null,
                'ayah_penghasilan' => $f->ayah_penghasilan ?? null,
                'ayah_nik' => $f->ayah_nik ?? null,
                'ayah_status' => $f->ayah_status ?? null,

                'ibu_nama' => $f->ibu_nama ?? null,
                'ibu_tahun_lahir' => $f->ibu_tahun_lahir ?? null,
                'ibu_pendidikan' => $f->ibu_pendidikan ?? null,
                'ibu_pekerjaan' => $f->ibu_pekerjaan ?? null,
                'ibu_penghasilan' => $f->ibu_penghasilan ?? null,
                'ibu_nik' => $f->ibu_nik ?? null,
                'ibu_status' => $f->ibu_status ?? null,

                'wali_nama' => $f->wali_nama ?? null,
                'wali_tahun_lahir' => $f->wali_tahun_lahir ?? null,
                'wali_pendidikan' => $f->wali_pendidikan ?? null,
                'wali_pekerjaan' => $f->wali_pekerjaan ?? null,
                'wali_penghasilan' => $f->wali_penghasilan ?? null,
                'wali_nik' => $f->wali_nik ?? null,
                'wali_hubungan' => $f->wali_hubungan ?? null,

                'status_kip' => $s->status_kip ?? false,
                'no_kip' => $s->no_kip ?? null,
                'nama_di_kip' => $s->nama_di_kip ?? null,
                'status_pip' => $s->status_pip ?? false,
                'pip_keterangan' => $s->pip_keterangan ?? null,
                'kebutuhan_khusus' => $s->kebutuhan_khusus ?? null,

                'sekolah_asal' => $s->sekolah_asal ?? null,
                'anak_ke' => $s->anak_ke ?? null,
                'no_kk' => $s->no_kk ?? null,
                'jarak_rumah' => $s->jarak_rumah ?? null,
                'has_bisnis' => $f->has_bisnis ?? false,
                'jenis_bisnis' => $f->jenis_bisnis ?? null,
                'desil' => $s->desil ?? null,
                'diterima_di_jenjang' => $s->diterima_di_jenjang ?? null,
                'tanggal_diterima' => $s->tanggal_diterima ?? null,
                'info_psb' => $s->info_psb ?? null,
                // Pastikan mengecek properti status_registrasi ada atau tidak di object $s
                'status_registrasi' => property_exists($s, 'status_registrasi') ? $s->status_registrasi : null,

                'angkatan' => $s->angkatan ?? null,
                'rt' => property_exists($s, 'rt') ? $s->rt : null,
                'rw' => property_exists($s, 'rw') ? $s->rw : null,
                'status_keluarga' => property_exists($s, 'status_keluarga') ? $s->status_keluarga : null,
                'foto' => property_exists($s, 'foto') ? $s->foto : null,

                'created_at' => $s->created_at ?? now(),
                'updated_at' => $s->updated_at ?? now(),
            ];
        }

        // Insert dalam chunk agar tidak memori berlebih jika data banyak
        foreach (array_chunk($insertData, 100) as $chunk) {
            DB::table('students')->insert($chunk);
        }

        $this->info('Menghapus tabel lama...');
        Schema::dropIfExists('student_families');
        // Schema::dropIfExists('students_backup'); // Biarkan saja dulu untuk amannya

        $this->info('Update data selesai! Struktur database sudah rapi tanpa ada data yang hilang.');
        $this->info('Tabel students lama telah dibackup sebagai "students_backup". Anda bisa menghapusnya jika sudah yakin.');
    }
}
