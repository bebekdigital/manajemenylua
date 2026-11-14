<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue';
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

// 8. Pilihan Status Keaktifan Master (Data Statis di students)
const statusKeaktifanOptions = [
    { value: 'Aktif', label: 'Aktif' },
    { value: 'Tidak Aktif', label: 'Tidak Aktif' },
    { value: 'Lulus', label: 'Lulus' },
];

// 9. Pilihan Keterangan Tidak Aktif
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
    'Brosur / Pamflet', 'Media Sosial', 'Website', 'Spanduk / Baliho',
    'Teman / Kerabat', 'Alumni', 'Pameran Pendidikan', 'Lainnya'
];
const pendidikanOptions = [
    'Tidak Sekolah', 'SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat',
    'D1 / D2 / D3', 'D4 / S1', 'S2', 'S3'
];
const statusOrtuOptions = ['Hidup', 'Meninggal'];
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

// ============================================================
// Column Groups (Diselaraskan persis dengan Tab Detail Siswa)
// ============================================================
// Data Statis: Identitas, Alamat, Keluarga (Ayah/Ibu/Wali), Registrasi, Kesejahteraan
// Data Dinamis: Riwayat Akademik (per semester/tahun ajaran)
const columnGroups = reactive([
    { key: 'identitas', label: 'Identitas', icon: 'person', expanded: true },
    { key: 'alamat', label: 'Alamat', icon: 'home', expanded: false },
    { key: 'keluarga_ayah', label: 'Ayah', icon: 'man', expanded: false },
    { key: 'keluarga_ibu', label: 'Ibu', icon: 'woman', expanded: false },
    { key: 'keluarga_wali', label: 'Wali', icon: 'supervisor_account', expanded: false },
    { key: 'registrasi', label: 'Registrasi', icon: 'how_to_reg', expanded: false },
    { key: 'bantuan', label: 'Kesejahteraan', icon: 'volunteer_activism', expanded: false },
    { key: 'akademik', label: 'Riwayat Akademik', icon: 'history_edu', expanded: true },
]);

