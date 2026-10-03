<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevertToIdPrimaryKeyCommand extends Command
{
    protected $signature = 'app:revert-to-id-primary-key';
    protected $description = 'Kembalikan primary key ke ID bawaan Laravel';

    public function handle()
    {
        $this->info('Memulai pengembalian Primary Key ke ID bawaan Laravel...');
        
        try {
            // 1. Tambahkan kolom id di tabel students jika belum ada
            if (!Schema::hasColumn('students', 'id')) {
                $this->info('Merombak tabel students (Menambahkan id, menghapus nisn sebagai Primary Key)...');
                
                // Harus mencabut foreign key lama dulu sebelum mengutak-atik nisn
                $this->dropForeignKeySafe('student_academic_records', 'sar_student_nisn_foreign');
                $this->dropForeignKeySafe('student_siblings', 'ss_student_nisn_foreign');

                // Tambahkan id
                DB::statement("ALTER TABLE students ADD COLUMN id BIGINT UNSIGNED FIRST");
                
                // Hapus primary key nisn
                DB::statement("ALTER TABLE students DROP PRIMARY KEY");
                
                // Set id jadi primary key auto increment
                DB::statement("ALTER TABLE students MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
                
                // Kembalikan unique nisn
                $this->addUniqueIndexSafe('students', 'students_nisn_unique', 'nisn');
            }

            // 2. Tambahkan kolom student_id di tabel relasi jika belum ada
            $this->info('Menambahkan kolom student_id ke tabel relasi...');
            if (!Schema::hasColumn('student_academic_records', 'student_id')) {
                DB::statement("ALTER TABLE student_academic_records ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER id");
            }
            if (!Schema::hasColumn('student_siblings', 'student_id')) {
                DB::statement("ALTER TABLE student_siblings ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER id");
            }

            // 3. Isi kolom student_id dengan data ID dari tabel students
            $this->info('Mengisi data ID ke tabel relasi...');
            if (Schema::hasColumn('students', 'nisn') && Schema::hasColumn('student_academic_records', 'student_nisn')) {
                DB::statement("UPDATE student_academic_records sar JOIN students s ON sar.student_nisn = s.nisn SET sar.student_id = s.id");
                DB::statement("UPDATE student_siblings ss JOIN students s ON ss.student_nisn = s.nisn SET ss.student_id = s.id");
            }

            // 4. Hapus Foreign Key lama dan kolom student_nisn
            $this->info('Menghapus Foreign Key lama (student_nisn)...');
            $this->dropForeignKeySafe('student_academic_records', 'sar_student_nisn_foreign');
            $this->dropForeignKeySafe('student_siblings', 'ss_student_nisn_foreign');
            
            $this->dropIndexSafe('student_academic_records', 'sar_nisn_year_unique');

            if (Schema::hasColumn('student_academic_records', 'student_nisn')) {
                DB::statement("ALTER TABLE student_academic_records DROP COLUMN student_nisn");
            }
            if (Schema::hasColumn('student_siblings', 'student_nisn')) {
                DB::statement("ALTER TABLE student_siblings DROP COLUMN student_nisn");
            }

            // 5. Jadikan student_id NOT NULL dan pasang Foreign Key baru
            $this->info('Memasang Foreign Key baru (student_id)...');
            DB::statement("ALTER TABLE student_academic_records MODIFY student_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE student_siblings MODIFY student_id BIGINT UNSIGNED NOT NULL");

            $this->addForeignKeySafe('student_academic_records', 'student_academic_records_student_id_foreign', 'student_id', 'students', 'id');
            $this->addForeignKeySafe('student_siblings', 'student_siblings_student_id_foreign', 'student_id', 'students', 'id');

            $this->addUniqueIndexSafe('student_academic_records', 'student_academic_records_student_id_academic_year_id_unique', 'student_id, academic_year_id');

            $this->info('Proses pengembalian berhasil! Database sekarang kembali menggunakan ID Auto Increment.');

        } catch (\Exception $e) {
            $this->error('Gagal melakukan migrasi: ' . $e->getMessage());
        }
    }

    private function dropForeignKeySafe($table, $key)
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$key}");
        } catch (\Exception $e) {}
    }

    private function dropIndexSafe($table, $key)
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$key}");
        } catch (\Exception $e) {}
    }

    private function addForeignKeySafe($table, $key, $column, $refTable, $refColumn)
    {
        try {
            $this->dropForeignKeySafe($table, $key);
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$key} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE CASCADE");
        } catch (\Exception $e) {}
    }

    private function addUniqueIndexSafe($table, $key, $columns)
    {
        try {
            $this->dropIndexSafe($table, $key);
            DB::statement("ALTER TABLE {$table} ADD UNIQUE INDEX {$key} ({$columns})");
        } catch (\Exception $e) {}
    }
}
