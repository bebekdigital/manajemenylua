<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    students: { type: Array, required: true },
    selectedAcademicYear: { type: Object, default: null },
    allAcademicYears: { type: Array, default: () => [] },
    allUnits: { type: Array, default: () => [] },
    allLevels: { type: Array, default: () => [] },
    allPrograms: { type: Array, default: () => [] },
    allClassrooms: { type: Array, default: () => [] },
});

// ============================================================
// Student Data (mutable local copy)
// ============================================================
const students = reactive(props.students.map(s => ({ ...s })));

const isSaving = ref(false);
const saveError = ref(null);
const successMessage = ref(null);

// ============================================================
// Dropdown Options (selaras dengan Edit di Detail Siswa)
// ============================================================

// 1. Pilihan Unit Sekolah Formal (SDIT, SMPIT, SMAIT)
const formalUnitOptions = computed(() => {
    const list = (props.allUnits || []).filter(u => {
        const upper = (u || '').toUpperCase();
        return !upper.includes('PPTQ') && !upper.includes('PESANTREN') && !upper.includes('PONDOK');
    });
    if (list.length > 0) return list;
    return ['SDIT Ulil Albab', 'SMP IT Tahfidzul Quran Ulil Albab Karanganyar', 'SMA IT Ulil Albab'];
});

// 2. Pilihan Unit Kepesantrenan / Asrama (PPTQ Ulil Albab)
const pesantrenUnitOptions = computed(() => {
    const list = (props.allUnits || []).filter(u => {
        const upper = (u || '').toUpperCase();
        return upper.includes('PPTQ') || upper.includes('PESANTREN') || upper.includes('PONDOK');
    });
    if (list.length > 0) return list;
    return ['PPTQ Ulil Albab'];
});

// 3. Pilihan Program Pendidikan
const programOptions = computed(() => {
    const set = new Set();
    (props.allPrograms || []).forEach(p => {
        if (p && typeof p === 'string' && p.trim()) set.add(p.trim());
    });
    students.forEach(s => {
        if (s.program && typeof s.program === 'string' && s.program.trim()) {
            set.add(s.program.trim());
        }
    });
    if (set.size === 0) {
        set.add('Fullday');
        set.add('Boarding');
        set.add('Umum');
    }
    return Array.from(set);
});

// 4. Pilihan Jenjang Pendidikan
const levelOptions = computed(() => {
    if (props.allLevels && props.allLevels.length > 0) {
        return props.allLevels;
    }
    return ['PAUD', 'TK', 'SD', 'SMP', 'SMA'];
});

// 5. Pilihan Angkatan
const angkatanOptions = computed(() => {
    const set = new Set();
    students.forEach(s => {
        if (s.angkatan) set.add(String(s.angkatan).trim());
    });
    return Array.from(set).sort((a, b) => {
        const nA = Number(a);
        const nB = Number(b);
        if (!isNaN(nA) && !isNaN(nB)) return nB - nA;
        return b.localeCompare(a);
    });
});

// 6. Pilihan Kelas
const classroomOptions = computed(() => {
    const set = new Set(props.allClassrooms || []);
    students.forEach(s => {
        if (s.kelas && typeof s.kelas === 'string' && s.kelas.trim()) {
            set.add(s.kelas.trim());
        }
    });
    return Array.from(set).sort();
});

// 7. Pilihan Jenis Kelamin
const jkOptions = [
    { value: 'L', label: 'L (Laki-laki)' },
    { value: 'P', label: 'P (Perempuan)' },
];

// 9. Pilihan Status Keaktifan Master (Data Statis di students)
const statusKeaktifanOptions = [
    { value: 'Aktif', label: 'Aktif' },
    { value: 'Tidak Aktif', label: 'Tidak Aktif' },
    { value: 'Lulus', label: 'Lulus' },
];

// 10. Pilihan Keterangan Tidak Aktif (Mutasi Keluar, Dikeluarkan, Putus Sekolah, Meninggal, Lainnya)
const ketTidakAktifOptions = [
    { value: '', label: '-- Kosong (Aktif) --' },
    { value: 'Mutasi Keluar', label: 'Mutasi Keluar' },
    { value: 'Dikeluarkan', label: 'Dikeluarkan' },
    { value: 'Putus Sekolah', label: 'Putus Sekolah' },
    { value: 'Meninggal', label: 'Meninggal' },
    { value: 'Lainnya', label: 'Lainnya' },
];

const agamaOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

const statusKeluargaOptions = ['Anak Kandung', 'Anak Angkat', 'Anak Tiri', 'Lainnya'];

const infoPsbOptions = [
    'Media Sosial', 'Brosur/Flyer', 'Website Sekolah', 'Rekomendasi Teman',
    'Rekomendasi Keluarga', 'Alumni', 'Pameran Pendidikan', 'Spanduk/Banner', 'Lainnya'
];

const statusOrtuOptions = ['Hidup', 'Meninggal'];

const pendidikanOptions = [
    'Tidak Sekolah', 'SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat',
    'D1', 'D2', 'D3', 'D4 / S1', 'S2', 'S3'
];

const pekerjaanOptions = [
    'Tidak Bekerja', 'PNS / ASN', 'TNI / POLRI', 'Karyawan Swasta',
    'Wiraswasta / Pedagang', 'Petani / Peternak', 'Buruh', 'Guru / Dosen',
    'Dokter / Tenaga Medis', 'Pensiunan', 'Lainnya'
];

const penghasilanOptions = [
    'Tidak Berpenghasilan', 'Kurang dari Rp 1.000.000', 'Rp 1.000.000 - Rp 2.000.000',
    'Rp 2.000.000 - Rp 3.000.000', 'Rp 3.000.000 - Rp 5.000.000',
    'Rp 5.000.000 - Rp 10.000.000', 'Lebih dari Rp 10.000.000'
];

// Helper functions for dropdown options
function getOptionValue(opt) {
    if (opt && typeof opt === 'object' && 'value' in opt) return opt.value;
    return opt;
}

function getOptionLabel(opt) {
    if (opt && typeof opt === 'object' && 'label' in opt) return opt.label;
    return opt;
}

