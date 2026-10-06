<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToIdPrimaryKeyCommand extends Command
{
    protected $signature = 'app:migrate-to-id-primary-key';

    protected $description = 'Ubah tabel students kembali menggunakan id auto-increment sebagai primary key, dan NISN sebagai unique column.';

    public function handle()
    {
        $this->info('Memulai perombakan Primary Key kembali ke ID (Auto-Increment)...');

        try {
            // 1. Hapus Foreign Key lama yang merujuk ke NISN
            $this->info('Menghapus Foreign Key lama (student_nisn)...');
            $this->dropForeignKeySafe('student_academic_records', 'sar_student_nisn_foreign');
            $this->dropForeignKeySafe('student_siblings', 'ss_student_nisn_foreign');

            // Drop unique index
            $this->dropIndexSafe('student_academic_records', 'sar_nisn_year_unique');

            // 2. Kembalikan Primary Key tabel Students ke ID
            $this->info('Merombak tabel students (Menambahkan id, menghapus Primary Key NISN)...');
            if (! Schema::hasColumn('students', 'id')) {
                // Drop primary key
                DB::statement('ALTER TABLE students DROP PRIMARY KEY');

                // Add id as primary key auto-increment
                DB::statement('ALTER TABLE students ADD id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

                // Add unique index for NISN
                $this->addUniqueIndexSafe('students', 'students_nisn_unique', 'nisn');
            }

            // 3. Tambahkan kolom student_id di tabel relasi jika belum ada
            $this->info('Menambahkan kolom student_id ke tabel relasi...');
            if (! Schema::hasColumn('student_academic_records', 'student_id')) {
                DB::statement('ALTER TABLE student_academic_records ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER id');
            }
            if (! Schema::hasColumn('student_siblings', 'student_id')) {
                DB::statement('ALTER TABLE student_siblings ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER id');
            }

            // 4. Isi kolom student_id dengan id dari tabel students
            $this->info('Mengisi data student_id ke tabel relasi...');
            if (Schema::hasColumn('student_academic_records', 'student_nisn') && Schema::hasColumn('students', 'id')) {
                DB::statement('UPDATE student_academic_records sar JOIN students s ON sar.student_nisn = s.nisn SET sar.student_id = s.id');
                DB::statement('UPDATE student_siblings ss JOIN students s ON ss.student_nisn = s.nisn SET ss.student_id = s.id');
            }

            // 5. Hapus kolom student_nisn
            $this->info('Menghapus kolom student_nisn...');
            if (Schema::hasColumn('student_academic_records', 'student_nisn')) {
                DB::statement('ALTER TABLE student_academic_records DROP COLUMN student_nisn');
            }
            if (Schema::hasColumn('student_siblings', 'student_nisn')) {
                DB::statement('ALTER TABLE student_siblings DROP COLUMN student_nisn');
            }

            // 6. Jadikan student_id NOT NULL dan pasang Foreign Key baru
            $this->info('Memasang Foreign Key baru (student_id)...');
            DB::statement('ALTER TABLE student_academic_records MODIFY student_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE student_siblings MODIFY student_id BIGINT UNSIGNED NOT NULL');

            $this->addForeignKeySafe('student_academic_records', 'student_academic_records_student_id_foreign', 'student_id', 'students', 'id');
            $this->addForeignKeySafe('student_siblings', 'student_siblings_student_id_foreign', 'student_id', 'students', 'id');

            $this->addUniqueIndexSafe('student_academic_records', 'student_academic_records_student_id_academic_year_id_unique', 'student_id, academic_year_id');

            $this->info('Proses perombakan berhasil! Database sekarang menggunakan ID sebagai Primary Key.');

        } catch (\Exception $e) {
            $this->error('Gagal melakukan migrasi: '.$e->getMessage());
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
            $this->dropForeignKeySafe($table, $key);
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$key} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE CASCADE");
        } catch (\Exception $e) {
            // Abaikan jika gagal
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