// Catatan: Kolom Nama Siswa dipin/freeze di bagian paling kiri (sticky),
// sehingga kolom identitas di scrollable table dimulai dari NISN.
const columns = computed(() => ({
    identitas: [
        { key: 'nisn', label: 'NISN', type: 'text', width: 'w-32' },
        { key: 'nipd', label: 'NIPD', type: 'text', width: 'w-28' },
        { key: 'angkatan', label: 'Angkatan', type: 'select', options: angkatanOptions.value, width: 'w-28' },
        { key: 'nik', label: 'NIK', type: 'text', width: 'w-40' },
        { key: 'no_kk', label: 'No KK', type: 'text', width: 'w-40' },
        { key: 'jk', label: 'JK', type: 'select', options: jkOptions, width: 'w-24' },
        { key: 'tempat_lahir', label: 'Tempat Lahir', type: 'text', width: 'w-36' },
        { key: 'tanggal_lahir', label: 'Tgl Lahir', type: 'date', width: 'w-36' },
        { key: 'agama', label: 'Agama', type: 'select', options: agamaOptions, width: 'w-28' },
        { key: 'no_wa', label: 'No WA', type: 'text', width: 'w-32' },
        { key: 'no_wa_2', label: 'No WA 2', type: 'text', width: 'w-32' },
        { key: 'email', label: 'Email', type: 'text', width: 'w-40' },
        { key: 'unit', label: 'Unit Formal', type: 'select', options: formalUnitOptions.value, width: 'w-48' },
        { key: 'program', label: 'Program', type: 'select', options: programOptions.value, width: 'w-36' },
        {
            key: 'unit_pesantren',
            label: 'Unit Asrama',
            type: 'select',
            options: [
                { value: '', label: '-- Tidak Berasrama --' },
                ...pesantrenUnitOptions.value.map(p => ({ value: p, label: p }))
            ],
            width: 'w-48'
        },
        { key: 'jenjang', label: 'Jenjang', type: 'select', options: levelOptions.value, width: 'w-28' },
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
        { key: 'ayah_nik', label: 'NIK Ayah', type: 'text', width: 'w-36' },
        { key: 'ayah_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ayah_pendidikan', label: 'Pendidikan', type: 'select', options: pendidikanOptions, width: 'w-36' },
        { key: 'ayah_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'ayah_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
        { key: 'ayah_status', label: 'Status', type: 'select', options: statusOrtuOptions, width: 'w-28' },
    ],
    keluarga_ibu: [
        { key: 'ibu_nama', label: 'Nama Ibu', type: 'text', width: 'w-40' },
        { key: 'ibu_nik', label: 'NIK Ibu', type: 'text', width: 'w-36' },
        { key: 'ibu_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ibu_pendidikan', label: 'Pendidikan', type: 'select', options: pendidikanOptions, width: 'w-36' },
        { key: 'ibu_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'ibu_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
        { key: 'ibu_status', label: 'Status', type: 'select', options: statusOrtuOptions, width: 'w-28' },
    ],
    keluarga_wali: [
        { key: 'wali_nama', label: 'Nama Wali', type: 'text', width: 'w-40' },
        { key: 'wali_nik', label: 'NIK Wali', type: 'text', width: 'w-36' },
        { key: 'wali_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'wali_pendidikan', label: 'Pendidikan', type: 'select', options: pendidikanOptions, width: 'w-36' },
        { key: 'wali_hubungan', label: 'Hubungan', type: 'text', width: 'w-32' },
        { key: 'wali_pekerjaan', label: 'Pekerjaan', type: 'select', options: pekerjaanOptions, width: 'w-36' },
        { key: 'wali_penghasilan', label: 'Penghasilan', type: 'select', options: penghasilanOptions, width: 'w-44' },
    ],
    registrasi: [
        { key: 'status_registrasi', label: 'Status Registrasi', type: 'select', options: ['Sudah Lengkap', 'Belum Lengkap'], width: 'w-36' },
        { key: 'status_keaktifan', label: 'Status Keaktifan', type: 'select', options: statusKeaktifanOptions, width: 'w-40' },
        { key: 'keterangan_tidak_aktif', label: 'Ket. Tidak Aktif', type: 'select', options: ketTidakAktifOptions, width: 'w-44' },
        { key: 'tanggal_tidak_aktif', label: 'Tgl Tidak Aktif', type: 'date', width: 'w-36' },
        { key: 'sekolah_asal', label: 'Sekolah Asal', type: 'text', width: 'w-40' },
        { key: 'npsn', label: 'NPSN', type: 'text', width: 'w-32' },
        { key: 'status_keluarga', label: 'Status Keluarga', type: 'select', options: statusKeluargaOptions, width: 'w-36' },
        { key: 'anak_ke', label: 'Anak Ke', type: 'number', width: 'w-24' },
        { key: 'diterima_di_jenjang', label: 'Diterima di Jenjang', type: 'select', options: levelOptions.value, width: 'w-40' },
        { key: 'tanggal_diterima', label: 'Tgl Diterima', type: 'date', width: 'w-36' },
        { key: 'info_psb', label: 'Sumber Info PSB', type: 'select', options: infoPsbOptions, width: 'w-44' },
    ],
    bantuan: [
        { key: 'desil', label: 'Desil', type: 'number', width: 'w-20' },
        { key: 'status_pip', label: 'PIP', type: 'checkbox', width: 'w-20' },
        { key: 'pip_keterangan', label: 'Keterangan PIP', type: 'text', width: 'w-44' },
        { key: 'status_kip', label: 'KIP', type: 'checkbox', width: 'w-20' },
        { key: 'no_kip', label: 'No KIP', type: 'text', width: 'w-36' },
    ],
    akademik: [
        { key: 'unit', label: 'Unit Formal', type: 'select', options: formalUnitOptions.value, width: 'w-48' },
        { key: 'program', label: 'Program Pendidikan', type: 'select', options: programOptions.value, width: 'w-36' },
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
        { key: 'jenjang', label: 'Jenjang', type: 'select', options: levelOptions.value, width: 'w-28' },
        { key: 'tingkat', label: 'Tingkat', type: 'number', width: 'w-20' },
        { key: 'kelas', label: 'Kelas', type: 'select', options: classroomOptions.value, width: 'w-28' },
        { key: 'student_status', label: 'Status Semester (Sistem)', type: 'badge', readonly: true, width: 'w-44' },
        { key: 'ket_tidak_aktif', label: 'Ket. Tidak Aktif', type: 'select', options: ketTidakAktifOptions, width: 'w-44' },
        { key: 'tanggal_tidak_aktif', label: 'Tgl Tidak Aktif', type: 'date', width: 'w-36' },
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
// Search & Multi-Select Dropdown Filters
// ============================================================
const searchQuery = ref('');
const selectedUnits = ref([]); // array of unit strings
const selectedTingkat = ref([]); // array of numbers
const selectedStatus = ref([]); // array of status strings ('Aktif', 'Tidak Aktif', 'Lulus')
const selectedRows = ref(new Set());

// State active dropdown popover: 'status' | 'unit' | 'tingkat' | null
const activeDropdown = ref(null);

function toggleDropdown(name) {
    activeDropdown.value = activeDropdown.value === name ? null : name;
}

function closeDropdown() {
    activeDropdown.value = null;
}

// Global click outside listener to close popover
function onDocClick(e) {
    if (activeDropdown.value && !e.target.closest('.filter-popover-container')) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocClick);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocClick);
});

// Pilihan Tingkat yang ada di data siswa
const availableTingkatOptions = computed(() => {
    const set = new Set();
    students.forEach(s => {
        if (s.tingkat !== null && s.tingkat !== undefined && s.tingkat !== '') {
            const num = Number(s.tingkat);
            if (!isNaN(num)) set.add(num);
        }
    });
    if (set.size === 0) {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    }
    return Array.from(set).sort((a, b) => a - b);
});

// Helper labels for multi-select dropdown pills
const statusFilterLabel = computed(() => {
    if (selectedStatus.value.length === 0) return 'Status: Semua';
    if (selectedStatus.value.length === 1) return `Status: ${selectedStatus.value[0]}`;
    return `Status (${selectedStatus.value.length})`;
});

const unitFilterLabel = computed(() => {
    if (selectedUnits.value.length === 0) return 'Unit: Semua';
    if (selectedUnits.value.length === 1) {
        const u = selectedUnits.value[0];
        if (u.includes('SMP')) return 'Unit: SMPIT';
        if (u.includes('SD')) return 'Unit: SDIT';
        if (u.includes('SMA')) return 'Unit: SMAIT';
        if (u.includes('PPTQ')) return 'Unit: PPTQ';
        return `Unit: ${u}`;
    }
    return `Unit (${selectedUnits.value.length})`;
});

const tingkatFilterLabel = computed(() => {
    if (selectedTingkat.value.length === 0) return 'Tingkat: Semua';
    if (selectedTingkat.value.length === 1) return `Tingkat ${selectedTingkat.value[0]}`;
    if (selectedTingkat.value.length <= 2) return `Tk. ${selectedTingkat.value.join(', ')}`;
    return `Tingkat (${selectedTingkat.value.length})`;
});

function toggleItemInArray(arrRef, item) {
    const idx = arrRef.value.indexOf(item);
    if (idx === -1) {
        arrRef.value.push(item);
    } else {
        arrRef.value.splice(idx, 1);
    }
}

function getCountStatus(st) {
    return students.filter(s => {
        const masterStatus = (s.status_keaktifan || 'Aktif').toLowerCase().trim();
        const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
        if (st === 'Lulus') return masterStatus === 'lulus' || ket === 'lulus';
        if (st === 'Tidak Aktif') return masterStatus === 'tidak aktif' || masterStatus.includes('tidak') || (ket && ket !== 'lulus');
        return masterStatus !== 'lulus' && masterStatus !== 'tidak aktif' && !masterStatus.includes('tidak') && (!ket || ket === 'lulus');
    }).length;
}

function getCountUnit(unitName) {
    const target = unitName.toLowerCase();
    return students.filter(s => {
        const u = (s.unit || '').toLowerCase();
        const up = (s.unit_pesantren || '').toLowerCase();
        return u.includes(target) || up.includes(target);
    }).length;
}

function getCountTingkat(tk) {
    return students.filter(s => Number(s.tingkat) === Number(tk)).length;
}

const isFilterActive = computed(() => {
    return selectedUnits.value.length > 0 ||
           selectedTingkat.value.length > 0 ||
           selectedStatus.value.length > 0 ||
           Boolean(searchQuery.value && searchQuery.value.trim());
});

const filterSummaryText = computed(() => {
    const parts = [];
    if (selectedUnits.value.length > 0) parts.push(`Unit: ${selectedUnits.value.join(', ')}`);
    if (selectedTingkat.value.length > 0) parts.push(`Tingkat: ${selectedTingkat.value.join(', ')}`);
    if (selectedStatus.value.length > 0) parts.push(`Status: ${selectedStatus.value.join(', ')}`);
    if (searchQuery.value && searchQuery.value.trim()) parts.push(`"${searchQuery.value.trim()}"`);
    return parts.join(' • ') || 'Semua Data';
});

function resetAllFilters() {
    selectedStatus.value = [];
    selectedUnits.value = [];
    selectedTingkat.value = [];
    searchQuery.value = '';
}

// Filtered student list
const filteredStudents = computed(() => {
    let list = students;

    // 1. Filter Status Keaktifan (Multi-select)
    if (selectedStatus.value.length > 0) {
        list = list.filter(s => {
            const masterStatus = (s.status_keaktifan || 'Aktif').toLowerCase().trim();
            const ket = (s.keterangan_tidak_aktif || s.ket_tidak_aktif || '').toLowerCase().trim();
            let currentVal = 'Aktif';
            if (masterStatus === 'lulus' || ket === 'lulus') {
                currentVal = 'Lulus';
            } else if (masterStatus === 'tidak aktif' || masterStatus.includes('tidak') || (ket && ket !== 'lulus')) {
                currentVal = 'Tidak Aktif';
            }
            return selectedStatus.value.includes(currentVal);
        });
    }

    // 2. Filter Unit (Multi-select)
    if (selectedUnits.value.length > 0) {
        list = list.filter(s => {
            const u = (s.unit || '').toLowerCase();
            const up = (s.unit_pesantren || '').toLowerCase();
            return selectedUnits.value.some(target => {
                const t = target.toLowerCase();
                return u.includes(t) || up.includes(t);
            });
        });
    }

    // 3. Filter Tingkat (Multi-select)
    if (selectedTingkat.value.length > 0) {
        list = list.filter(s => {
            if (s.tingkat === null || s.tingkat === undefined || s.tingkat === '') return false;
            return selectedTingkat.value.includes(Number(s.tingkat));
        });
    }

    // 4. Search Query
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

// ============================================================
// Bulk Edit (Target Siswa Dinamis)
// ============================================================
const showBulkPanel = ref(false);
const bulkField = ref('');
const bulkValue = ref('');
const bulkTarget = ref('selected'); // 'selected' | 'filtered'
const bulkColumn = computed(() => allColumns.value.find(c => c.key === bulkField.value));

// Multi-Semester Target Options
const bulkSemesterScope = ref('current');
const bulkSelectedSemesters = ref(props.selectedAcademicYear ? [props.selectedAcademicYear.id] : []);
const multiSemesterStudentNisns = ref(new Set());
const activeTargetYearIds = ref(new Set());

const futureAcademicYearIds = computed(() => {
    if (!props.selectedAcademicYear || !props.allAcademicYears || props.allAcademicYears.length === 0) {
        return props.selectedAcademicYear ? [props.selectedAcademicYear.id] : [];
    }
    const sorted = [...props.allAcademicYears].sort((a, b) => (a.start_date || '').localeCompare(b.start_date || ''));
    const currIdx = sorted.findIndex(ay => ay.id === props.selectedAcademicYear.id);
    if (currIdx === -1) return [props.selectedAcademicYear.id];
    return sorted.slice(currIdx).map(ay => ay.id);
});

// List of names of selected students
const selectedStudentNames = computed(() => {
    return students
        .filter(s => selectedRows.value.has(s.nisn))
        .map(s => s.nama);
});

// Target button labels for Bulk Edit
const targetSelectedLabel = computed(() => {
    const count = selectedRows.value.size;
    if (count === 0) return 'Belum ada siswa dipilih';
    if (count <= 3) return selectedStudentNames.value.join(', ');
    return `${count} Siswa Terpilih`;
});

const targetFilteredLabel = computed(() => {
    if (isFilterActive.value) {
        return `Hasil Filter (${filteredStudents.value.length} Siswa)`;
    }
    return `Seluruh Siswa (${students.length} Siswa)`;
});

function onBulkFieldChange() {
    bulkValue.value = '';
    if ((bulkField.value === 'keterangan_tidak_aktif' || bulkField.value === 'status_keaktifan') && props.selectedAcademicYear) {
        bulkSelectedSemesters.value = [props.selectedAcademicYear.id];
    }
}

function applyBulk() {
    if (!bulkField.value) return;

    const targets = bulkTarget.value === 'filtered'
        ? filteredStudents.value
        : students.filter(s => selectedRows.value.has(s.nisn));

    if (targets.length === 0) {
        saveError.value = bulkTarget.value === 'selected'
            ? 'Tidak ada siswa yang dipilih di tabel. Silakan centang baris siswa terlebih dahulu atau gunakan target "Hasil Filter".'
            : 'Tidak ada data siswa pada filter saat ini.';
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
        // Auto-sync unit_pesantren jika kolom program diubah
        if (bulkField.value === 'program') {
            const prog = (val || '').toLowerCase();
            if (prog.includes('boarding') || prog.includes('pptq') || prog.includes('pesantren')) {
                s.unit_pesantren = pesantrenUnitOptions.value[0] || 'PPTQ Ulil Albab';
            } else {
                s.unit_pesantren = '';
            }
        }
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

    const targetDesc = bulkTarget.value === 'selected' && targets.length <= 3
        ? selectedStudentNames.value.join(', ')
        : `${targets.length} siswa`;

    successMessage.value = `Berhasil menerapkan nilai "${displayVal}" pada ${targetDesc}${scopeMsg} untuk kolom ${bulkColumn.value?.label}. Jangan lupa klik "Simpan Perubahan".`;
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

function onCellSelectChange(student, key) {
    if (key === 'program') {
        const prog = (student.program || '').toLowerCase();
        if (prog.includes('boarding') || prog.includes('pptq') || prog.includes('pesantren')) {
            student.unit_pesantren = pesantrenUnitOptions.value[0] || 'PPTQ Ulil Albab';
        } else {
            student.unit_pesantren = '';
        }
    }
}

function toggleGroup(groupKey) {
    const g = columnGroups.find(g => g.key === groupKey);
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
                    <!-- Tombol Kembali (Di atas judul, kecil dan ringkas) -->
                    <div class="mb-2">
                        <Link
                            href="/students"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border border-slate-300 text-slate-600 bg-white font-semibold text-xs hover:bg-slate-50 hover:text-slate-900 transition-all shadow-2xs"
                        >
                            <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                            <span>Kembali ke Database Siswa</span>
                        </Link>
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
                                <span>Data Statis (Master Induk)</span>
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
                                <span>Data Dinamis (Riwayat Semester)</span>
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
                 PANEL BULK EDIT (Dynamic Target Siswa)
            ====================================================== -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-3 scale-99"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-3 scale-99"
            >
                <div
                    v-if="showBulkPanel"
                    class="mb-5 p-4 sm:p-5 bg-gradient-to-br from-emerald-50/90 via-slate-50 to-white border-2 border-emerald-300 rounded-2xl shadow-md"
                >
                    <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-emerald-200/80">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-800 text-[22px]">bolt</span>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Bulk Edit Nilai Kolom Massal</h3>
                                <p class="text-xs text-slate-500">Terapkan satu nilai ke seluruh baris terpilih atau hasil filter sekaligus</p>
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
                        <!-- 1. Target Siswa (Dinamis: Nama <= 3 atau Jumlah Siswa / Hasil Filter) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Target Siswa</label>
                            <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 border border-slate-200/90 w-full mb-1">
                                <button
                                    type="button"
                                    @click="bulkTarget = 'selected'"
                                    :class="[
                                        'flex-1 py-1.5 px-2 rounded-lg text-xs font-bold transition-all cursor-pointer text-center truncate',
                                        bulkTarget === 'selected'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900 font-semibold'
                                    ]"
                                    :title="targetSelectedLabel"
                                >
                                    {{ targetSelectedLabel }}
                                </button>
                                <button
                                    type="button"
                                    @click="bulkTarget = 'filtered'"
                                    :class="[
                                        'flex-1 py-1.5 px-2 rounded-lg text-xs font-bold transition-all cursor-pointer text-center truncate',
                                        bulkTarget === 'filtered'
                                            ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                            : 'text-slate-600 hover:text-slate-900 font-semibold'
                                    ]"
                                    :title="targetFilteredLabel"
                                >
                                    {{ targetFilteredLabel }}
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium truncate">
                                <span v-if="bulkTarget === 'selected'">
                                    {{ selectedRows.size === 0 ? 'Pilih baris di tabel dengan mencentang' : (selectedRows.size <= 3 ? selectedStudentNames.join(', ') : selectedRows.size + ' siswa terpilih') }}
                                </span>
                                <span v-else>
                                    {{ isFilterActive ? filterSummaryText : 'Seluruh data siswa (' + students.length + ' siswa)' }}
                                </span>
                            </p>
                        </div>

                        <!-- 2. Pilih Kolom -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Pilih Kolom</label>
                            <select
                                v-model="bulkField"
                                @change="onBulkFieldChange"
                                class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all cursor-pointer font-semibold"
                            >
                                <option value="">-- Pilih Kolom --</option>
                                <optgroup v-for="grp in visibleGroups" :key="grp.key" :label="grp.label">
                                    <option v-for="col in columns[grp.key].filter(c => !c.readonly)" :key="col.key" :value="col.key">
                                        {{ col.label }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- 3. Nilai Baru -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Nilai Baru</label>

                            <!-- Select Type Dropdown -->
                            <select
                                v-if="bulkColumn?.type === 'select'"
                                v-model="bulkValue"
                                class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all cursor-pointer font-semibold"
                            >
                                <option value="">-- Pilih Nilai --</option>
                                <option
                                    v-for="opt in bulkColumn.options"
                                    :key="typeof opt === 'object' ? opt.value : opt"
                                    :value="typeof opt === 'object' ? opt.value : opt"
                                >
                                    {{ typeof opt === 'object' ? opt.label : opt }}
                                </option>
                            </select>

                            <!-- Date Type -->
                            <input
                                v-else-if="bulkColumn?.type === 'date'"
                                v-model="bulkValue"
                                type="date"
                                class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all"
                            />

                            <!-- Checkbox Type -->
                            <div v-else-if="bulkColumn?.type === 'checkbox'" class="flex items-center gap-2 h-[38px] px-3 bg-white border border-slate-300 rounded-xl">
                                <input
                                    v-model="bulkValue"
                                    type="checkbox"
                                    class="w-4 h-4 rounded accent-emerald-700 cursor-pointer"
                                />
                                <span class="text-xs font-semibold text-slate-700">Centang (Aktif / Ya)</span>
                            </div>

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
                                placeholder="Masukkan nilai baru..."
                                class="w-full px-3 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:border-emerald-700 shadow-2xs transition-all"
                            />
                        </div>

                        <!-- 4. Tombol Terapkan -->
                        <div>
                            <button
                                type="button"
                                @click="applyBulk"
                                :disabled="!bulkField"
                                class="w-full px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm transition-all shadow-2xs disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-[18px]">check</span>
                                <span>Terapkan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ======================================================
                 TOOLBAR (Search + Multi-Select Filter + Group Legend)
            ====================================================== -->
            <div class="mb-4 flex flex-wrap items-center gap-2.5 filter-popover-container relative">
                <!-- Pencarian -->
                <div class="relative w-64 sm:w-72">
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

                <!-- 1. Dropdown Filter Unit (Multi-Select) -->
                <div class="relative">
                    <button
                        type="button"
                        @click.stop="toggleDropdown('unit')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold border rounded-xl bg-white shadow-2xs transition-all cursor-pointer"
                        :class="selectedUnits.length > 0 ? 'border-emerald-600 text-emerald-950 bg-emerald-50/70' : 'border-slate-300 text-slate-700 hover:border-slate-400'"
                    >
                        <span class="material-symbols-outlined text-[16px] text-emerald-700">apartment</span>
                        <span>{{ unitFilterLabel }}</span>
                        <span class="material-symbols-outlined text-[16px] text-slate-400">expand_more</span>
                    </button>

                    <div
                        v-if="activeDropdown === 'unit'"
                        class="absolute left-0 top-full mt-1.5 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-2 space-y-1"
                        @click.stop
                    >
                        <div class="flex items-center justify-between px-2 py-1 border-b border-slate-100 text-[11px] font-bold text-slate-500">
                            <span>Filter Unit</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectedUnits = [...props.allUnits]" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">Semua</button>
                                <span class="text-slate-300">•</span>
                                <button type="button" @click="selectedUnits = []" class="text-rose-600 hover:text-rose-800 cursor-pointer">Reset</button>
                            </div>
                        </div>
                        <div class="max-h-60 overflow-y-auto space-y-0.5 py-1">
                            <label
                                v-for="u in props.allUnits"
                                :key="u"
                                class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg hover:bg-emerald-50/70 text-slate-800 text-xs font-semibold cursor-pointer transition-colors"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <input
                                        type="checkbox"
                                        :checked="selectedUnits.includes(u)"
                                        @change="toggleItemInArray(selectedUnits, u)"
                                        class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                    />
                                    <span class="truncate">{{ u }}</span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal shrink-0">({{ getCountUnit(u) }})</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 2. Dropdown Filter Tingkat (Multi-Select) -->
                <div class="relative">
                    <button
                        type="button"
                        @click.stop="toggleDropdown('tingkat')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold border rounded-xl bg-white shadow-2xs transition-all cursor-pointer"
                        :class="selectedTingkat.length > 0 ? 'border-emerald-600 text-emerald-950 bg-emerald-50/70' : 'border-slate-300 text-slate-700 hover:border-slate-400'"
                    >
                        <span class="material-symbols-outlined text-[16px] text-emerald-700">layers</span>
                        <span>{{ tingkatFilterLabel }}</span>
                        <span class="material-symbols-outlined text-[16px] text-slate-400">expand_more</span>
                    </button>

                    <div
                        v-if="activeDropdown === 'tingkat'"
                        class="absolute left-0 top-full mt-1.5 w-52 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-2 space-y-1"
                        @click.stop
                    >
                        <div class="flex items-center justify-between px-2 py-1 border-b border-slate-100 text-[11px] font-bold text-slate-500">
                            <span>Filter Tingkat</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectedTingkat = [...availableTingkatOptions]" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">Semua</button>
                                <span class="text-slate-300">•</span>
                                <button type="button" @click="selectedTingkat = []" class="text-rose-600 hover:text-rose-800 cursor-pointer">Reset</button>
                            </div>
                        </div>
                        <div class="max-h-60 overflow-y-auto space-y-0.5 py-1">
                            <label
                                v-for="tk in availableTingkatOptions"
                                :key="tk"
                                class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg hover:bg-emerald-50/70 text-slate-800 text-xs font-semibold cursor-pointer transition-colors"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="selectedTingkat.includes(tk)"
                                        @change="toggleItemInArray(selectedTingkat, tk)"
                                        class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                    />
                                    <span>Tingkat {{ tk }}</span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">({{ getCountTingkat(tk) }})</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. Dropdown Filter Status Keaktifan (Multi-Select) -->
                <div class="relative">
                    <button
                        type="button"
                        @click.stop="toggleDropdown('status')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold border rounded-xl bg-white shadow-2xs transition-all cursor-pointer"
                        :class="selectedStatus.length > 0 ? 'border-emerald-600 text-emerald-950 bg-emerald-50/70' : 'border-slate-300 text-slate-700 hover:border-slate-400'"
                    >
                        <span class="material-symbols-outlined text-[16px] text-emerald-700">verified_user</span>
                        <span>{{ statusFilterLabel }}</span>
                        <span class="material-symbols-outlined text-[16px] text-slate-400">expand_more</span>
                    </button>

                    <div
                        v-if="activeDropdown === 'status'"
                        class="absolute left-0 top-full mt-1.5 w-52 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-2 space-y-1"
                        @click.stop
                    >
                        <div class="flex items-center justify-between px-2 py-1 border-b border-slate-100 text-[11px] font-bold text-slate-500">
                            <span>Status Keaktifan</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectedStatus = ['Aktif', 'Tidak Aktif', 'Lulus']" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">Semua</button>
                                <span class="text-slate-300">•</span>
                                <button type="button" @click="selectedStatus = []" class="text-rose-600 hover:text-rose-800 cursor-pointer">Reset</button>
                            </div>
                        </div>
                        <div class="space-y-0.5 py-1">
                            <label
                                v-for="st in ['Aktif', 'Tidak Aktif', 'Lulus']"
                                :key="st"
                                class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg hover:bg-emerald-50/70 text-slate-800 text-xs font-semibold cursor-pointer transition-colors"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="selectedStatus.includes(st)"
                                        @change="toggleItemInArray(selectedStatus, st)"
                                        class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                    />
                                    <span>{{ st }}</span>
                                </div>
                                <span class="text-[11px] text-slate-400 font-normal">({{ getCountStatus(st) }})</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tombol Reset Filter jika ada filter aktif -->
                <button
                    v-if="isFilterActive"
                    type="button"
                    @click="resetAllFilters"
                    class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-200 transition-colors cursor-pointer"
                    title="Hapus semua filter"
                >
                    <span class="material-symbols-outlined text-[15px]">filter_alt_off</span>
                    <span>Reset Filter</span>
                </button>

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
                 TABLE DATA (Thead Dark Forest Green #004b23 & Pinned Nama Siswa)
            ====================================================== -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-auto" style="max-height: calc(100vh - 290px);">
                    <table class="text-left border-collapse min-w-max w-full">
                        <!-- ============ THEAD ROW 1: Group Headers ============ -->
                        <thead class="sticky top-0 z-30">
                            <tr class="bg-[#004b23] text-white">
                                <!-- 1. Checkbox Fixed -->
                                <th
                                    rowspan="2"
                                    class="p-0 sticky left-0 z-30 bg-[#004b23] border-r border-emerald-800 text-center w-10 select-none shadow-[2px_0_4px_rgba(0,0,0,0.06)]"
                                >
                                    <div class="flex items-center justify-center p-2">
                                        <input
                                            type="checkbox"
                                            :checked="allSelected"
                                            @change="toggleSelectAll"
                                            class="w-3.5 h-3.5 rounded accent-emerald-700 cursor-pointer"
                                        />
                                    </div>
                                </th>

                                <!-- 2. No Fixed -->
                                <th
                                    rowspan="2"
                                    class="px-2 py-2 text-center text-[11px] font-bold text-white uppercase tracking-wider sticky left-10 z-30 bg-[#004b23] border-r border-emerald-800 w-9 select-none"
                                >
                                    No
                                </th>

                                <!-- 3. Nama Siswa (PINNED / STICKY AT LEFT-[76px]) -->
                                <th
                                    rowspan="2"
                                    class="px-3 py-2 text-left text-[11px] font-bold text-white uppercase tracking-wider sticky left-[76px] z-30 bg-[#004b23] border-r-2 border-emerald-950 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.25)] min-w-[200px] w-56 select-none"
                                >
                                    Nama Siswa
                                </th>

                                <!-- Dynamic Group Headers -->
                                <th
                                    v-for="grp in visibleGroups"
                                    :key="grp.key"
                                    :colspan="groupSpan(grp.key)"
                                    class="text-center text-[11px] font-bold text-white px-3 py-2 border-r border-emerald-800/80 cursor-pointer select-none bg-[#004b23] hover:bg-[#00381a] transition-colors"
                                    @click="toggleGroup(grp.key)"
                                >
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-[14px] text-yellow-300">{{ grp.icon }}</span>
                                        <span>{{ grp.label }}</span>
                                        <span class="material-symbols-outlined text-[13px] text-emerald-300/80">
                                            {{ columnGroups.find(g => g.key === grp.key)?.expanded ? 'unfold_less' : 'unfold_more' }}
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
                                    <template v-else>
                                        <th
                                            class="text-center text-[10px] text-emerald-300 font-semibold px-2 py-1 border-r border-emerald-900 cursor-pointer select-none whitespace-nowrap"
                                            @click="toggleGroup(grp.key)"
                                        >
                                            [+] {{ columns[grp.key]?.length || 0 }} Kolom
                                        </th>
                                    </template>
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
                                <!-- 1. Checkbox Fixed -->
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

                                <!-- 2. No Fixed -->
                                <td class="px-2 py-1.5 text-[11px] text-slate-500 font-medium text-center sticky left-10 z-20 border-r border-slate-100" :class="selectedRows.has(student.nisn) ? 'bg-emerald-50' : 'bg-white group-hover:bg-slate-50'">
                                    {{ index + 1 }}
                                </td>

                                <!-- 3. Nama Siswa (PINNED / STICKY AT LEFT-[76px]) -->
                                <td class="px-2 py-1.5 sticky left-[76px] z-20 border-r-2 border-slate-300 shadow-[3px_0_6px_-2px_rgba(0,0,0,0.12)] min-w-[200px] w-56" :class="selectedRows.has(student.nisn) ? 'bg-emerald-50' : 'bg-white group-hover:bg-slate-50'">
                                    <input
                                        type="text"
                                        v-model="student.nama"
                                        class="w-full px-2 py-1 text-[11.5px] font-bold text-slate-900 border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none transition-colors bg-white"
                                    />
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
                                                    <span>{{ student[col.key] || 'Aktif' }}</span>
                                                </span>
                                            </div>

                                            <!-- Select type (Unit Formal, Unit Asrama, Program, Jenjang, Status, dll.) -->
                                            <select
                                                v-else-if="col.type === 'select'"
                                                v-model="student[col.key]"
                                                @change="onCellSelectChange(student, col.key)"
                                                :class="[
                                                    'w-full px-2 py-1 text-[11.5px] border border-slate-200 rounded-lg focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none transition-colors bg-white cursor-pointer',
                                                    col.width
                                                ]"
                                            >
                                                <option
                                                    v-for="opt in col.options"
                                                    :key="typeof opt === 'object' ? opt.value : opt"
                                                    :value="typeof opt === 'object' ? opt.value : opt"
                                                >
                                                    {{ typeof opt === 'object' ? opt.label : opt }}
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
                                    <template v-else>
                                        <td
                                            class="p-2 border-r border-slate-100 text-center bg-slate-50/50 cursor-pointer"
                                            @click="toggleGroup(grp.key)"
                                            title="Klik untuk membuka grup ini"
                                        >
                                            <span class="text-[10px] text-slate-400 font-mono">···</span>
                                        </td>
                                    </template>
                                </template>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="filteredStudents.length === 0">
                                <td :colspan="3 + visibleGroups.reduce((sum, g) => sum + groupSpan(g.key), 0)" class="py-16 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <span class="material-symbols-outlined text-2xl">search_off</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">Tidak ada data yang sesuai</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Stats -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800 font-bold">{{ filteredStudents.length }}</strong> dari total <strong class="text-slate-800 font-bold">{{ students.length }}</strong> siswa
                        <span v-if="isFilterActive" class="text-emerald-700 font-semibold ml-1">({{ filterSummaryText }})</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Status Aktif: <strong class="text-slate-700">{{ getCountStatus('Aktif') }}</strong></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Tidak Aktif: <strong class="text-slate-700">{{ getCountStatus('Tidak Aktif') }}</strong></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <span>Lulus: <strong class="text-slate-700">{{ getCountStatus('Lulus') }}</strong></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar styling halus */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