function hasOption(options, val) {
    if (!options || !Array.isArray(options)) return false;
    return options.some(opt => getOptionValue(opt) === val);
}

// ============================================================
// Column Groups (collapsible)
// ============================================================
const columnGroups = reactive([
    { key: 'identitas', label: 'Identitas', icon: 'person', expanded: true },
    { key: 'akademik', label: 'Akademik', icon: 'school', expanded: true },
    { key: 'registrasi', label: 'Registrasi', icon: 'how_to_reg', expanded: false },
    { key: 'alamat', label: 'Alamat', icon: 'home', expanded: false },
    { key: 'keluarga_ayah', label: 'Ayah', icon: 'man', expanded: false },
    { key: 'keluarga_ibu', label: 'Ibu', icon: 'woman', expanded: false },
    { key: 'keluarga_wali', label: 'Wali', icon: 'supervisor_account', expanded: false },
    { key: 'bantuan', label: 'Bantuan', icon: 'volunteer_activism', expanded: false },
]);

const columns = computed(() => ({
    identitas: [
        { key: 'nama', label: 'Nama Lengkap', type: 'text', width: 'w-48' },
        { key: 'nipd', label: 'NIPD', type: 'text', width: 'w-28' },
        { key: 'angkatan', label: 'Angkatan', type: 'select', options: angkatanOptions.value, width: 'w-28' },
        { key: 'nik', label: 'NIK', type: 'text', width: 'w-40' },
        { key: 'no_kk', label: 'No KK', type: 'text', width: 'w-40' },
        { key: 'jk', label: 'JK', type: 'select', options: jkOptions, width: 'w-24' },
        { key: 'tempat_lahir', label: 'Tempat Lahir', type: 'text', width: 'w-36' },
        { key: 'tanggal_lahir', label: 'Tgl Lahir', type: 'date', width: 'w-36' },
        { key: 'agama', label: 'Agama', type: 'select', options: agamaOptions, width: 'w-28' },
        { key: 'no_wa', label: 'No WA', type: 'text', width: 'w-32' },
    ],
    akademik: [
        { key: 'unit', label: 'Unit Formal', type: 'select', options: formalUnitOptions.value, width: 'w-48' },
        {
            key: 'unit_pesantren',
            label: 'Unit Asrama / Pesantren',
            type: 'select',
            options: [
                { value: '', label: '-- Tidak Berasrama (Non-Boarding) --' },
                ...pesantrenUnitOptions.value.map(p => ({ value: p, label: p }))
            ],
            width: 'w-52'
        },
        { key: 'program', label: 'Program Pendidikan', type: 'select', options: programOptions.value, width: 'w-36' },
        { key: 'jenjang', label: 'Jenjang', type: 'select', options: levelOptions.value, width: 'w-28' },
        { key: 'tingkat', label: 'Tingkat', type: 'number', width: 'w-20' },
        { key: 'kelas', label: 'Kelas', type: 'select', options: classroomOptions.value, width: 'w-28' },
        { key: 'student_status', label: 'Status Semester (Sistem)', type: 'badge', readonly: true, width: 'w-44' },
    ],
    registrasi: [
        { key: 'status_keaktifan', label: 'Status Keaktifan', type: 'select', options: statusKeaktifanOptions, width: 'w-40' },
        { key: 'keterangan_tidak_aktif', label: 'Ket. Tidak Aktif', type: 'select', options: ketTidakAktifOptions, width: 'w-44' },
        { key: 'tanggal_tidak_aktif', label: 'Tgl Tidak Aktif', type: 'date', width: 'w-36' },
        { key: 'sekolah_asal', label: 'Sekolah Asal', type: 'text', width: 'w-40' },
        { key: 'status_keluarga', label: 'Status Keluarga', type: 'select', options: statusKeluargaOptions, width: 'w-36' },
        { key: 'anak_ke', label: 'Anak Ke', type: 'number', width: 'w-24' },
        { key: 'diterima_di_jenjang', label: 'Diterima di Jenjang', type: 'select', options: levelOptions.value, width: 'w-40' },
        { key: 'tanggal_diterima', label: 'Tgl Diterima', type: 'date', width: 'w-36' },
        { key: 'info_psb', label: 'Sumber Info PSB', type: 'select', options: infoPsbOptions, width: 'w-44' },
    ],
    alamat: [
        { key: 'jalan', label: 'Jalan', type: 'text', width: 'w-48' },
        { key: 'rt', label: 'RT', type: 'text', width: 'w-16' },
        { key: 'rw', label: 'RW', type: 'text', width: 'w-16' },
        { key: 'dusun', label: 'Dusun', type: 'text', width: 'w-32' },
        { key: 'desa', label: 'Desa', type: 'text', width: 'w-32' },
        { key: 'kecamatan', label: 'Kecamatan', type: 'text', width: 'w-32' },
        { key: 'kabupaten', label: 'Kabupaten', type: 'text', width: 'w-32' },
        { key: 'provinsi', label: 'Provinsi', type: 'text', width: 'w-32' },
    ],
    keluarga_ayah: [
        { key: 'ayah_nama', label: 'Nama Ayah', type: 'text', width: 'w-40' },
        { key: 'ayah_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ayah_pendidikan', label: 'Pendidikan', type: 'select', options: pendidikanOptions, width: 'w-36' },
        { key: 'ayah_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'ayah_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
        { key: 'ayah_status', label: 'Status', type: 'select', options: statusOrtuOptions, width: 'w-28' },
    ],
    keluarga_ibu: [
        { key: 'ibu_nama', label: 'Nama Ibu', type: 'text', width: 'w-40' },
        { key: 'ibu_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ibu_pendidikan', label: 'Pendidikan', type: 'select', options: pendidikanOptions, width: 'w-36' },
        { key: 'ibu_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'ibu_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
        { key: 'ibu_status', label: 'Status', type: 'select', options: statusOrtuOptions, width: 'w-28' },
    ],
    keluarga_wali: [
        { key: 'wali_nama', label: 'Nama Wali', type: 'text', width: 'w-40' },
        { key: 'wali_hubungan', label: 'Hubungan', type: 'text', width: 'w-32' },
        { key: 'wali_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'wali_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
    ],
    bantuan: [
        { key: 'desil', label: 'Desil', type: 'number', width: 'w-20' },
        { key: 'status_pip', label: 'PIP', type: 'checkbox', width: 'w-20' },
        { key: 'pip_keterangan', label: 'Keterangan PIP', type: 'text', width: 'w-44' },
        { key: 'status_kip', label: 'KIP', type: 'checkbox', width: 'w-20' },
        { key: 'no_kip', label: 'No KIP', type: 'text', width: 'w-36' },
    ],
}));

// All flat columns (for bulk edit dropdown)
const allColumns = computed(() => Object.values(columns.value).flat());

// ============================================================
// Data Mode: 'statis' (Master Data) vs 'dinamis' (Riwayat Semester)
// ============================================================
const dataMode = ref(
    (typeof window !== 'undefined' && sessionStorage.getItem('inline_edit_data_mode')) ||
    (typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('academic_year_id') ? 'dinamis' : 'statis')
);

function setMode(mode) {
    dataMode.value = mode;
    bulkField.value = '';
    bulkValue.value = '';
    if (typeof window !== 'undefined') {
        sessionStorage.setItem('inline_edit_data_mode', mode);
    }
    if (mode === 'dinamis') {
        const ak = columnGroups.find(g => g.key === 'akademik');
        if (ak) ak.expanded = true;
    } else {
        const id = columnGroups.find(g => g.key === 'identitas');
        if (id) id.expanded = true;
    }
}

// Inisialisasi awal grup yang terbuka
if (dataMode.value === 'dinamis') {
    const ak = columnGroups.find(g => g.key === 'akademik');
    if (ak) ak.expanded = true;
}

// ============================================================
// Academic Year Switcher
// ============================================================
const currentAcademicYearId = ref(props.selectedAcademicYear?.id || (props.allAcademicYears?.[0]?.id ?? ''));

function switchAcademicYear() {
    if (!currentAcademicYearId.value) return;
    router.get('/students/inline-edit', { academic_year_id: currentAcademicYearId.value }, {
        preserveState: false,
        preserveScroll: true,
    });
}

// ============================================================
// Search, Filter Keaktifan, & Selection
// ============================================================
const searchQuery = ref('');
const activeStatusFilter = ref('semua'); // 'semua' | 'aktif' | 'tidak_aktif' | 'lulus'
const selectedRows = ref(new Set());

const statusCounts = computed(() => {
    let aktif = 0;
    let tidakAktif = 0;
    let lulus = 0;
    students.forEach(s => {
        const masterStatus = (s.status_keaktifan || '').toLowerCase().trim();
        const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
        if (masterStatus === 'lulus' || ket === 'lulus') {
            lulus++;
        } else if (masterStatus === 'tidak aktif' || masterStatus.includes('tidak') || (ket && ket !== 'lulus')) {
            tidakAktif++;
        } else {
            aktif++;
        }
    });
    return {
        semua: students.length,
        aktif,
        tidakAktif,
        lulus,
    };
});

const filteredStudents = computed(() => {
    let list = students;

    // Filter Status Keaktifan
    if (activeStatusFilter.value === 'aktif') {
        list = list.filter(s => {
            const masterStatus = (s.status_keaktifan || '').toLowerCase().trim();
            const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
            if (masterStatus === 'lulus' || ket === 'lulus') return false;
            if (masterStatus === 'tidak aktif' || masterStatus.includes('tidak') || (ket && ket !== 'lulus')) return false;
            return true;
        });
    } else if (activeStatusFilter.value === 'tidak_aktif') {
        list = list.filter(s => {
            const masterStatus = (s.status_keaktifan || '').toLowerCase().trim();
            const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
            return masterStatus === 'tidak aktif' || masterStatus.includes('tidak') || (ket && ket !== 'lulus');
        });
    } else if (activeStatusFilter.value === 'lulus') {
        list = list.filter(s => {
            const masterStatus = (s.status_keaktifan || '').toLowerCase().trim();
            const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
            return masterStatus === 'lulus' || ket === 'lulus';
        });
    }

    // Filter Search Query
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(s =>
            s.nama?.toLowerCase().includes(q) ||
            s.nisn?.includes(q) ||
            s.nipd?.includes(q) ||
            String(s.angkatan || '').toLowerCase().includes(q) ||
            s.kelas?.toLowerCase().includes(q) ||
            s.unit?.toLowerCase().includes(q) ||
            s.status_keaktifan?.toLowerCase().includes(q) ||
            s.student_status?.toLowerCase().includes(q) ||
            s.ket_tidak_aktif?.toLowerCase().includes(q) ||
            s.keterangan_tidak_aktif?.toLowerCase().includes(q)
        );
    }

    return list;
});

function toggleSelectAll() {
    if (selectedRows.value.size === filteredStudents.value.length) {
        selectedRows.value = new Set();
    } else {
        selectedRows.value = new Set(filteredStudents.value.map(s => s.nisn));
    }
}

function toggleRow(nisn) {
    if (selectedRows.value.has(nisn)) {
        selectedRows.value.delete(nisn);
    } else {
        selectedRows.value.add(nisn);
    }
    selectedRows.value = new Set(selectedRows.value);
}

const allSelected = computed(() =>
    filteredStudents.value.length > 0 && selectedRows.value.size === filteredStudents.value.length
);

const someSelected = computed(() => selectedRows.value.size > 0 && !allSelected.value);

// ============================================================
// Bulk Edit & Multi-Semester Management
// ============================================================
const showBulkPanel = ref(false);
const bulkField = ref('');
const bulkValue = ref('');
const bulkMode = ref('selected'); // 'selected' or 'all'
const bulkColumn = computed(() => allColumns.value.find(c => c.key === bulkField.value));

// Multi-Semester Target Options
const bulkSemesterScope = ref('current'); // 'current' | 'future' | 'manual'
const bulkSelectedSemesters = ref(props.selectedAcademicYear ? [props.selectedAcademicYear.id] : []);
const multiSemesterStudentNisns = ref(new Set());
const activeTargetYearIds = ref(new Set());

// Daftar semester masa kini & masa depan (dimulai dari selectedAcademicYear)
const futureAcademicYearIds = computed(() => {
    if (!props.selectedAcademicYear || !props.allAcademicYears || props.allAcademicYears.length === 0) {
        return props.selectedAcademicYear ? [props.selectedAcademicYear.id] : [];
    }
    const sorted = [...props.allAcademicYears].sort((a, b) => (a.start_date || '').localeCompare(b.start_date || ''));
    const currIdx = sorted.findIndex(ay => ay.id === props.selectedAcademicYear.id);
    if (currIdx === -1) return [props.selectedAcademicYear.id];
    return sorted.slice(currIdx).map(ay => ay.id);
});

function selectAllSemesters() {
    bulkSelectedSemesters.value = (props.allAcademicYears || []).map(ay => ay.id);
}

function clearAllSemesters() {
    bulkSelectedSemesters.value = [];
}

function onBulkFieldChange() {
    bulkValue.value = '';
    if ((bulkField.value === 'keterangan_tidak_aktif' || bulkField.value === 'status_keaktifan') && props.selectedAcademicYear) {
        bulkSelectedSemesters.value = [props.selectedAcademicYear.id];
    }
}

function applyBulk() {
    if (!bulkField.value) return;
    const targets = bulkMode.value === 'all'
        ? students
        : students.filter(s => selectedRows.value.has(s.nisn));

    if (targets.length === 0) {
        saveError.value = 'Tidak ada siswa yang dipilih. Silakan pilih baris siswa terlebih dahulu atau gunakan opsi "Semua".';
        return;
    }

    let val = bulkValue.value;
    if (bulkColumn.value?.type === 'number') {
        val = val === '' ? null : Number(val);
    } else if (bulkColumn.value?.type === 'checkbox') {
        val = Boolean(val);
    }

    targets.forEach(s => {
        s[bulkField.value] = val;
        // Sinkronisasi otomatis keaktifan & keterangan di tabel client
        if (bulkField.value === 'keterangan_tidak_aktif') {
            if (val) {
                s.status_keaktifan = 'Tidak Aktif';
            }
        } else if (bulkField.value === 'status_keaktifan') {
            if (val === 'Aktif') {
                s.keterangan_tidak_aktif = '';
                s.tanggal_tidak_aktif = null;
            } else if (val === 'Lulus') {
                s.keterangan_tidak_aktif = 'Lulus';
            }
        }
    });

    // Jika yang di-bulk adalah keterangan_tidak_aktif atau status_keaktifan, simpan target semesternya
    if (bulkField.value === 'keterangan_tidak_aktif' || bulkField.value === 'status_keaktifan') {
        let targetYears = [];
        if (bulkSemesterScope.value === 'current') {
            targetYears = [props.selectedAcademicYear?.id || currentAcademicYearId.value];
        } else if (bulkSemesterScope.value === 'future') {
            targetYears = futureAcademicYearIds.value;
        } else if (bulkSemesterScope.value === 'manual') {
            targetYears = bulkSelectedSemesters.value.length > 0
                ? bulkSelectedSemesters.value
                : [props.selectedAcademicYear?.id || currentAcademicYearId.value];
        }

        targets.forEach(s => {
            multiSemesterStudentNisns.value.add(s.nisn);
        });
        targetYears.forEach(yId => {
            activeTargetYearIds.value.add(yId);
        });
    }

    const displayVal = val === '' || val === null || val === undefined ? '(dikosongkan)' : val;
    let scopeMsg = '';
    if (bulkField.value === 'keterangan_tidak_aktif' || bulkField.value === 'status_keaktifan') {
        const countSem = activeTargetYearIds.value.size;
        scopeMsg = ` pada ${countSem} semester`;
    }

    successMessage.value = `Berhasil menerapkan nilai "${displayVal}" pada ${targets.length} siswa${scopeMsg} untuk kolom ${bulkColumn.value?.label}. Jangan lupa klik "Simpan Perubahan".`;
    setTimeout(() => {
        if (successMessage.value) successMessage.value = null;
    }, 6000);

    bulkValue.value = '';
    bulkField.value = '';
    showBulkPanel.value = false;
}

// ============================================================
// Save
// ============================================================
function saveChanges() {
    isSaving.value = true;
    saveError.value = null;
    successMessage.value = null;

    const payload = {
        students: students,
        academic_year_id: currentAcademicYearId.value,
        target_academic_year_ids: Array.from(activeTargetYearIds.value),
        multi_semester_students: Array.from(multiSemesterStudentNisns.value),
    };

    router.post('/students/inline-update', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            multiSemesterStudentNisns.value.clear();
            activeTargetYearIds.value.clear();
            successMessage.value = 'Perubahan data siswa berhasil disimpan ke database.';
            setTimeout(() => {
                if (successMessage.value) successMessage.value = null;
            }, 5000);
        },
        onError: (errors) => {
            saveError.value = Object.values(errors).join(', ') || 'Terjadi kesalahan saat menyimpan data.';
            isSaving.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        }
    });
}

