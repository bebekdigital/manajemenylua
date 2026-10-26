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

### 3. Daftar 18 Modul Fitur Web Riil yang Dikelola dalam Matriks Hak Akses

Berikut adalah daftar 18 modul fitur riil di aplikasi yang dapat dikonfigurasi hak aksesnya secara granular (Lihat, Tambah, Ubah, Hapus, Ekspor, Impor):

| No | Grup Fitur (`feature_group`) | Nama Modul | Kategori | Aksi yang Didukung |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `dashboard_overview` | Beranda & Ringkasan | Dashboard | Lihat |
| 2 | `student_directory` | Direktori Data Siswa | Database Siswa | Lihat, Tambah, Ubah, Hapus, Ekspor, Impor |
| 3 | `student_detail` | Detail Profil & Mutasi Siswa | Database Siswa | Lihat, Tambah, Ubah, Hapus |
| 4 | `student_inline_edit` | Edit Cepat (Inline Edit Siswa) | Database Siswa | Lihat, Ubah |
| 5 | `student_photos` | Upload & Galeri Foto Siswa | Database Siswa | Lihat, Tambah, Hapus |
| 6 | `student_mapping` | Pemetaan (Mapping) Kelas Siswa | Database Siswa | Lihat, Tambah, Ubah |
| 7 | `employee_directory` | Direktori Data Pegawai | Database Pegawai | Lihat, Tambah, Ubah, Hapus, Ekspor, Impor |
| 8 | `employee_detail_history` | Detail Profil & Riwayat Penugasan | Database Pegawai | Lihat, Tambah, Ubah, Hapus |
| 9 | `employee_documents` | Dokumen & Berkas SK Pegawai | Database Pegawai | Lihat, Tambah, Hapus, Ekspor |
| 10 | `administration_identity_document` | Cetak Dokumen & Biodata Siswa | Administrasi | Lihat, Tambah, Ekspor |
| 11 | `administration_template_editor` | Editor Template Surat & Dokumen | Administrasi | Lihat, Ubah |
| 12 | `statistics_student_analytics` | Statistik & Analisis Kesiswaan | Statistik | Lihat, Ekspor |
| 13 | `pembelajaran_classrooms` | Rombongan Belajar (Rombel) & Kelas | Pembelajaran | Lihat, Tambah, Ubah, Hapus |
| 14 | `portal_academic_years` | Pengaturan: Tahun Ajaran & Semester | Pengaturan Master | Lihat, Tambah, Ubah, Hapus |
| 15 | `portal_school_units` | Pengaturan: Jenjang, Unit & Program | Pengaturan Master | Lihat, Tambah, Ubah, Hapus |
| 16 | `portal_positions` | Pengaturan: Struktur Bidang & Jabatan | Pengaturan Master | Lihat, Tambah, Ubah, Hapus |
| 17 | `portal_user_accounts` | Pengaturan: Manajemen Akun Pengguna | Pengaturan Master | Lihat, Tambah, Ubah, Hapus |
| 18 | `portal_user_generate` | Pengaturan: Generate Akun Otomatis | Pengaturan Master | Lihat, Tambah |
| 19 | `portal_role_permissions` | Pengaturan: Matriks Hak Akses | Pengaturan Master | Lihat, Ubah |

---

### 4. Matriks Rekomendasi Awal Hak Akses untuk 3 Role Utama

| No | Modul Fitur | Superadmin | Pimpinan: Kepala Bidang SDM | Pendidik: Guru |
| :---: | :--- | :---: | :---: | :---: |
| 1 | Beranda & Ringkasan | **Full (Lihat)** | **Lihat** | **Lihat** |
| 2 | Direktori Data Siswa | **Full CRUD + Ex/Im** | **Lihat, Ekspor** | **Lihat** *(kelas ampunya)* |
| 3 | Detail Profil & Mutasi Siswa | **Full CRUD** | **Lihat** | **Lihat** *(kelas ampunya)* |
| 4 | Edit Cepat (Inline Edit) | **Full (Lihat, Ubah)** | *Tidak Diizinkan* | *Tidak Diizinkan* |
| 5 | Upload & Galeri Foto Siswa | **Full (Lihat, Tambah, Hapus)** | **Lihat** | **Lihat** |
| 6 | Pemetaan (Mapping) Kelas | **Full (Lihat, Tambah, Ubah)** | **Lihat** | *Tidak Diizinkan* |
| 7 | Direktori Data Pegawai | **Full CRUD + Ex/Im** | **Full CRUD + Ex/Im** *(Tupoksi SDM)* | **Lihat** *(biodata rekan)* |
| 8 | Detail Pegawai & Penugasan | **Full CRUD** | **Full CRUD** | **Lihat** *(profil pribadi)* |
| 9 | Dokumen & Berkas SK Pegawai | **Full (Lihat, Tambah, Hapus, Ekspor)** | **Full (Lihat, Tambah, Hapus, Ekspor)** | **Lihat, Ekspor** *(SK pribadi)* |
| 10 | Cetak Dokumen & Biodata | **Full (Lihat, Tambah, Ekspor)** | **Lihat, Ekspor** | **Lihat, Ekspor** *(kelas ampunya)* |
| 11 | Editor Template Surat | **Full (Lihat, Ubah)** | *Tidak Diizinkan* | *Tidak Diizinkan* |
| 12 | Statistik & Analisis Kesiswaan | **Full (Lihat, Ekspor)** | **Lihat, Ekspor** | **Lihat** |
| 13 | Rombel & Kelas (Pembelajaran) | **Full CRUD** | **Lihat** | **Lihat, Ubah** *(kelas ampunya)* |
| 14 | Pengaturan: Tahun Ajaran | **Full CRUD** | **Lihat** | *Disembunyikan* |
| 15 | Pengaturan: Jenjang & Unit | **Full CRUD** | **Lihat** | *Disembunyikan* |
| 16 | Pengaturan: Bidang & Jabatan | **Full CRUD** | **Full CRUD** *(Bidang SDM)* | *Disembunyikan* |
| 17 | Pengaturan: Manajemen Akun | **Full CRUD** | **Lihat** | *Disembunyikan* |
| 18 | Pengaturan: Generate Akun | **Full (Lihat, Tambah)** | *Disembunyikan* | *Disembunyikan* |
| 19 | Pengaturan: Matriks Hak Akses | **Full (Lihat, Ubah)** | **Lihat** | *Disembunyikan* |
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
