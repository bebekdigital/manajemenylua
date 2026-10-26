# Konsep Panel CRUD & Matriks Hak Akses Berdasarkan Role
## Sistem Informasi & Pusat Data Terpadu — Yayasan Li Ulil Albab Karanganyar

---

### 1. Pendahuluan & Tujuan
Dokumen ini merumuskan konsep arsitektur hak akses (Role-Based Access Control / RBAC) dan rancangan panel CRUD (Create, Read, Update, Delete) untuk setiap menu serta tabel di dalam portal sistem manajemen sekolah Yayasan Li Ulil Albab Karanganyar.

Tujuan utama:
1. **Keamanan Data**: Mencegah akses, manipulasi, atau penghapusan data antar unit atau luar wewenang.
2. **Efisiensi Pengguna**: Menyajikan antarmuka yang relevan dan fokus sesuai tugas pokok dan fungsi (tupoksi) masing-masing pegawai.
3. **Pemisahan Tingkat Tanggung Jawab**: Membedakan hak akses tingkat Pengurus Yayasan (lintas unit), Pimpinan Unit (level sekolah), Staf Administrasi/TU (operasional harian), dan Tenaga Pendidik (kelas binaan).

---

### 2. Klastering Role Pengguna (5 Tingkatan Akses)

Berdasarkan struktur jabatan yang terdaftar di master data yayasan dan unit sekolah, hak akses dikelompokkan ke dalam **5 Klaster Wewenang**:

| Klaster | Level | Daftar Role / Jabatan Riil di Sistem | Cakupan Kerja (Scope) |
| :--- | :---: | :--- | :--- |
| **Klaster 1: Super Administrator** | Global IT | `superadmin` | Seluruh data yayasan, seluruh unit, konfigurasi server, dan master akun pengguna. |
| **Klaster 2: Pimpinan Yayasan / Kabid** | Executive | `Kepala Bidang Pendidikan`, `Kepala Bidang SDM`, `Kepala Bidang Keuangan`, `Kepala Bidang Sarpras`, `Kepala Bidang Dakwah & Humas`, `Bendahara Yayasan`, `Pengawas Sekolah`, `Penjamin Mutu Pendidikan` | Monitoring lintas unit, pengawasan SDM, kebijakan anggaran, persetujuan program. |
| **Klaster 3: Pimpinan Unit Sekolah** | Manajerial Unit | `Kepala Sekolah`, `Wakil Kepala Sekolah` (Kurikulum / Kesiswaan) | Terkunci pada **unit sekolah masing-masing** (misal: hanya SDIT atau SMPIT). Menentukan wali kelas, rombel, dan persetujuan data unit. |
| **Klaster 4: Tenaga Administrasi (TU & Operator)** | Operasional | `Tenaga Administrasi (TU)`, `Operator Dapodik`, `Bendahara Unit` | Input & pembaruan data harian siswa, unggah berkas/dokumen, cetak formulir, dan ekspor-impor Excel pada unitnya. |
| **Klaster 5: Tenaga Pendidik (Guru)** | Edukasional | `Wali Kelas`, `Guru Kelas`, `Guru Mata Pelajaran` | Khusus rombel / kelas yang diampu: presensi, jurnal mengajar, catatan karakter, dan pelaporan capaian nilai. |

---

### 3. Matriks Hak Akses CRUD pada Menu "Pengaturan" (Portal Master)

Menu Pengaturan (`/portal`) mengelola data induk yayasan, struktur sekolah, dan akun akses sistem:

| Menu / Tab | Tabel Database | Klaster 1: Superadmin | Klaster 2: Pimpinan Yayasan | Klaster 3: Pimpinan Unit | Klaster 4: TU & Operator | Klaster 5: Guru |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **Tab Akademik: Tahun Pelajaran** | `academic_years` | **CRUD Penuh** (Set TA aktif, semester baru) | **Read-Only** | **Read-Only** | **Read-Only** | Disembunyikan |
| **Tab Akademik: Mapping Kelas Aktif** | `academic_year_classes` | **CRUD Penuh** | **Read-Only** | **Update** (Rombel unitnya) | **Read-Only** | Disembunyikan |
| **Tab Unit & Bidang: Jenjang Pendidikan** | `school_levels` | **CRUD Penuh** (PAUD/TK, SD, SMP, SMA) | **Read-Only** | **Read-Only** | Disembunyikan | Disembunyikan |
| **Tab Unit & Bidang: Unit Sekolah** | `school_units` | **CRUD Penuh** (Tambah/Hapus Unit Sekolah) | **Read-Only** | **Read-Only** | Disembunyikan | Disembunyikan |
| **Tab Unit & Bidang: Program Sekolah** | `school_programs` | **CRUD Penuh** (Reguler, Tahfidz, Boarding) | **Read / Update** | **Read-Only** | Disembunyikan | Disembunyikan |
| **Tab Unit & Bidang: Master Rombel / Kelas** | `school_classes` | **CRUD Penuh** | **Read-Only** | **CRUD** (Unit bersangkutan) | **Read-Only** | Disembunyikan |
| **Tab Unit & Bidang: Jabatan Unit** | `school_positions` | **CRUD Penuh** | **Read / Update** | **Read-Only** | Disembunyikan | Disembunyikan |
| **Tab Unit & Bidang: Bidang Yayasan & Jabatan** | `departments`, `department_positions` | **CRUD Penuh** | **CRUD** (Khusus pada bidangnya) | Disembunyikan | Disembunyikan | Disembunyikan |
| **Tab Pengguna: Akun Pengguna** | `users` | **CRUD Penuh** | **Read-Only** (Staf di bidangnya) | **Read-Only** (Staf di unitnya) | Disembunyikan | Disembunyikan |
| **Tab Pengguna: Generate Akun Otomatis** | `users` $\leftarrow$ `employees` | **Full Action** | Disembunyikan | Disembunyikan | Disembunyikan | Disembunyikan |