// ============================================================
// Helpers
// ============================================================
function toggleGroup(key) {
    const g = columnGroups.find(g => g.key === key);
    if (g) g.expanded = !g.expanded;
}

const visibleGroups = computed(() => {
    return columnGroups.filter(g => {
        if (!columns.value[g.key]) return false;
        if (dataMode.value === 'statis') {
            return g.key !== 'akademik';
        } else {
            return g.key === 'akademik';
        }
    });
});

function groupSpan(groupKey) {
    const g = columnGroups.find(g => g.key === groupKey);
    return g?.expanded ? (columns.value[groupKey]?.length ?? 1) : 1;
}
</script>

<template>
    <Head title="Edit Data Massal Siswa - Ulil Albab" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-white min-h-screen">
        <div class="max-w-[1440px] mx-auto">
            <!-- ======================================================
                 PAGE HEADER
            ====================================================== -->
            <div class="mb-5 sm:mb-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="min-w-0">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                        <Link href="/students" class="hover:text-emerald-800 transition-colors">Database Siswa</Link>
                        <span class="material-symbols-outlined text-[14px] text-slate-400">chevron_right</span>
                        <span class="font-medium text-slate-800">Edit Data Massal</span>
                    </div>

                    <!-- Judul Utama -->
                    <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-slate-900 tracking-tight leading-tight">
                        Edit Data Massal Siswa
                    </h1>
                    <p class="text-xs sm:text-[13.5px] text-slate-500 mt-1 font-medium">
                        Edit langsung nilai pada tabel atau gunakan Bulk Edit untuk mengubah banyak baris sekaligus
                    </p>

                    <!-- Pilihan Mode: Data Statis vs Data Dinamis (Riwayat) -->
                    <div class="mt-3.5 flex items-center gap-2.5 flex-wrap">
                        <div class="inline-flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/90 shadow-2xs">
                            <button
                                type="button"
                                @click="setMode('statis')"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                    dataMode === 'statis'
                                        ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                        : 'text-slate-600 hover:text-slate-900 font-semibold'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[16px]">badge</span>
                                <span>Data Statis</span>
                            </button>
                            <button
                                type="button"
                                @click="setMode('dinamis')"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                    dataMode === 'dinamis'
                                        ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                        : 'text-slate-600 hover:text-slate-900 font-semibold'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[16px]">history_edu</span>
                                <span>Data Dinamis (Riwayat)</span>
                            </button>
                        </div>

                        <!-- Dropdown Tahun Ajaran: HANYA MUNCUL DI DATA DINAMIS -->
                        <transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="dataMode === 'dinamis' && allAcademicYears && allAcademicYears.length > 0"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-300 rounded-xl shadow-2xs"
                            >
                                <span class="material-symbols-outlined text-[18px] text-emerald-800">calendar_month</span>
                                <span class="text-xs font-bold text-emerald-950">Tahun Ajaran:</span>
                                <select
                                    v-model="currentAcademicYearId"
                                    @change="switchAcademicYear"
                                    class="bg-transparent text-xs font-bold text-emerald-950 focus:outline-none cursor-pointer pr-1"
                                >
                                    <option v-for="ay in allAcademicYears" :key="ay.id" :value="ay.id">
                                        {{ ay.label }}{{ ay.is_current ? ' (Aktif)' : '' }}
                                    </option>
                                </select>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Tombol Aksi Header -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                    <!-- Bulk Edit Toggle -->
                    <button
                        type="button"
                        @click="showBulkPanel = !showBulkPanel"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-xs sm:text-sm transition-all shadow-2xs cursor-pointer font-bold',
                            showBulkPanel
                                ? 'bg-emerald-100 border-emerald-300 text-emerald-950 shadow-xs'
                                : 'bg-white border-slate-300 text-slate-700 hover:border-emerald-300 hover:text-emerald-900'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[18px] text-emerald-700">bolt</span>
                        <span>Bulk Edit</span>
                        <span
                            v-if="selectedRows.size > 0"
                            class="bg-emerald-900 text-yellow-200 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none"
                        >
                            {{ selectedRows.size }}
                        </span>
                    </button>

                    <!-- Kembali ke Database Siswa -->
                    <Link
                        href="/students"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 bg-white font-semibold text-xs sm:text-sm hover:bg-slate-50 hover:border-slate-400 transition-all shadow-2xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        <span>Kembali</span>
                    </Link>

                    <!-- Simpan Perubahan -->
                    <button
                        type="button"
                        @click="saveChanges"
                        :disabled="isSaving"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm transition-all shadow-xs disabled:opacity-50 cursor-pointer active:scale-95 border border-emerald-950/30"
                    >
                        <span class="material-symbols-outlined text-[18px]">{{ isSaving ? 'hourglass_empty' : 'save' }}</span>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Flash & Error Banner -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="successMessage"
                    class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 text-sm text-emerald-900 shadow-2xs"
                >
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-emerald-700 text-[20px] shrink-0 mt-0.5">check_circle</span>
                        <div class="font-medium leading-relaxed">{{ successMessage }}</div>
                    </div>
                    <button type="button" @click="successMessage = null" class="text-emerald-700 hover:text-emerald-900 p-0.5 rounded-full">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </transition>

            <div
                v-if="saveError"
                class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl flex items-start justify-between gap-3 text-sm text-rose-800 shadow-2xs"
            >
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-rose-600 text-[20px] shrink-0 mt-0.5">error</span>
                    <div>
                        <strong class="font-bold">Gagal menyimpan data:</strong> {{ saveError }}
                    </div>
                </div>
                <button type="button" @click="saveError = null" class="text-rose-600 hover:text-rose-800 p-0.5 rounded-full">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>

            <!-- ======================================================
                 BULK EDIT PANEL (Style Web: Card Emerald, Capsule Toggle, Clean Dropdowns)
            ====================================================== -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showBulkPanel" class="mb-5 p-4 sm:p-5 bg-white border border-emerald-200/90 rounded-2xl shadow-sm ring-1 ring-emerald-500/10">
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-800 shrink-0">
                                <span class="material-symbols-outlined text-[20px]">bolt</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Bulk Edit Nilai Kolom Massal</h3>
                                <p class="text-xs text-slate-500">Terapkan satu nilai ke seluruh baris terpilih atau semua siswa sekaligus</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showBulkPanel = false"
                            class="w-7 h-7 rounded-full bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center cursor-pointer transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <!-- 1. Target Siswa -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Target Siswa</label>
                            <div class="bg-slate-100 p-1 rounded-xl inline-flex items-center gap-1 border border-slate-200/90 w-full">
                                <button
                                    type="button"
                                    @click="bulkMode = 'selected'"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-center',
                                        bulkMode === 'selected'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900 font-semibold'
                                    ]"
                                >
                                    Terpilih ({{ selectedRows.size }})
                                </button>
                                <button
                                    type="button"
                                    @click="bulkMode = 'all'"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-center',
                                        bulkMode === 'all'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900 font-semibold'
                                    ]"
                                >
                                    Semua ({{ students.length }})
                                </button>
                            </div>
                        </div>

                        <!-- 2. Pilih Kolom -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Pilih Kolom</label>
                            <select
                                v-model="bulkField"
                                @change="onBulkFieldChange"
                                class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all cursor-pointer"
                            >
                                <option value="">-- Pilih Kolom --</option>
                                <optgroup v-for="grp in visibleGroups" :key="grp.key" :label="grp.label">
                                    <option v-for="col in columns[grp.key].filter(c => !c.readonly)" :key="col.key" :value="col.key">
                                        {{ col.label }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- 3. Nilai Baru (Disesuaikan dengan data dropdown detail siswa) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Nilai Baru
                                <span v-if="bulkColumn" class="text-emerald-800 font-normal lowercase">({{ bulkColumn.label }})</span>
                            </label>

                            <div v-if="!bulkField" class="px-3 py-2 text-xs text-slate-400 bg-slate-50 border border-slate-200 rounded-xl">
                                Pilih kolom terlebih dahulu
                            </div>
                            <template v-else>
                                <!-- Select Type Dropdown (Unit Formal, Unit Asrama, Program, Jenjang, Status, JK, dll.) -->
                                <select
                                    v-if="bulkColumn?.type === 'select'"
                                    v-model="bulkValue"
                                    class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all cursor-pointer"
                                >
                                    <option value="">-- Pilih Nilai Baru --</option>
                                    <option
                                        v-for="(opt, idx) in bulkColumn.options"
                                        :key="idx"
                                        :value="getOptionValue(opt)"
                                    >
                                        {{ getOptionLabel(opt) }}
                                    </option>
                                </select>

                                <!-- Checkbox / Boolean Type (PIP, KIP) -->
                                <select
                                    v-else-if="bulkColumn?.type === 'checkbox'"
                                    v-model="bulkValue"
                                    class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all cursor-pointer"
                                >
                                    <option value="">-- Pilih --</option>
                                    <option :value="true">Ya</option>
                                    <option :value="false">Tidak</option>
                                </select>

                                <!-- Date Type -->
                                <input
                                    v-else-if="bulkColumn?.type === 'date'"
                                    v-model="bulkValue"
                                    type="date"
                                    class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all"
                                />

                                <!-- Number Type -->
                                <input
                                    v-else-if="bulkColumn?.type === 'number'"
                                    v-model="bulkValue"
                                    type="number"
                                    placeholder="Masukkan angka..."
                                    class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all"
                                />

                                <!-- Text Type Default -->
                                <input
                                    v-else
                                    v-model="bulkValue"
                                    type="text"
                                    :placeholder="`Masukkan nilai ${bulkColumn?.label}...`"
                                    class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all"
                                />
                            </template>
                        </div>

                        <!-- 4. Tombol Terapkan -->
                        <div>
                            <button
                                type="button"
                                @click="applyBulk"
                                :disabled="!bulkField || (bulkValue === '' && bulkColumn?.type !== 'select')"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 rounded-xl text-xs sm:text-sm font-bold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-all shadow-xs active:scale-95 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[17px]">bolt</span>
                                <span>Terapkan Massal</span>
                            </button>
                        </div>
                    </div>

                    <!-- Opsi Target Semester (Muncul saat mengubah Ket. Tidak Aktif atau Status Keaktifan) -->
                    <div v-if="bulkField === 'keterangan_tidak_aktif' || bulkField === 'status_keaktifan'" class="mt-4 pt-3.5 border-t border-emerald-100">
                        <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                            <label class="text-xs font-bold uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-emerald-700">event_repeat</span>
                                <span>Cakupan Tahun Ajaran & Semester Target</span>
                            </label>
                            <span class="text-[11px] text-slate-500 font-medium">Terapkan status siswa ini sekaligus ke banyak semester agar ringkas</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-3">
                            <label
                                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-bold"
                                :class="bulkSemesterScope === 'current' ? 'bg-emerald-50 border-emerald-400 text-emerald-950 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            >
                                <input type="radio" value="current" v-model="bulkSemesterScope" class="accent-emerald-700" />
                                <span>Semester Ini Saja</span>
                            </label>

                            <label
                                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-bold"
                                :class="bulkSemesterScope === 'future' ? 'bg-emerald-50 border-emerald-400 text-emerald-950 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            >
                                <input type="radio" value="future" v-model="bulkSemesterScope" class="accent-emerald-700" />
                                <span>Semester Ini & Setelahnya (Cascade)</span>
                            </label>

                            <label
                                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs font-bold"
                                :class="bulkSemesterScope === 'manual' ? 'bg-emerald-50 border-emerald-400 text-emerald-950 shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            >
                                <input type="radio" value="manual" v-model="bulkSemesterScope" class="accent-emerald-700" />
                                <span>Pilih Manual Banyak Semester</span>
                            </label>
                        </div>

                        <!-- Multi-Checklist Semester jika pilih Manual -->
                        <div v-if="bulkSemesterScope === 'manual'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                <span class="text-[11px] font-bold text-slate-600 uppercase">Centang Semester Target:</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="selectAllSemesters" class="text-[11px] font-bold text-emerald-800 hover:underline">Pilih Semua</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="clearAllSemesters" class="text-[11px] font-bold text-slate-500 hover:underline">Hapus Pilihan</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <label
                                    v-for="ay in allAcademicYears"
                                    :key="ay.id"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer transition-all"
                                    :class="bulkSelectedSemesters.includes(ay.id) ? 'bg-emerald-100 text-emerald-950 border-emerald-400' : 'bg-white text-slate-700 border-slate-300'"
                                >
                                    <input type="checkbox" :value="ay.id" v-model="bulkSelectedSemesters" class="accent-emerald-700 rounded" />
                                    <span>{{ ay.label }}</span>
                                    <span v-if="ay.is_current" class="text-[10px] font-bold text-emerald-800 bg-emerald-200/80 px-1 rounded">(Aktif)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ======================================================
                 TOOLBAR (Search + Selection + Group Legend)
            ====================================================== -->
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <!-- Pencarian -->
                <div class="relative w-72">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NISN, NIPD, angkatan..."
                        class="w-full pl-9 pr-8 py-2 text-xs border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 shadow-2xs transition-all"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded-full"
                    >
                        <span class="material-symbols-outlined text-[14px]">close</span>
                    </button>
                </div>

                <!-- Filter Status Keaktifan (Tabs: Semua, Aktif, Tidak Aktif) -->
                <div class="inline-flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/90 shadow-2xs">
                    <button
                        type="button"
                        @click="activeStatusFilter = 'semua'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            activeStatusFilter === 'semua'
                                ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60'
                                : 'text-slate-600 hover:text-slate-900 font-semibold'
                        ]"
                    >
                        Semua ({{ statusCounts.semua }})
                    </button>
                    <button
                        type="button"
                        @click="activeStatusFilter = 'aktif'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                            activeStatusFilter === 'aktif'
                                ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-semibold'
                        ]"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Aktif ({{ statusCounts.aktif }})
                    </button>
                    <button
                        type="button"
                        @click="activeStatusFilter = 'tidak_aktif'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                            activeStatusFilter === 'tidak_aktif'
                                ? 'bg-rose-900 text-rose-100 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-semibold'
                        ]"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                        Tidak Aktif ({{ statusCounts.tidakAktif }})
                    </button>
                    <button
                        type="button"
                        @click="activeStatusFilter = 'lulus'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                            activeStatusFilter === 'lulus'
                                ? 'bg-blue-900 text-blue-100 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-semibold'
                        ]"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                        Lulus ({{ statusCounts.lulus }})
                    </button>
                </div>

                <!-- Info Baris Terpilih -->
                <div v-if="selectedRows.size > 0" class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200/90 rounded-full text-xs font-semibold text-emerald-900">
                    <span><strong>{{ selectedRows.size }}</strong> siswa dipilih</span>
                    <button type="button" @click="selectedRows = new Set()" class="text-rose-600 hover:underline text-[11px] font-bold">Batalkan</button>
                </div>

                <!-- Grup Kolom Toggle (Kapsul Seragam Style Web Ulil Albab) -->
                <div class="flex flex-wrap gap-1.5 ml-auto">
                    <button
                        v-for="grp in visibleGroups"
                        :key="grp.key"
                        type="button"
                        @click="toggleGroup(grp.key)"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer shadow-2xs',
                            columnGroups.find(g => g.key === grp.key)?.expanded
                                ? 'bg-emerald-900 text-yellow-200 border-emerald-950/30'
                                : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 hover:text-slate-900'
                        ]"
                        :title="`Klik untuk ${columnGroups.find(g => g.key === grp.key)?.expanded ? 'sembunyikan' : 'tampilkan'} kolom ${grp.label}`"
                    >
                        <span class="material-symbols-outlined text-[15px]">{{ grp.icon }}</span>
                        <span>{{ grp.label }}</span>
                    </button>
                </div>
            </div>

            <!-- ======================================================
                 TABLE DATA (Thead Dark Forest Green #004b23 & Crisp Subheaders)
            ====================================================== -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-auto" style="max-height: calc(100vh - 290px);">
                    <table class="text-left border-collapse min-w-max w-full">
                        <!-- ============ THEAD ROW 1: Group Headers ============ -->
                        <thead class="sticky top-0 z-30">
                            <tr class="bg-[#004b23] text-white">
                                <!-- Sticky Checkbox -->
                                <th class="p-0 sticky left-0 z-40 bg-[#004b23] border-r border-emerald-800" rowspan="2">
                                    <div class="flex items-center justify-center w-10 h-full p-2">
                                        <input
                                            type="checkbox"
                                            :checked="allSelected"
                                            :indeterminate="someSelected"
                                            @change="toggleSelectAll"
                                            class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                        />
                                    </div>
                                </th>
                                <th class="p-2 text-[11px] font-bold text-white bg-[#004b23] sticky left-10 z-40 w-10 text-center border-r border-emerald-800" rowspan="2">#</th>
                                <th class="p-2 text-[11px] font-bold text-white uppercase bg-[#004b23] sticky left-[80px] z-40 w-32 border-r border-emerald-800" rowspan="2">NISN</th>

                                <!-- Dynamic Group Headers -->
                                <th
                                    v-for="grp in visibleGroups"
                                    :key="grp.key"
                                    :colspan="groupSpan(grp.key)"
                                    class="text-center text-[11px] font-bold text-white px-3 py-2 border-r border-emerald-800/80 cursor-pointer select-none bg-[#004b23] hover:bg-[#00381a] transition-colors"
                                    @click="toggleGroup(grp.key)"
                                >
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-emerald-200">{{ grp.icon }}</span>
                                        <span>{{ grp.label }}</span>
                                        <span class="material-symbols-outlined text-[14px] text-emerald-300/80">
                                            {{ columnGroups.find(g => g.key === grp.key)?.expanded ? 'expand_less' : 'expand_more' }}
                                        </span>
                                    </div>
                                </th>
                            </tr>

                            <!-- ============ THEAD ROW 2: Column Sub-headers ============ -->
                            <tr class="bg-[#00381a] text-white">
                                <template v-for="grp in visibleGroups" :key="grp.key">
                                    <template v-if="columnGroups.find(g => g.key === grp.key)?.expanded">
                                        <th
                                            v-for="col in columns[grp.key]"
                                            :key="col.key"
                                            :class="['text-left text-[10px] font-bold text-emerald-100 uppercase tracking-wider px-2 py-1.5 border-r border-emerald-900 whitespace-nowrap', col.width]"
                                        >
                                            {{ col.label }}
                                        </th>
                                    </template>
                                    <th v-else class="text-[10px] font-semibold text-emerald-200/80 text-center px-2 py-1.5 border-r border-emerald-900">
                                        <span class="material-symbols-outlined text-[13px]">more_horiz</span>
                                    </th>
                                </template>
                            </tr>
                        </thead>

                        <!-- ============ TBODY ============ -->
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(student, index) in filteredStudents"
                                :key="student.nisn"
                                :class="[
                                    'transition-colors group',
                                    selectedRows.has(student.nisn) ? 'bg-emerald-50/70 hover:bg-emerald-100/60' : 'hover:bg-slate-50'
                                ]"
                            >
                                <!-- Checkbox -->
                                <td class="p-0 sticky left-0 z-20 border-r border-slate-200" :class="selectedRows.has(student.nisn) ? 'bg-emerald-50' : 'bg-white group-hover:bg-slate-50'">
                                    <div class="flex items-center justify-center w-10 p-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedRows.has(student.nisn)"
                                            @change="toggleRow(student.nisn)"
                                            class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                        />
                                    </div>
                                </td>

                                <!-- No -->
                                <td class="px-2 py-1.5 text-[11px] text-slate-500 font-medium text-center sticky left-10 z-20 border-r border-slate-100" :class="selectedRows.has(student.nisn) ? 'bg-emerald-50' : 'bg-white group-hover:bg-slate-50'">
                                    {{ index + 1 }}
                                </td>

                                <!-- NISN (readonly) -->
                                <td class="px-2 py-1.5 text-[11px] text-slate-700 font-mono font-semibold sticky left-[80px] z-20 border-r border-slate-200" :class="selectedRows.has(student.nisn) ? 'bg-emerald-50' : 'bg-white group-hover:bg-slate-50'">
                                    {{ student.nisn }}
                                </td>

                                <!-- Dynamic Columns -->
                                <template v-for="grp in visibleGroups" :key="grp.key">
                                    <template v-if="columnGroups.find(g => g.key === grp.key)?.expanded">
                                        <td
                                            v-for="col in columns[grp.key]"
                                            :key="col.key"
                                            class="p-1 border-r border-slate-100"
                                        >
                                            <!-- Checkbox type -->
                                            <div v-if="col.type === 'checkbox'" class="flex items-center justify-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="student[col.key]"
                                                    class="w-4 h-4 rounded accent-emerald-700 cursor-pointer"
                                                />
                                            </div>

                                            <!-- Badge / Readonly type (e.g. Generate Sistem Status Semester) -->
                                            <div v-else-if="col.type === 'badge' || col.readonly" class="flex items-center justify-center px-1 py-1">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-tight shadow-2xs select-none border',
                                                        student[col.key] === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                        student[col.key] === 'Tidak Aktif-Sebagian' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                        student[col.key] === 'Tidak Aktif' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                        student[col.key] === 'Lulus' ? 'bg-sky-50 text-sky-700 border-sky-200' :
                                                        'bg-slate-50 text-slate-600 border-slate-200'
                                                    ]"
                                                    :title="'Digenerate otomatis oleh sistem (' + (student[col.key] || 'Aktif') + ')'"
                                                >
                                                    <span
                                                        :class="[
                                                            'w-1.5 h-1.5 rounded-full',
                                                            student[col.key] === 'Aktif' ? 'bg-emerald-500' :
                                                            student[col.key] === 'Tidak Aktif-Sebagian' ? 'bg-amber-500' :
                                                            student[col.key] === 'Tidak Aktif' ? 'bg-rose-500' :
                                                            student[col.key] === 'Lulus' ? 'bg-sky-500' :
                                                            'bg-slate-400'
                                                        ]"
                                                    ></span>
                                                    {{ student[col.key] || 'Aktif' }}
                                                    <svg class="w-3 h-3 text-slate-400 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </span>
                                            </div>

                                            <!-- Select type (Unit Formal, Unit Asrama, Program, Jenjang, Status, dll.) -->
                                            <select
                                                v-else-if="col.type === 'select'"
                                                v-model="student[col.key]"
                                                :class="['w-full px-2 py-1 text-[11.5px] border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none bg-white transition-colors cursor-pointer', col.width]"
                                            >
                                                <option value="">-</option>
                                                <option
                                                    v-for="(opt, optIdx) in col.options"
                                                    :key="optIdx"
                                                    :value="getOptionValue(opt)"
                                                >
                                                    {{ getOptionLabel(opt) }}
                                                </option>
                                                <!-- Jika ada nilai lama yg belum ada di daftar opsi -->
                                                <option
                                                    v-if="student[col.key] && !hasOption(col.options, student[col.key])"
                                                    :value="student[col.key]"
                                                >
                                                    {{ student[col.key] }}
                                                </option>
                                            </select>

                                            <!-- Date type -->
                                            <input
                                                v-else-if="col.type === 'date'"
                                                type="date"
                                                v-model="student[col.key]"
                                                :class="['w-full px-2 py-1 text-[11.5px] border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none transition-colors bg-white', col.width]"
                                            />

                                            <!-- Number type -->
                                            <input
                                                v-else-if="col.type === 'number'"
                                                type="number"
                                                v-model="student[col.key]"
                                                :class="['w-full px-2 py-1 text-[11.5px] border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none transition-colors bg-white', col.width]"
                                            />

                                            <!-- Text default -->
                                            <input
                                                v-else
                                                type="text"
                                                v-model="student[col.key]"
                                                :class="['w-full px-2 py-1 text-[11.5px] border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none transition-colors bg-white', col.width]"
                                            />
                                        </td>
                                    </template>
                                    <!-- Collapsed Group placeholder -->
                                    <td v-else class="p-2 border-r border-slate-100 text-center">
                                        <span class="text-[10px] text-slate-300">—</span>
                                    </td>
                                </template>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredStudents.length === 0">
                                <td :colspan="3 + visibleGroups.reduce((sum, g) => sum + groupSpan(g.key), 0)" class="py-16 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <span class="material-symbols-outlined text-2xl">search_off</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">Tidak ada data yang sesuai</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Tabel -->
                <div class="px-4 py-3 border-t border-slate-200 flex items-center justify-between bg-slate-50/70">
                    <span class="text-xs text-slate-500 font-medium">
                        Menampilkan <strong class="text-slate-800 font-bold">{{ filteredStudents.length }}</strong> dari <strong class="text-slate-800 font-bold">{{ students.length }}</strong> siswa
                        <span v-if="selectedRows.size > 0"> · <strong class="text-emerald-800 font-bold">{{ selectedRows.size }} dipilih</strong></span>
                    </span>
                    <button
                        type="button"
                        @click="saveChanges"
                        :disabled="isSaving"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 rounded-xl text-xs font-bold disabled:opacity-50 cursor-pointer transition-all shadow-xs active:scale-95 border border-emerald-950/30"
                    >
                        <span class="material-symbols-outlined text-[16px]">{{ isSaving ? 'hourglass_empty' : 'save' }}</span>
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
