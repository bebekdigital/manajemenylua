<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToNisnPrimaryKeyCommand extends Command
{
    protected $signature = 'app:migrate-to-nisn-primary-key';
    protected $description = 'Ubah tabel students menggunakan nisn sebagai primary key tanpa menghapus data.';

    public function handle()
    {
        $this->info('Memulai perombakan Primary Key ke NISN...');
        
        try {
            // 1. Tambahkan kolom student_nisn di tabel relasi jika belum ada
            $this->info('Menambahkan kolom student_nisn ke tabel relasi...');
            if (!Schema::hasColumn('student_academic_records', 'student_nisn')) {
                DB::statement("ALTER TABLE student_academic_records ADD COLUMN student_nisn VARCHAR(20) NULL AFTER student_id");
            }
            if (!Schema::hasColumn('student_siblings', 'student_nisn')) {
                DB::statement("ALTER TABLE student_siblings ADD COLUMN student_nisn VARCHAR(20) NULL AFTER student_id");
            }

            // 2. Isi kolom student_nisn dengan data NISN dari tabel students
            // (Tetap jalankan, jika kolom id masih ada)
            $this->info('Mengisi data NISN ke tabel relasi...');
            if (Schema::hasColumn('students', 'id') && Schema::hasColumn('student_academic_records', 'student_id')) {
                DB::statement("UPDATE student_academic_records sar JOIN students s ON sar.student_id = s.id SET sar.student_nisn = s.nisn");
                DB::statement("UPDATE student_siblings ss JOIN students s ON ss.student_id = s.id SET ss.student_nisn = s.nisn");
            }

            // Pastikan tidak ada student_nisn yang kosong
            $emptyCount = DB::table('student_academic_records')->whereNull('student_nisn')->count();
            if ($emptyCount > 0) {
                throw new \Exception("Ada data akademik yang siswa-nya tidak memiliki NISN atau tidak valid.");
            }

            // 3. Hapus Foreign Key lama dan kolom student_id
            $this->info('Menghapus Foreign Key lama (student_id)...');
            $this->dropForeignKeySafe('student_academic_records', 'student_academic_records_student_id_foreign');
            $this->dropForeignKeySafe('student_siblings', 'student_siblings_student_id_foreign');
            
            $this->dropIndexSafe('student_academic_records', 'student_academic_records_student_id_academic_year_id_unique');

            if (Schema::hasColumn('student_academic_records', 'student_id')) {
                DB::statement("ALTER TABLE student_academic_records DROP COLUMN student_id");
            }
            if (Schema::hasColumn('student_siblings', 'student_id')) {
                DB::statement("ALTER TABLE student_siblings DROP COLUMN student_id");
            }

            // 4. Ubah Primary Key di tabel Students
            $this->info('Merombak tabel students (Menghapus id, menjadikan nisn sebagai Primary Key)...');
            if (Schema::hasColumn('students', 'id')) {
                DB::statement("ALTER TABLE students MODIFY id BIGINT UNSIGNED NOT NULL");
                DB::statement("ALTER TABLE students DROP PRIMARY KEY");
                $this->dropIndexSafe('students', 'students_nisn_unique');
                DB::statement("ALTER TABLE students ADD PRIMARY KEY (nisn)");
                DB::statement("ALTER TABLE students DROP COLUMN id");
            }

            // 5. Jadikan student_nisn NOT NULL dan pasang Foreign Key baru
            $this->info('Memasang Foreign Key baru (student_nisn)...');
            DB::statement("ALTER TABLE student_academic_records MODIFY student_nisn VARCHAR(20) NOT NULL");
            DB::statement("ALTER TABLE student_siblings MODIFY student_nisn VARCHAR(20) NOT NULL");

            $this->addForeignKeySafe('student_academic_records', 'sar_student_nisn_foreign', 'student_nisn', 'students', 'nisn');
            $this->addForeignKeySafe('student_siblings', 'ss_student_nisn_foreign', 'student_nisn', 'students', 'nisn');

            $this->addUniqueIndexSafe('student_academic_records', 'sar_nisn_year_unique', 'student_nisn, academic_year_id');

            $this->info('Proses perombakan berhasil! Database sekarang menggunakan NISN sebagai Primary Key.');

        } catch (\Exception $e) {
            $this->error('Gagal melakukan migrasi: ' . $e->getMessage());
        }
    }

    private function dropForeignKeySafe($table, $key)
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$key}");
        } catch (\Exception $e) {
            // Abaikan jika tidak ada
        }
    }

    private function dropIndexSafe($table, $key)
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$key}");
        } catch (\Exception $e) {
            // Abaikan jika tidak ada
        }
    }

    private function addForeignKeySafe($table, $key, $column, $refTable, $refColumn)
    {
        try {
            // Try to drop first in case it exists, to be idempotent
            $this->dropForeignKeySafe($table, $key);
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$key} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE CASCADE");
        } catch (\Exception $e) {
            // Abaikan jika gagal (mungkin sudah ada)
        }
    }

    private function addUniqueIndexSafe($table, $key, $columns)
    {
        try {
            $this->dropIndexSafe($table, $key);
            DB::statement("ALTER TABLE {$table} ADD UNIQUE INDEX {$key} ({$columns})");
        } catch (\Exception $e) {
            // Abaikan jika gagal
        }
    }
}