---

### 4. Matriks Hak Akses CRUD pada Menu Utama Aplikasi

| Modul Menu | Tabel Database Terkait | Klaster 1: Superadmin | Klaster 2: Pimpinan Yayasan | Klaster 3: Pimpinan Unit | Klaster 4: TU & Operator | Klaster 5: Guru |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: |
| **Data Siswa: Direktori & Detail** | `students`, `student_siblings` | **CRUD Penuh** (Semua unit) | **Read-Only** (Semua unit) | **CRUD** (Hanya unitnya) | **CRUD** (Hanya unitnya) | **Read-Only** (Hanya kelasnya) |
| **Data Siswa: Impor / Ekspor Excel** | `students` | **Akses Penuh** | **Ekspor Saja** | **Ekspor Saja** | **Impor & Ekspor** | Disembunyikan |
| **Data Siswa: Riwayat Akademik / Mutasi** | `student_academic_records` | **CRUD Penuh** | **Read-Only** | **Approval / CRUD** | **Input Mutasi** | **Read-Only** |
| **Data Pegawai: Direktori & Biodata** | `employees`, `employee_records` | **CRUD Penuh** | **CRUD** (Khusus SDM) / Read (Kabid lain) | **Read-Only** (Staf unitnya) | **Read-Only** (Data umum) | **Read / Update** (Profil pribadi) |
| **Data Pegawai: SK & Riwayat Penugasan** | `employee_positions`, `employee_documents` | **CRUD Penuh** | **CRUD** (Khusus Kabid SDM) | **Usulan Penugasan** | Disembunyikan | Disembunyikan |
| **Pembelajaran: Plotting Rombel & Siswa** | `classrooms`, `student_classrooms` | **CRUD Penuh** | **Read-Only** | **Approval & Setting Wali** | **Operasional Plotting** | Disembunyikan |
| **Pembelajaran: Jurnal & Presensi Kelas** | `attendances`, `learning_records` *(roadmap)* | **CRUD Penuh** | **Monitoring** | **Monitoring & Rekap** | **Bantu Rekap** | **CRUD** (Kelas ampunya) |
| **Administrasi: Template & Cetak Dokumen** | `student_documents`, Pengaturan Blanko | **CRUD Penuh** | **Read & Cetak** | **Cetak & TTD Unit** | **Cetak Massal Siswa** | **Cetak Rapor/Biodata Kelas** |
| **Statistik & Laporan Analitik** | Agregasi data siswa & pegawai | **Global (Semua)** | **Global & Eksekutif** | **Spesifik Unitnya** | **Spesifik Unitnya** | **Spesifik Kelasnya** |

---

### 5. Rancangan Konsep Panel CRUD Spesifik per Klaster Role

#### A. Panel Superadmin (The Control Tower)
* **Karakteristik Tampilan**: Penuh kendali, menampilkan seluruh switch unit, tool audit, dan manajemen sistem.
* **Fitur & Komponen CRUD**:
  1. **Global Unit Switcher**: Dropdown cepat untuk beralih pandangan (Semua Unit, TKIT, SDIT, SMPIT, SMAIT).
  2. **Batch User Generator**: Tombol generate akun otomatis berdasarkan data penugasan pegawai dengan format username cerdas dan password tanggal lahir (`YYYYMMDD`).
  3. **Role & Position Assignment**: Otoritas mengubah role pengguna menjadi superadmin atau jabatan tertentu.
  4. **Emergency Recovery**: Tombol reset password massal, force active tahun ajaran, dan audit log riwayat perubahan data krusial.

