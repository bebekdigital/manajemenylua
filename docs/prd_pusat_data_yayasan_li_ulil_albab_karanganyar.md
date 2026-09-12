# Product Requirements Document (PRD)
## Sistem Informasi & Pusat Data Terpadu — Yayasan Li Ulil Albab Karanganyar

---

### 1. Executive Summary & Project Overview
* **Nama Produk/Proyek**: Pusat Data Yayasan Li Ulil Albab Karanganyar (Foundation Data Center & Academic Portal)
* **Organisasi / Klien**: Yayasan Li Ulil Albab Karanganyar
* **Tujuan Utama**: Membangun portal administrasi terpusat yang modern, formal, dan aman untuk mengelola ekosistem pendidikan yayasan, mencakup data siswa, staf/pegawai, laporan ringkasan/analitik eksekutif, serta mekanisme autentikasi multi-peran (multi-role authentication).
* **Target Pengguna**:
  1. **Super Admin / Pengurus Yayasan**: Memantau metrik agregat, ringkasan anggaran, analitik pertumbuhan, dan tata kelola global.
  2. **Kepala Sekolah & Administrator Akademik**: Mengelola direktori data siswa, proses pendaftaran, kelas, dan riwayat akademik.
  3. **HR / Kepegawaian**: Mengelola data staf pendidik & kependidikan, status keaktifan, departemen, dan kontak.
  4. **Tenaga Pendidik (Guru/Staf)**: Mengakses jadwal, pelaporan operasional, dan pengumuman internal.

---

### 2. Design Language & Brand System
* **Brand Aesthetics**: Formal, akademis, kredibel, dan berwibawa (prestigious institutional feel).
* **Color Palette**:
  * **Primary**: Hijau Islami Formal / Deep Institutional Emerald (`#064e3b` / `#006837`)
  * **Secondary / Accent**: Emas & Kuning Madu Hangat (`#eab308` / `#f59e0b` / aksen gold `#d4af37`)
  * **Background / Neutral**: Clean Pearl White / Off-White (`#fbf9f8` / `#fcf9f8`), Slate Gray untuk outline dan border halus.
* **Typography**:
  * **Primary Interface & Headings**: `Plus Jakarta Sans` (modern, bersih, geometris, profesional).
  * **Body & Data Grid**: `Inter` & `Plus Jakarta Sans` untuk memastikan keterbacaan tinggi pada dataset padat.
* **Layout & Navigation Architecture**:
  * **Persistent Left Sidebar Navigation**: Navigasi vertikal terstruktur dengan brand mark yayasan, tab menu (Overview, Student Data, Staff Records, Analytics, Settings), aksi cepat "Generate Report", serta utilitas bawah (Help Center, Logout).
  * **Sticky Top Header**: Pencarian global, notifikasi instan, status akun, dan profil avatar.

---

### 3. Core Modules & Feature Specifications

#### Modul 1: Autentikasi & Portal Akses Multi-Role (`/login`)
* **Tujuan**: Menyediakan gerbang masuk yang aman dan fleksibel berdasarkan peran pengguna.
* **Fitur Utama**:
  * **Pilihan Peran (Role Selector)**: Dropdown role (Administrator, Pengurus Yayasan, Guru/Staf, Tenaga Administrasi).
  * **Kredensial**: Input Nomor Induk / Username / Email terenkripsi dan kata sandi dengan toggle intip/lupa sandi.
  * **Security Assurance Badge**: Notasi keamanan enkripsi standar industri dan pengawasan data yayasan.
  * **Pilihan Tata Letak**: Varian Clean Institutional Card (tengah) dan Split-Screen Branding.

#### Modul 2: Dashboard Eksekutif & Ringkasan (`/dashboard`)
* **Tujuan**: Memberikan visibilitas instan terhadap metrik kunci operasional yayasan.
* **Fitur Utama**:
  * **Statistik Utama (KPI Cards)**:
    * Total Siswa (dengan indikator tren pertumbuhan % YoY).
    * Total Pegawai/Staf (dengan indikator rekrutmen terbaru).
    * Program Aktif (persebaran di seluruh unit/kampus).
    * Ringkasan Anggaran (Budget Overview kuartalan).
  * **Aktivitas Terkini (Recent Activity Feed)**: Audit log pendaftaran siswa baru, update role staf, dan status perubahan data secara real-time.
  * **Papan Pengumuman (Announcements Board)**: Rapat staf kuartalan, jadwal pemeliharaan sistem, dan agenda yayasan.

#### Modul 3: Direktori Siswa (`/students`)
* **Tujuan**: Sentralisasi database peserta didik dari seluruh jenjang.
* **Fitur Utama**:
  * Filter multi-kategori: Berdasarkan jenjang/kelas (Grade 1-6 / SMP / SMA) dan status (Aktif, Non-Aktif, Pending).
  * Pencarian instan berdasarkan Nama atau NIS/Student ID.
  * Tabel komprehensif: Avatar inisial, NIS, tanggal registrasi, badge status dinamis, dan menu aksi baris (detail, edit, arsip).
  * Paginasi data yang responsif.

#### Modul 4: Direktori Pegawai & Staf (`/staff`)
* **Tujuan**: Manajemen sumber daya manusia (SDM) pendidik dan kependidikan.
* **Fitur Utama**:
  * Quick filter departemen: Academic, Administration, Facilities & IT.
  * Aksi cepat: Tombol "Add New Staff" dan "Export Data" (Excel/PDF).
  * Kolom data: Nama lengkap & gelar, NIP/Employee ID, status kepegawaian (Active, On Leave, Resigned), posisi & departemen, kontak email/telepon.

#### Modul 5: Analitik & Pelaporan (`/analytics`)
* **Tujuan**: Visualisasi data untuk pengambilan keputusan strategis oleh pengurus yayasan.
* **Fitur Utama**:
  * Tren penerimaan siswa tahunan.
  * Rasio guru dan siswa.
  * Laporan alokasi anggaran dan serapan dana operasional.

---

### 4. Non-Functional Requirements
1. **Keamanan (Security)**:
   * Role-Based Access Control (RBAC) ketat antar peran.
   * Perlindungan terhadap SQL Injection, XSS, dan CSRF.
   * Enkripsi data sensitif (password hash Argon2/Bcrypt, SSL/TLS 1.3).
2. **Performa & Keterbacaan**:
   * *First Contentful Paint (FCP)* di bawah 1.2 detik pada jaringan desktop standar.
   * Optimalisasi tata letak data tabel agar nyaman dibaca pada resolusi 1280px hingga 1920px+.
3. **Aksesibilitas (A11y)**:
   * Kontras warna teks memenuhi standar WCAG 2.1 AA (rasio kontras minimum 4.5:1 untuk teks utama di atas latar hijau/putih).
   * Dukungan navigasi keyboard pada seluruh form dan tabel interaktif.

---

### 5. Roadmap & Tahapan Rilis
* **Fase 1 (Pondasi UI & Shell)**: Dashboard ringkasan, halaman direktori siswa, direktori staf, dan halaman login multi-role (Selesai pada kanvas saat ini).
* **Fase 2 (Interaktivitas & Form Entri)**: Modal penambahan staf/siswa baru, filter interaktif lanjutan, dan alur lupa password.
* **Fase 3 (Integrasi Backend & RBAC)**: Integrasi API database, autentikasi sesi JWT/OAuth, dan export laporan ke format PDF/XLSX.