#### B. Panel Pimpinan Yayasan / Kabid (The Executive Dashboard)
* **Karakteristik Tampilan**: Elegan, minim tombol aksi hapus/edit yang berisiko, kaya akan grafik perbandingan antar-unit, tombol ekspor laporan eksekutif, dan modul *Approval*.
* **Fitur & Komponen CRUD**:
  1. **Cross-Unit Comparison**: Grafik perbandingan jumlah siswa, keterisian kelas, dan sebaran wilayah antar sekolah yayasan.
  2. **Panel Khusus Kabid SDM**:
     * CRUD riwayat mutasi / rotasi penugasan guru antar-unit.
     * Pengawasan masa berlaku SK, evaluasi kinerja, dan verifikasi berkas ijazah/sertifikasi pegawai.
  3. **Panel Khusus Kabid Pendidikan**:
     * Evaluasi kurikulum, pengawasan rombel, dan verifikasi rasio guru:siswa ideal di tiap unit.

#### C. Panel Pimpinan Unit / Kepala Sekolah (The Unit Command Center)
* **Karakteristik Tampilan**: Terisolasi secara aman (*data-scoped*) hanya untuk unit sekolahnya.
* **Fitur & Komponen CRUD**:
  1. **Unit Classroom Builder**: Panel menetapkan guru mana yang menjabat sebagai Wali Kelas untuk setiap rombel di tahun ajaran baru.
  2. **Approval Mutasi Siswa**: Validasi ketika ada siswa pindah masuk, keluar, atau permohonan dispensasi biaya/PIP.
  3. **Verifikasi Keaktifan Siswa & Pegawai**: Memberi status aktif/cuti/keluar sebelum data masuk ke laporan resmi yayasan.

#### D. Panel Tenaga Administrasi (TU & Operator) (The Working Engine)
* **Karakteristik Tampilan**: Dioptimalkan untuk kecepatan input data berulang, pencarian instan (*realtime search*), dan pemrosesan dokumen.
* **Fitur & Komponen CRUD**:
  1. **Excel Batch Importer & Validator**: Unggah data siswa dari format Dapodik / Excel dengan pratayang validasi kesalahan kolom sebelum disimpan.
  2. **Panel Checklist Dokumen Siswa**: Tabel indikator kelengkapan dokumen (Akta Lahir, KK, KIP, Foto) dengan status warna (Lengkap / Kurang).
  3. **One-Click Document Generator**: Cetak Surat Keterangan Siswa Aktif, Formulir Biodata Standar, dan Label Kartu Pelajar.

#### E. Panel Guru & Wali Kelas (The Classroom Workspace)
* **Karakteristik Tampilan**: Bersih, intuitif, fokus pada kelas ampunya, dan sangat responsif untuk akses via ponsel atau tablet.
* **Fitur & Komponen CRUD**:
  1. **My Classroom Hub**: Menampilkan daftar siswa kelas yang diampu secara otomatis tanpa perlu mencari manual di antara ribuan siswa unit lain.
  2. **Student Notes & Character Log**: Input catatan anekdot, prestasi, bimbingan konseling, atau kedisiplinan siswa.
  3. **Direct WA Contact**: Tombol aksi langsung membuka obrolan WhatsApp dengan nomor kontak ayah/ibu siswa.

---

### 6. Rekomendasi Arsitektur Teknis Implementasi

1. **Multi-Tenancy Scoping (Pemisahan Data Otomatis)**:
   * Menggunakan Laravel Eloquent Global Scope atau Middleware `ScopeByUnit`:
     ```php
     if (!auth()->user()->isSuperadmin() && auth()->user()->unit) {
         $query->where('unit', auth()->user()->unit);
     }
     ```
   * Memastikan pimpinan unit dan TU tidak bisa melihat atau menyunting data unit lain secara sengaja maupun tidak sengaja (ID tampering).

2. **Laravel Authorization (Policies & Gates)**:
   * Membuat Policy granular: `StudentPolicy`, `EmployeePolicy`, `PortalPolicy`.
   * Pengecekan terpusat via Gates:
     ```php
     Gate::define('access-portal-settings', fn(User $user) => in_array($user->role, ['superadmin', 'Kepala Bidang SDM', 'Kepala Bidang Pendidikan']));
     ```

3. **Inertia Props & Vue Conditional Rendering**:
   * Melempar informasi kemampuan user ke frontend via `page.props.auth.permissions`.
   * Di template Vue, tombol aksi dikontrol dengan aman:
     ```html
     <button v-if="can('create-student')" @click="openCreateModal">Tambah Siswa</button>
     ```

4. **Audit Trail (Activity Log)**:
   * Menyimpan log riwayat: `user_id`, `action` (create/update/delete), `table_name`, `record_id`, `before_data`, `after_data`.
   * Memudahkan identifikasi jika terjadi perubahan atau penghapusan data krusial.

---
*Dokumen ini disusun sebagai acuan arsitektur pengembangan hak akses dan antarmuka CRUD Sistem Informasi Yayasan Li Ulil Albab Karanganyar.*
