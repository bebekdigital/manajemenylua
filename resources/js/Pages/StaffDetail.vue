<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditDataButton from '@/Components/Shared/EditDataButton.vue';
import { useWilayah } from '@/Composables/useWilayah';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    allEmployees: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
    allAcademicYears: {
        type: Array,
        default: () => [],
    },
    availableUnits: {
        type: Array,
        default: () => [],
    },
    schoolUnits: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({
            can_update: true,
            can_delete: true,
            can_view_kepegawaian: true,
            can_update_kepegawaian: true,
        }),
    },
});

const allTabs = [
    { id: 'identitas', label: 'Identitas', icon: 'badge' },
    { id: 'alamat', label: 'Alamat', icon: 'home' },
    { id: 'keluarga', label: 'Keluarga', icon: 'family_restroom' },
    { id: 'kepegawaian', label: 'Kepegawaian', icon: 'work' },
    { id: 'penugasan', label: 'Penugasan', icon: 'assignment_ind' },
    { id: 'pendidikan', label: 'Pendidikan', icon: 'school' },
    { id: 'perizinan', label: 'Perizinan', icon: 'assignment_turned_in' },
    { id: 'dokumen', label: 'Dokumen', icon: 'folder_shared' },
];

const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && allTabs.some((t) => t.id === tabParam)) {
            return tabParam;
        }
    }
    return 'identitas';
};

const page = usePage();
const activeTab = ref(getInitialTab());
const isEditing = ref(false);
const canEdit = computed(() => props.permissions?.can_update ?? true);
const canViewKepegawaian = computed(() => props.permissions?.can_view_kepegawaian ?? true);
const canUpdateKepegawaian = computed(() => props.permissions?.can_update_kepegawaian ?? true);
const canApprovePermit = computed(() => props.permissions?.can_approve_permit ?? false);
const isEditingKepegawaian = computed(() => isEditing.value && canUpdateKepegawaian.value);

// REALTIME PERMIT STATE (Reactive untuk Live Update & Edit Data)
const perizinanList = ref([...(props.employee?.perizinan || [])]);

watch(() => props.employee?.perizinan, (newVal) => {
    if (newVal) {
        perizinanList.value = [...newVal];
    }
}, { deep: true, immediate: true });

const dismissedFlash = ref(false);

const flashSuccess = computed(() => !dismissedFlash.value ? page.props.flash?.success : null);
const flashError = computed(() => !dismissedFlash.value ? page.props.flash?.error : null);

watch(() => page.url, (newUrl) => {
    try {
        const parsed = new URL(newUrl, window.location.origin);
        const tabParam = parsed.searchParams.get('tab');
        if (tabParam && allTabs.some((t) => t.id === tabParam)) {
            activeTab.value = tabParam;
        }
    } catch {
        // ignore
    }
}, { immediate: true });

const tabs = computed(() => {
    if (!canViewKepegawaian.value) {
        return allTabs.filter((t) => t.id !== 'kepegawaian');
    }
    return allTabs;
});

watch(canViewKepegawaian, (canView) => {
    if (!canView && activeTab.value === 'kepegawaian') {
        activeTab.value = 'identitas';
    }
});

// Dropdown Switcher Pemilih Pegawai Lain di Header
const isEmployeeDropdownOpen = ref(false);
const employeeSearchQuery = ref('');
const employeeDropdownRef = ref(null);
const employeeSearchInputRef = ref(null);
const mobileEmployeeSearchInputRef = ref(null);

const filteredEmployees = computed(() => {
    const list = props.allEmployees || [];
    const q = employeeSearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((emp) => {
        const nameMatch = emp.nama?.toLowerCase().includes(q);
        const nipyMatch = emp.nipy?.toLowerCase().includes(q);
        const jabatanMatch = emp.jabatan?.toLowerCase().includes(q);
        const unitMatch = emp.unit_kerja?.toLowerCase().includes(q);
        return nameMatch || nipyMatch || jabatanMatch || unitMatch;
    });
});

function toggleEmployeeDropdown(e) {
    e.stopPropagation();
    if (isEditing.value || isManagingRiwayat.value) {
        alert('Anda sedang dalam mode edit/kelola. Selesaikan terlebih dahulu sebelum beralih pegawai.');
        return;
    }
    isEmployeeDropdownOpen.value = !isEmployeeDropdownOpen.value;
    if (isEmployeeDropdownOpen.value) {
        employeeSearchQuery.value = '';
        setTimeout(() => {
            if (window.innerWidth < 640) {
                mobileEmployeeSearchInputRef.value?.focus();
            } else {
                employeeSearchInputRef.value?.focus();
            }
        }, 60);
    }
}

function closeEmployeeDropdown() {
    isEmployeeDropdownOpen.value = false;
    employeeSearchQuery.value = '';
}

function handleOutsideClick(e) {
    if (e.target?.closest?.('.mobile-employee-sheet')) return;
    if (employeeDropdownRef.value && !employeeDropdownRef.value.contains(e.target)) {
        closeEmployeeDropdown();
    }
}

function selectEmployee(empId) {
    if (String(empId) === String(props.employee.id)) {
        closeEmployeeDropdown();
        return;
    }

    if (isEditing.value || isManagingRiwayat.value) {
        alert('Anda sedang dalam mode edit/kelola. Selesaikan terlebih dahulu sebelum beralih ke pegawai lain.');
        return;
    }

    closeEmployeeDropdown();
    router.visit(`/staff/${empId}`);
}

const form = useForm({
    nama: '',
    gelar_depan: '',
    gelar_belakang: '',
    nipy: '',
    nik: '',
    jk: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    no_wa: '',
    email: '',
    skill: '',

    // Alamat Domisili
    jalan_domisili: '',
    rt_rw_domisili: '',
    dusun_domisili: '',
    desa_domisili: '',
    kecamatan_domisili: '',
    kab_domisili: '',
    provinsi_domisili: '',

    // Alamat Tinggal
    jalan_tinggal: '',
    rt_rw_tinggal: '',
    dusun_tinggal: '',
    desa_tinggal: '',
    kecamatan_tinggal: '',
    kabupaten_tinggal: '',
    provinsi_tinggal: '',
    status_rumah: '',
    kepemilikan_bpjs: '',
    penanggung_bpjs: '',

    // Profil & Keluarga
    status_pernikahan: '',
    nama_suami_istri: '',
    ttl_suami_istri: '',
    pekerjaan_suami_istri: '',
    tanggal_menikah: '',
    jumlah_anak: '',
    nama_ibu: '',
    nama_ayah: '',
    alamat_orangtua: '',
    kontak_darurat: '',
    hubungan_kontak_darurat: '',

    // Kepegawaian (Data Statis Sheet 1 & Jenjang Sheet 3)
    tmt_pengangkatan: '',
    tst_pengangkatan: '',
    academic_year_id: null,
    unit_kerja: '',
    jabatan: '',
    jenis_kepegawaian: '',
    tmt_jabatan: '',
    tst_jabatan: '',
    status_keaktifan: 'Aktif',
    keterangan_tidak_aktif: '',
    jenjang_kepegawaian: '',
    tmt_kenaikan_jenjang: '',
    tst_kenaikan_jenjang: '',
    tst_jenjang: '',
    keaktifan_dapodik: '',
    unit_keaktifan_dapodik: '',

    // Riwayat Kepegawaian & Jabatan
    riwayat_kepegawaian: [],
    riwayat_jabatan: [],

    // Riwayat Pendidikan Formal & Pelatihan Nonformal
    riwayat_pendidikan: [],
    riwayat_pelatihan: [],

    // Riwayat Perizinan
    riwayat_perizinan: [],
});

function initFormFromProps() {
    form.nama = props.employee.nama || '';
    form.gelar_depan = props.employee.gelar_depan || '';
    form.gelar_belakang = props.employee.gelar_belakang || '';
    form.nipy = props.employee.nipy || '';
    form.nik = props.employee.nik || '';
    form.jk = props.employee.jk || '';
    form.tempat_lahir = props.employee.tempat_lahir || '';
    form.tanggal_lahir = props.employee.tanggal_lahir || '';
    form.no_wa = props.employee.no_wa || '';
    form.email = props.employee.email || '';
    form.skill = props.employee.skill || '';

    form.jalan_domisili = props.employee.jalan_domisili || '';
    form.rt_rw_domisili = props.employee.rt_rw_domisili || '';
    form.dusun_domisili = props.employee.dusun_domisili || '';
    form.desa_domisili = props.employee.desa_domisili || '';
    form.kecamatan_domisili = props.employee.kecamatan_domisili || '';
    form.kab_domisili = props.employee.kab_domisili || '';
    form.provinsi_domisili = props.employee.provinsi_domisili || '';

    form.jalan_tinggal = props.employee.jalan_tinggal || '';
    form.rt_rw_tinggal = props.employee.rt_rw_tinggal || '';
    form.dusun_tinggal = props.employee.dusun_tinggal || '';
    form.desa_tinggal = props.employee.desa_tinggal || '';
    form.kecamatan_tinggal = props.employee.kecamatan_tinggal || '';
    form.kabupaten_tinggal = props.employee.kabupaten_tinggal || '';
    form.provinsi_tinggal = props.employee.provinsi_tinggal || '';
    form.status_rumah = props.employee.status_rumah || '';
    form.kepemilikan_bpjs = props.employee.kepemilikan_bpjs || '';
    form.penanggung_bpjs = props.employee.penanggung_bpjs || '';

    form.status_pernikahan = props.employee.status_pernikahan || '';
    form.nama_suami_istri = props.employee.nama_suami_istri || '';
    form.ttl_suami_istri = props.employee.ttl_suami_istri || '';
    form.pekerjaan_suami_istri = props.employee.pekerjaan_suami_istri || '';
    form.tanggal_menikah = props.employee.tanggal_menikah || '';
    form.jumlah_anak = props.employee.jumlah_anak !== null && props.employee.jumlah_anak !== undefined ? props.employee.jumlah_anak : '';
    form.nama_ibu = props.employee.nama_ibu || '';
    form.nama_ayah = props.employee.nama_ayah || '';
    form.alamat_orangtua = props.employee.alamat_orangtua || '';
    form.kontak_darurat = props.employee.kontak_darurat || '';
    form.hubungan_kontak_darurat = props.employee.hubungan_kontak_darurat || '';

    form.tmt_pengangkatan = props.employee.tmt_pengangkatan || '';
    form.tst_pengangkatan = props.employee.tst_pengangkatan || '';
    form.academic_year_id = props.selectedAcademicYear?.id || null;
    form.unit_kerja = props.employee.unit_kerja || '';
    form.jabatan = props.employee.jabatan || '';
    form.jenis_kepegawaian = props.employee.jenis_kepegawaian || '';
    form.tmt_jabatan = props.employee.tmt_jabatan || '';
    form.tst_jabatan = props.employee.tst_jabatan || '';
    form.status_keaktifan = props.employee.status_keaktifan || 'Aktif';
    form.keterangan_tidak_aktif = props.employee.keterangan_tidak_aktif || '';
    form.jenjang_kepegawaian = props.employee.jenjang_kepegawaian || '';
    form.tmt_kenaikan_jenjang = props.employee.tmt_kenaikan_jenjang || '';
    form.tst_kenaikan_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
    form.tst_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
    form.keaktifan_dapodik = props.employee.keaktifan_dapodik || '';
    form.unit_keaktifan_dapodik = props.employee.unit_keaktifan_dapodik || '';

    form.riwayat_kepegawaian = (props.employee.riwayat_kepegawaian || []).map((r) => ({
        id: r.id || null,
        jenjang_kepegawaian: r.jenjang_kepegawaian || '',
        no_sk: r.no_sk || '',
        tmt_kenaikan_jenjang: r.tmt_kenaikan_jenjang || '',
        tst_kenaikan_jenjang: r.tst_kenaikan_jenjang || r.tst_jenjang || '',
        document: r.document || null,
    }));

    form.riwayat_jabatan = (props.employee.riwayat_jabatan || []).map((p) => ({
        id: p.id || null,
        unit_kerja: p.unit_kerja || '',
        jabatan: p.jabatan || '',
        jenis_kepegawaian: p.jenis_kepegawaian || '',
        no_sk: p.no_sk || '',
        tmt_jabatan: p.tmt_jabatan || '',
        tst_jabatan: p.tst_jabatan || '',
        document: p.document || null,
        custom_jabatan: false,
    }));

    form.riwayat_pendidikan = (props.employee.riwayat_pendidikan || []).map((p) => ({
        id: p.id || null,
        jenjang_pendidikan: p.jenjang_pendidikan || '',
        jurusan: p.jurusan || '',
        instansi_pendidikan: p.instansi_pendidikan || '',
        tahun_lulus: p.tahun_lulus || '',
        pembiayaan: p.pembiayaan || '',
        document: p.document || null,
    }));

    form.riwayat_pelatihan = (props.employee.riwayat_pelatihan || []).map((t) => ({
        id: t.id || null,
        bidang: t.bidang || '',
        keahlian: t.keahlian || '',
        instansi_penyelenggara: t.instansi_penyelenggara || '',
        tahun: t.tahun || '',
        pembiayaan: t.pembiayaan || '',
        document: t.document || null,
    }));

    const sourcePermits = (perizinanList.value && perizinanList.value.length > 0)
        ? perizinanList.value
        : (props.employee.perizinan || []);

    form.riwayat_perizinan = sourcePermits.map((p) => ({
        id: p.id || null,
        tanggal: p.tanggal || '',
        tanggal_selesai: p.tanggal_selesai || '',
        jenis: p.jenis || '',
        detail: p.detail || '',
        keterangan: p.keterangan || '',
        status: p.status || 'diajukan',
        catatan: p.catatan || '',
        lampiran: null,
        lampiran_nama: p.lampiran_nama || '',
        preview_url: p.preview_url || '',
        download_url: p.download_url || '',
    }));
}

// ==========================================
// WILAYAH INDONESIA API & CASCADING STATE
// ==========================================
const { getProvinces, getRegencies, getDistricts, getVillages, isMatch } = useWilayah();

const provincesList = ref([]);

// Domisili Wilayah State
const selectedProvDomisiliId = ref('');
const selectedKabDomisiliId = ref('');
const selectedKecDomisiliId = ref('');
const selectedDesDomisiliId = ref('');
const regenciesDomisili = ref([]);
const districtsDomisili = ref([]);
const villagesDomisili = ref([]);
const loadingDomisili = ref({ prov: false, kab: false, kec: false, des: false });

// Tinggal Wilayah State
const selectedProvTinggalId = ref('');
const selectedKabTinggalId = ref('');
const selectedKecTinggalId = ref('');
const selectedDesTinggalId = ref('');
const regenciesTinggal = ref([]);
const districtsTinggal = ref([]);
const villagesTinggal = ref([]);
const loadingTinggal = ref({ prov: false, kab: false, kec: false, des: false });

async function initWilayahFromForm() {
    loadingDomisili.value.prov = true;
    loadingTinggal.value.prov = true;
    try {
        provincesList.value = await getProvinces();
    } finally {
        loadingDomisili.value.prov = false;
        loadingTinggal.value.prov = false;
    }

    // 1. Domisili
    if (form.provinsi_domisili) {
        const prov = provincesList.value.find((p) => p.id === selectedProvDomisiliId.value || isMatch(p.name, form.provinsi_domisili));
        if (prov) {
            selectedProvDomisiliId.value = prov.id;
            form.provinsi_domisili = prov.name;
            loadingDomisili.value.kab = true;
            try {
                regenciesDomisili.value = await getRegencies(prov.id);
            } finally {
                loadingDomisili.value.kab = false;
            }

            if (form.kab_domisili) {
                const kab = regenciesDomisili.value.find((k) => k.id === selectedKabDomisiliId.value || isMatch(k.name, form.kab_domisili));
                if (kab) {
                    selectedKabDomisiliId.value = kab.id;
                    form.kab_domisili = kab.name;
                    loadingDomisili.value.kec = true;
                    try {
                        districtsDomisili.value = await getDistricts(kab.id);
                    } finally {
                        loadingDomisili.value.kec = false;
                    }

                    if (form.kecamatan_domisili) {
                        const kec = districtsDomisili.value.find((d) => d.id === selectedKecDomisiliId.value || isMatch(d.name, form.kecamatan_domisili));
                        if (kec) {
                            selectedKecDomisiliId.value = kec.id;
                            form.kecamatan_domisili = kec.name;
                            loadingDomisili.value.des = true;
                            try {
                                villagesDomisili.value = await getVillages(kec.id);
                            } finally {
                                loadingDomisili.value.des = false;
                            }

                            if (form.desa_domisili) {
                                const des = villagesDomisili.value.find((v) => v.id === selectedDesDomisiliId.value || isMatch(v.name, form.desa_domisili));
                                if (des) {
                                    selectedDesDomisiliId.value = des.id;
                                    form.desa_domisili = des.name;
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        selectedProvDomisiliId.value = '';
        selectedKabDomisiliId.value = '';
        selectedKecDomisiliId.value = '';
        selectedDesDomisiliId.value = '';
        regenciesDomisili.value = [];
        districtsDomisili.value = [];
        villagesDomisili.value = [];
    }

    // 2. Tinggal
    if (form.provinsi_tinggal) {
        const prov = provincesList.value.find((p) => p.id === selectedProvTinggalId.value || isMatch(p.name, form.provinsi_tinggal));
        if (prov) {
            selectedProvTinggalId.value = prov.id;
            form.provinsi_tinggal = prov.name;
            loadingTinggal.value.kab = true;
            try {
                regenciesTinggal.value = await getRegencies(prov.id);
            } finally {
                loadingTinggal.value.kab = false;
            }

            if (form.kabupaten_tinggal) {
                const kab = regenciesTinggal.value.find((k) => k.id === selectedKabTinggalId.value || isMatch(k.name, form.kabupaten_tinggal));
                if (kab) {
                    selectedKabTinggalId.value = kab.id;
                    form.kabupaten_tinggal = kab.name;
                    loadingTinggal.value.kec = true;
                    try {
                        districtsTinggal.value = await getDistricts(kab.id);
                    } finally {
                        loadingTinggal.value.kec = false;
                    }

                    if (form.kecamatan_tinggal) {
                        const kec = districtsTinggal.value.find((d) => d.id === selectedKecTinggalId.value || isMatch(d.name, form.kecamatan_tinggal));
                        if (kec) {
                            selectedKecTinggalId.value = kec.id;
                            form.kecamatan_tinggal = kec.name;
                            loadingTinggal.value.des = true;
                            try {
                                villagesTinggal.value = await getVillages(kec.id);
                            } finally {
                                loadingTinggal.value.des = false;
                            }

                            if (form.desa_tinggal) {
                                const des = villagesTinggal.value.find((v) => v.id === selectedDesTinggalId.value || isMatch(v.name, form.desa_tinggal));
                                if (des) {
                                    selectedDesTinggalId.value = des.id;
                                    form.desa_tinggal = des.name;
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        selectedProvTinggalId.value = '';
        selectedKabTinggalId.value = '';
        selectedKecTinggalId.value = '';
        selectedDesTinggalId.value = '';
        regenciesTinggal.value = [];
        districtsTinggal.value = [];
        villagesTinggal.value = [];
    }
}

async function onProvinsiDomisiliChange() {
    const val = selectedProvDomisiliId.value;
    const prov = provincesList.value.find((p) => p.id === val || isMatch(p.name, val));

    if (prov) {
        selectedProvDomisiliId.value = prov.id;
        form.provinsi_domisili = prov.name;
    } else {
        form.provinsi_domisili = val || '';
    }

    selectedKabDomisiliId.value = '';
    selectedKecDomisiliId.value = '';
    selectedDesDomisiliId.value = '';
    form.kab_domisili = '';
    form.kecamatan_domisili = '';
    form.desa_domisili = '';
    regenciesDomisili.value = [];
    districtsDomisili.value = [];
    villagesDomisili.value = [];

    if (selectedProvDomisiliId.value) {
        loadingDomisili.value.kab = true;
        try {
            regenciesDomisili.value = await getRegencies(selectedProvDomisiliId.value);
        } finally {
            loadingDomisili.value.kab = false;
        }
    }
}

async function onKabupatenDomisiliChange() {
    const val = selectedKabDomisiliId.value;
    const kab = regenciesDomisili.value.find((k) => k.id === val || isMatch(k.name, val));

    if (kab) {
        selectedKabDomisiliId.value = kab.id;
        form.kab_domisili = kab.name;
    } else {
        form.kab_domisili = val || '';
    }

    selectedKecDomisiliId.value = '';
    selectedDesDomisiliId.value = '';
    form.kecamatan_domisili = '';
    form.desa_domisili = '';
    districtsDomisili.value = [];
    villagesDomisili.value = [];

    if (selectedKabDomisiliId.value) {
        loadingDomisili.value.kec = true;
        try {
            districtsDomisili.value = await getDistricts(selectedKabDomisiliId.value);
        } finally {
            loadingDomisili.value.kec = false;
        }
    }
}

async function onKecamatanDomisiliChange() {
    const val = selectedKecDomisiliId.value;
    const kec = districtsDomisili.value.find((d) => d.id === val || isMatch(d.name, val));

    if (kec) {
        selectedKecDomisiliId.value = kec.id;
        form.kecamatan_domisili = kec.name;
    } else {
        form.kecamatan_domisili = val || '';
    }

    selectedDesDomisiliId.value = '';
    form.desa_domisili = '';
    villagesDomisili.value = [];

    if (selectedKecDomisiliId.value) {
        loadingDomisili.value.des = true;
        try {
            villagesDomisili.value = await getVillages(selectedKecDomisiliId.value);
        } finally {
            loadingDomisili.value.des = false;
        }
    }
}

function onDesaDomisiliChange() {
    const val = selectedDesDomisiliId.value;
    const des = villagesDomisili.value.find((v) => v.id === val || isMatch(v.name, val));

    if (des) {
        selectedDesDomisiliId.value = des.id;
        form.desa_domisili = des.name;
    } else {
        form.desa_domisili = val || '';
    }
}

async function onProvinsiTinggalChange() {
    const val = selectedProvTinggalId.value;
    const prov = provincesList.value.find((p) => p.id === val || isMatch(p.name, val));

    if (prov) {
        selectedProvTinggalId.value = prov.id;
        form.provinsi_tinggal = prov.name;
    } else {
        form.provinsi_tinggal = val || '';
    }

    selectedKabTinggalId.value = '';
    selectedKecTinggalId.value = '';
    selectedDesTinggalId.value = '';
    form.kabupaten_tinggal = '';
    form.kecamatan_tinggal = '';
    form.desa_tinggal = '';
    regenciesTinggal.value = [];
    districtsTinggal.value = [];
    villagesTinggal.value = [];

    if (selectedProvTinggalId.value) {
        loadingTinggal.value.kab = true;
        try {
            regenciesTinggal.value = await getRegencies(selectedProvTinggalId.value);
        } finally {
            loadingTinggal.value.kab = false;
        }
    }
}

async function onKabupatenTinggalChange() {
    const val = selectedKabTinggalId.value;
    const kab = regenciesTinggal.value.find((k) => k.id === val || isMatch(k.name, val));

    if (kab) {
        selectedKabTinggalId.value = kab.id;
        form.kabupaten_tinggal = kab.name;
    } else {
        form.kabupaten_tinggal = val || '';
    }

    selectedKecTinggalId.value = '';
    selectedDesTinggalId.value = '';
    form.kecamatan_tinggal = '';
    form.desa_tinggal = '';
    districtsTinggal.value = [];
    villagesTinggal.value = [];

    if (selectedKabTinggalId.value) {
        loadingTinggal.value.kec = true;
        try {
            districtsTinggal.value = await getDistricts(selectedKabTinggalId.value);
        } finally {
            loadingTinggal.value.kec = false;
        }
    }
}

async function onKecamatanTinggalChange() {
    const val = selectedKecTinggalId.value;
    const kec = districtsTinggal.value.find((d) => d.id === val || isMatch(d.name, val));

    if (kec) {
        selectedKecTinggalId.value = kec.id;
        form.kecamatan_tinggal = kec.name;
    } else {
        form.kecamatan_tinggal = val || '';
    }

    selectedDesTinggalId.value = '';
    form.desa_tinggal = '';
    villagesTinggal.value = [];

    if (selectedKecTinggalId.value) {
        loadingTinggal.value.des = true;
        try {
            villagesTinggal.value = await getVillages(selectedKecTinggalId.value);
        } finally {
            loadingTinggal.value.des = false;
        }
    }
}

function onDesaTinggalChange() {
    const val = selectedDesTinggalId.value;
    const des = villagesTinggal.value.find((v) => v.id === val || isMatch(v.name, val));

    if (des) {
        selectedDesTinggalId.value = des.id;
        form.desa_tinggal = des.name;
    } else {
        form.desa_tinggal = val || '';
    }
}

onMounted(async () => {
    initFormFromProps();
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === 'true' && canEdit.value) {
        isEditing.value = true;
    }
    await initWilayahFromForm();
    window.addEventListener('click', handleOutsideClick);
    window.addEventListener('focus', onWindowFocus);
    window.addEventListener('resize', checkScroll);
    nextTick(() => checkScroll());
});

onUnmounted(() => {
    window.removeEventListener('click', handleOutsideClick);
    window.removeEventListener('focus', onWindowFocus);
    window.removeEventListener('resize', checkScroll);
    stopPermitPolling();
});

function startEdit() {
    if (!canEdit.value) return;
    if (isManagingRiwayat.value) {
        alert('Anda sedang dalam mode kelola Riwayat Jenjang. Selesaikan (klik Selesai Kelola) terlebih dahulu sebelum mengedit Informasi Umum.');
        return;
    }
    initFormFromProps();
    form.clearErrors();
    isEditing.value = true;
    initWilayahFromForm();
}

function cancelEdit() {
    initFormFromProps();
    form.clearErrors();
    isEditing.value = false;
    initWilayahFromForm();
}

function saveChanges() {
    if (!canUpdateKepegawaian.value) {
        // Pulihkan nilai asli kepegawaian agar tidak terubah saat user tidak memiliki hak akses edit kepegawaian
        form.nipy = props.employee.nipy || '';
        form.tmt_pengangkatan = props.employee.tmt_pengangkatan || '';
        form.tst_pengangkatan = props.employee.tst_pengangkatan || '';
        form.status_keaktifan = props.employee.status_keaktifan || 'Aktif';
        form.keterangan_tidak_aktif = props.employee.keterangan_tidak_aktif || '';
        form.keaktifan_dapodik = props.employee.keaktifan_dapodik || '';
        form.unit_keaktifan_dapodik = props.employee.unit_keaktifan_dapodik || '';
        form.jenjang_kepegawaian = props.employee.jenjang_kepegawaian || '';
        form.tmt_kenaikan_jenjang = props.employee.tmt_kenaikan_jenjang || '';
        form.tst_kenaikan_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
        form.tst_jenjang = props.employee.tst_kenaikan_jenjang || props.employee.tst_jenjang || '';
        form.riwayat_kepegawaian = JSON.parse(JSON.stringify(props.employee.riwayat_kepegawaian || []));
    } else if (form.riwayat_kepegawaian && form.riwayat_kepegawaian.length > 0) {
        const validRecs = form.riwayat_kepegawaian.filter((r) => r.jenjang_kepegawaian || r.tmt_kenaikan_jenjang);
        if (validRecs.length > 0) {
            const sorted = [...validRecs].sort((a, b) => (b.tmt_kenaikan_jenjang || '').localeCompare(a.tmt_kenaikan_jenjang || ''));
            form.jenjang_kepegawaian = sorted[0].jenjang_kepegawaian || '';
            form.tmt_kenaikan_jenjang = sorted[0].tmt_kenaikan_jenjang || '';
            form.tst_kenaikan_jenjang = sorted[0].tst_kenaikan_jenjang || '';
            form.tst_jenjang = sorted[0].tst_kenaikan_jenjang || '';
        } else {
            form.jenjang_kepegawaian = '';
            form.tmt_kenaikan_jenjang = '';
            form.tst_kenaikan_jenjang = '';
            form.tst_jenjang = '';
        }
    } else {
        form.jenjang_kepegawaian = '';
        form.tmt_kenaikan_jenjang = '';
        form.tst_kenaikan_jenjang = '';
        form.tst_jenjang = '';
    }

    // Sync latest jabatan to top-level fields
    if (form.riwayat_jabatan && form.riwayat_jabatan.length > 0) {
        const validPos = form.riwayat_jabatan.filter((p) => p.jabatan || p.unit_kerja || p.tmt_jabatan);
        if (validPos.length > 0) {
            const sorted = [...validPos].sort((a, b) => (b.tmt_jabatan || '').localeCompare(a.tmt_jabatan || ''));
            form.unit_kerja = sorted[0].unit_kerja || '';
            form.jabatan = sorted[0].jabatan || '';
            form.jenis_kepegawaian = sorted[0].jenis_kepegawaian || '';
            form.tmt_jabatan = sorted[0].tmt_jabatan || '';
            form.tst_jabatan = sorted[0].tst_jabatan || '';
        } else {
            form.unit_kerja = '';
            form.jabatan = '';
            form.jenis_kepegawaian = '';
            form.tmt_jabatan = '';
            form.tst_jabatan = '';
        }
    } else {
        form.unit_kerja = '';
        form.jabatan = '';
        form.jenis_kepegawaian = '';
        form.tmt_jabatan = '';
        form.tst_jabatan = '';
    }

    const hasPermitFiles = (form.riwayat_perizinan || []).some((r) => r.lampiran instanceof File);
    if (hasPermitFiles) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/staff/${props.employee.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isEditing.value = false;
                dismissedFlash.value = false;
            },
        });
    } else {
        form.put(`/staff/${props.employee.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
                dismissedFlash.value = false;
            },
        });
    }
}

// State & fungsi Kelola Riwayat Jenjang Kepegawaian (Section 2)
const isManagingRiwayat = ref(false);
const isRiwayatModalOpen = ref(false);
const modalRiwayatMode = ref('create'); // 'create' | 'edit'
const editingRiwayatIndex = ref(null);
const isSavingRiwayat = ref(false);

const riwayatModalForm = reactive({
    id: null,
    jenjang_kepegawaian: '',
    no_sk: '',
    tmt_kenaikan_jenjang: '',
    tst_kenaikan_jenjang: '',
});

const currentRiwayatList = computed(() => {
    return (form.riwayat_kepegawaian && form.riwayat_kepegawaian.length > 0)
        ? form.riwayat_kepegawaian
        : (props.employee.riwayat_kepegawaian || []);
});

function startManageRiwayat() {
    if (isEditing.value) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat jenjang.');
        return;
    }
    isManagingRiwayat.value = true;
    if (!form.riwayat_kepegawaian || form.riwayat_kepegawaian.length === 0) {
        form.riwayat_kepegawaian = JSON.parse(JSON.stringify(props.employee.riwayat_kepegawaian || []));
    }
}

function finishManageRiwayat() {
    isManagingRiwayat.value = false;
}

function openAddRiwayatModal() {
    modalRiwayatMode.value = 'create';
    editingRiwayatIndex.value = null;
    riwayatModalForm.id = null;
    riwayatModalForm.jenjang_kepegawaian = '';
    riwayatModalForm.no_sk = '';
    riwayatModalForm.tmt_kenaikan_jenjang = '';
    riwayatModalForm.tst_kenaikan_jenjang = '';
    isRiwayatModalOpen.value = true;
}

function openEditRiwayatModal(record, index) {
    modalRiwayatMode.value = 'edit';
    editingRiwayatIndex.value = index;
    riwayatModalForm.id = record.id || null;
    riwayatModalForm.jenjang_kepegawaian = record.jenjang_kepegawaian || '';
    riwayatModalForm.no_sk = record.no_sk || '';
    riwayatModalForm.tmt_kenaikan_jenjang = record.tmt_kenaikan_jenjang || '';
    riwayatModalForm.tst_kenaikan_jenjang = record.tst_kenaikan_jenjang || record.tst_jenjang || '';
    isRiwayatModalOpen.value = true;
}

function closeRiwayatModal() {
    isRiwayatModalOpen.value = false;
}

function syncJenjangKepegawaianFromRecords() {
    if (form.riwayat_kepegawaian && form.riwayat_kepegawaian.length > 0) {
        const validRecs = form.riwayat_kepegawaian.filter((r) => r.jenjang_kepegawaian || r.tmt_kenaikan_jenjang);
        if (validRecs.length > 0) {
            const sorted = [...validRecs].sort((a, b) => (b.tmt_kenaikan_jenjang || '').localeCompare(a.tmt_kenaikan_jenjang || ''));
            form.jenjang_kepegawaian = sorted[0].jenjang_kepegawaian || '';
            form.tmt_kenaikan_jenjang = sorted[0].tmt_kenaikan_jenjang || '';
            form.tst_kenaikan_jenjang = sorted[0].tst_kenaikan_jenjang || '';
            form.tst_jenjang = sorted[0].tst_kenaikan_jenjang || '';
        } else {
            form.jenjang_kepegawaian = '';
            form.tmt_kenaikan_jenjang = '';
            form.tst_kenaikan_jenjang = '';
            form.tst_jenjang = '';
        }
    } else {
        form.jenjang_kepegawaian = '';
        form.tmt_kenaikan_jenjang = '';
        form.tst_kenaikan_jenjang = '';
        form.tst_jenjang = '';
    }
}

function submitRiwayatModal() {
    if (!riwayatModalForm.jenjang_kepegawaian && !riwayatModalForm.tmt_kenaikan_jenjang) {
        alert('Mohon pilih Jenjang Kepegawaian atau isi TMT Kenaikan Jenjang.');
        return;
    }

    if (!form.riwayat_kepegawaian) {
        form.riwayat_kepegawaian = JSON.parse(JSON.stringify(props.employee.riwayat_kepegawaian || []));
    }

    if (modalRiwayatMode.value === 'create') {
        form.riwayat_kepegawaian.push({
            id: null,
            jenjang_kepegawaian: riwayatModalForm.jenjang_kepegawaian,
            no_sk: riwayatModalForm.no_sk,
            tmt_kenaikan_jenjang: riwayatModalForm.tmt_kenaikan_jenjang,
            tst_kenaikan_jenjang: riwayatModalForm.tst_kenaikan_jenjang,
            document: null,
        });
    } else if (modalRiwayatMode.value === 'edit' && editingRiwayatIndex.value !== null) {
        const item = form.riwayat_kepegawaian[editingRiwayatIndex.value];
        if (item) {
            item.jenjang_kepegawaian = riwayatModalForm.jenjang_kepegawaian;
            item.no_sk = riwayatModalForm.no_sk;
            item.tmt_kenaikan_jenjang = riwayatModalForm.tmt_kenaikan_jenjang;
            item.tst_kenaikan_jenjang = riwayatModalForm.tst_kenaikan_jenjang;
        }
    }

    syncJenjangKepegawaianFromRecords();

    isSavingRiwayat.value = true;
    form.put(`/staff/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isRiwayatModalOpen.value = false;
            isSavingRiwayat.value = false;
            initFormFromProps();
        },
        onError: () => {
            isSavingRiwayat.value = false;
        }
    });
}

function deleteRiwayatRecord(index) {
    if (!confirm('Apakah Anda yakin ingin menghapus catatan riwayat jenjang ini?')) {
        return;
    }
    if (!form.riwayat_kepegawaian) {
        form.riwayat_kepegawaian = JSON.parse(JSON.stringify(props.employee.riwayat_kepegawaian || []));
    }
    form.riwayat_kepegawaian.splice(index, 1);
    syncJenjangKepegawaianFromRecords();

    isSavingRiwayat.value = true;
    form.put(`/staff/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isSavingRiwayat.value = false;
            initFormFromProps();
        },
        onError: () => {
            isSavingRiwayat.value = false;
        }
    });
}

function addRiwayatKepegawaianRow() {
    openAddRiwayatModal();
}

function removeRiwayatKepegawaianRow(index) {
    deleteRiwayatRecord(index);
}

function addRiwayatJabatanRow() {
    form.riwayat_jabatan.push({
        id: null,
        unit_kerja: '',
        jabatan: '',
        jenis_kepegawaian: '',
        no_sk: '',
        tmt_jabatan: '',
        tst_jabatan: '',
        document: null,
        custom_jabatan: false,
    });
}

function removeRiwayatJabatanRow(index) {
    form.riwayat_jabatan.splice(index, 1);
}

// Daftar nama Unit Kerja / Bidang dari Diagram Susunan Organisasi (Departments)
const bidangList = computed(() => {
    return (props.departments || []).map((d) => d.name);
});

// Semua nama unit kerja terdaftar di master
const allMasterUnitNames = computed(() => {
    return bidangList.value;
});

// Mengambil daftar nama jabatan yang cocok dengan unit kerja yang dipilih
function getJabatanOptionsForUnit(unitName) {
    if (!unitName) return [];

    const cleanUnit = unitName.trim().toLowerCase();

    // Cari di Bidang / Unit Organisasi (Departments dari Diagram)
    const foundDept = (props.departments || []).find((d) => d.name && d.name.trim().toLowerCase() === cleanUnit);
    if (foundDept && Array.isArray(foundDept.positions) && foundDept.positions.length > 0) {
        return foundDept.positions;
    }

    // Fallback cari di schoolUnits jika ada
    const foundSchool = (props.schoolUnits || []).find((u) => u.name && u.name.trim().toLowerCase() === cleanUnit);
    if (foundSchool && Array.isArray(foundSchool.positions) && foundSchool.positions.length > 0) {
        return foundSchool.positions;
    }

    return [];
}

// Reset jabatan jika unit kerja diganti dan jabatan lama tidak ada di unit baru
function onUnitKerjaChange(pos) {
    const available = getJabatanOptionsForUnit(pos.unit_kerja);
    if (pos.jabatan && available.length > 0 && !available.includes(pos.jabatan)) {
        pos.jabatan = '';
    }
}

function copyDomisiliToTinggal() {
    form.jalan_tinggal = form.jalan_domisili;
    form.rt_rw_tinggal = form.rt_rw_domisili;
    form.dusun_tinggal = form.dusun_domisili;
    form.desa_tinggal = form.desa_domisili;
    form.kecamatan_tinggal = form.kecamatan_domisili;
    form.kabupaten_tinggal = form.kab_domisili;
    form.provinsi_tinggal = form.provinsi_domisili;

    // Sync cascading dropdown state
    selectedProvTinggalId.value = selectedProvDomisiliId.value;
    regenciesTinggal.value = [...regenciesDomisili.value];
    selectedKabTinggalId.value = selectedKabDomisiliId.value;
    districtsTinggal.value = [...districtsDomisili.value];
    selectedKecTinggalId.value = selectedKecDomisiliId.value;
    villagesTinggal.value = [...villagesDomisili.value];
    selectedDesTinggalId.value = selectedDesDomisiliId.value;
}

function addEducationRow() {
    form.riwayat_pendidikan.push({
        id: null,
        jenjang_pendidikan: '',
        jurusan: '',
        instansi_pendidikan: '',
        tahun_lulus: '',
        pembiayaan: '',
        document: null,
    });
}

function removeEducationRow(index) {
    form.riwayat_pendidikan.splice(index, 1);
}

function addTrainingRow() {
    form.riwayat_pelatihan.push({
        id: null,
        bidang: '',
        keahlian: '',
        instansi_penyelenggara: '',
        tahun: '',
        pembiayaan: '',
        document: null,
    });
}

function removeTrainingRow(index) {
    form.riwayat_pelatihan.splice(index, 1);
}

function addRiwayatPerizinanRow() {
    const todayStr = new Date().toISOString().split('T')[0];
    form.riwayat_perizinan.push({
        id: null,
        tanggal: todayStr,
        tanggal_selesai: '',
        jenis: '',
        detail: '',
        keterangan: '',
        status: 'diajukan',
        catatan: '',
        lampiran: null,
        lampiran_nama: '',
        preview_url: '',
        download_url: '',
    });
}

function removeRiwayatPerizinanRow(index) {
    const row = form.riwayat_perizinan[index];
    if (!canApprovePermit.value) {
        if (row && row.id && row.status === 'disetujui') {
            alert('Perizinan yang sudah disetujui tidak dapat dihapus.');
            return;
        }
        if (isTeacher.value && row && row.id && row.status !== 'diajukan') {
            alert('Perizinan yang sudah diproses tidak dapat dihapus.');
            return;
        }
    }
    form.riwayat_perizinan.splice(index, 1);
}

function onPermitFileChange(row, event) {
    const file = event.target.files?.[0] || null;
    row.lampiran = file;
}

function openPermitPreview(item) {
    if (!item?.preview_url) return;
    const isPdf = (item.lampiran_nama || '').toLowerCase().endsWith('.pdf');
    openPreview({
        title: item.lampiran_nama || `Lampiran Perizinan - ${item.jenis}`,
        file_name: item.lampiran_nama || 'lampiran',
        formatted_file_size: '',
        mime_type: isPdf ? 'application/pdf' : 'image/jpeg',
        preview_url: item.preview_url,
        download_url: item.download_url,
    });
}

const showPermitNoteModal = ref(false);
const permitNoteForm = useForm({
    id: null,
    jenis: '',
    detail: '',
    status: 'diajukan',
    catatan: '',
    updater_name: '',
    updater_role: '',
    formatted_tanggal: '',
});

function openPermitNoteModal(item) {
    if (!item?.id) return;
    permitNoteForm.id = item.id;
    permitNoteForm.jenis = item.jenis || '';
    permitNoteForm.detail = item.detail || '';
    permitNoteForm.status = item.status || 'diajukan';
    permitNoteForm.catatan = item.catatan || '';
    permitNoteForm.updater_name = item.updater_name || '';
    permitNoteForm.updater_role = item.updater_role || '';
    permitNoteForm.formatted_tanggal = item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '';
    showPermitNoteModal.value = true;
}

function closePermitNoteModal() {
    showPermitNoteModal.value = false;
    permitNoteForm.reset();
}

function submitPermitNote() {
    if (!permitNoteForm.id) return;
    permitNoteForm.patch(`/staff/${props.employee.id}/permits/${permitNoteForm.id}/note`, {
        preserveScroll: true,
        onSuccess: () => {
            closePermitNoteModal();
            fetchPermitsRealtime();
        },
    });
}

// REALTIME PERMIT POLLING (Ringan & Cepat untuk Shared Hosting)
let permitPollInterval = null;

async function fetchPermitsRealtime() {
    // Hindari reload jika sedang mode edit form profil utama atau sedang mengisi form approval
    if (isEditing.value) return;

    // Jika pihak approval sedang membuka modal untuk memberi status/catatan, jangan fetch agar input tidak terganggu
    if (showPermitNoteModal.value && canApprovePermit.value) return;

    try {
        const response = await axios.get(`/staff/${props.employee.id}/permits/realtime`);
        if (response.data && Array.isArray(response.data.permits)) {
            perizinanList.value = response.data.permits;
            if (props.employee) {
                props.employee.perizinan = response.data.permits;
            }

            // Auto-sinkronisasi data di modal HANYA jika modal sedang dibuka oleh penerima respon (bukan approver)
            if (showPermitNoteModal.value && permitNoteForm.id && !canApprovePermit.value) {
                const item = response.data.permits.find(p => p.id === permitNoteForm.id);
                if (item) {
                    permitNoteForm.status = item.status || 'diajukan';
                    permitNoteForm.catatan = item.catatan || '';
                    permitNoteForm.updater_name = item.updater_name || '';
                    permitNoteForm.updater_role = item.updater_role || '';
                }
            }
        }
    } catch {
        // Abaikan error jaringan sesaat
    }
}

function startPermitPolling() {
    stopPermitPolling();
    // Tarik data segera tanpa menunggu interval
    fetchPermitsRealtime();
    // Interval 3 detik HANYA saat tab perizinan aktif
    permitPollInterval = setInterval(() => {
        if (activeTab.value === 'perizinan' && !isEditing.value && !(showPermitNoteModal.value && canApprovePermit.value)) {
            fetchPermitsRealtime();
        }
    }, 3000);
}

function stopPermitPolling() {
    if (permitPollInterval) {
        clearInterval(permitPollInterval);
        permitPollInterval = null;
    }
}

function onWindowFocus() {
    if (activeTab.value === 'perizinan' && !isEditing.value && !(showPermitNoteModal.value && canApprovePermit.value)) {
        fetchPermitsRealtime();
    }
}

// Pantau perubahan tab aktif
watch(activeTab, (newTab) => {
    if (newTab === 'perizinan') {
        startPermitPolling();
    } else {
        stopPermitPolling();
    }
}, { immediate: true });

function getAlamatDomisili(e) {
    return [e.jalan_domisili, e.rt_rw_domisili, e.dusun_domisili, e.desa_domisili, e.kecamatan_domisili, e.kab_domisili, e.provinsi_domisili]
        .filter(Boolean).join(', ') || '-';
}

function getAlamatTinggal(e) {
    return [e.jalan_tinggal, e.rt_rw_tinggal, e.dusun_tinggal, e.desa_tinggal, e.kecamatan_tinggal, e.kabupaten_tinggal, e.provinsi_tinggal]
        .filter(Boolean).join(', ') || '-';
}

function getStatusColor(status) {
    const map = {
        'Aktif': 'bg-emerald-100 text-emerald-700',
        'Non-Aktif': 'bg-slate-100 text-slate-500',
        'Pensiun': 'bg-amber-100 text-amber-700',
        'Cuti': 'bg-blue-100 text-blue-700',
    };
    return map[status] ?? 'bg-outline-variant/10 text-on-surface-variant';
}

function getJenjangColor(jenjang) {
    const map = {
        'PJT': 'bg-amber-100 text-amber-800',
        'PTTY': 'bg-blue-100 text-blue-700',
        'PTY': 'bg-emerald-100 text-emerald-700',
        // Fallback untuk legacy data
        'GTY': 'bg-primary/10 text-primary',
        'GTT': 'bg-secondary/10 text-secondary',
        'PTT': 'bg-orange-100 text-orange-700',
    };
    return map[jenjang] ?? 'bg-outline-variant/10 text-on-surface-variant';
}

function parseDateOnly(dateVal) {
    if (!dateVal) return null;
    if (dateVal instanceof Date) return dateVal;
    if (typeof dateVal === 'string') {
        const str = dateVal.split('T')[0].trim();
        const parts = str.split('-');
        if (parts.length === 3) {
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const d = parseInt(parts[2], 10);
            if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                return new Date(y, m, d);
            }
        }
    }
    const d = new Date(dateVal);
    return isNaN(d.getTime()) ? null : d;
}

function hitungMasaKerja(tmtDate, tstDate = null) {
    if (!tmtDate) return '-';
    const start = parseDateOnly(tmtDate);
    if (!start) return '-';

    let end = tstDate ? parseDateOnly(tstDate) : null;
    if (!end) {
        const now = new Date();
        end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    }

    if (start > end) return '0 Tahun';

    let years = end.getFullYear() - start.getFullYear();
    let months = end.getMonth() - start.getMonth();
    let days = end.getDate() - start.getDate();

    if (days < 0) {
        months--;
    }
    if (months < 0) {
        years--;
        months += 12;
    }

    if (years <= 0 && months <= 0) {
        return 'Kurang dari 1 bulan';
    }

    const parts = [];
    if (years > 0) parts.push(`${years} Tahun`);
    if (months > 0) parts.push(`${months} Bulan`);
    return parts.join(' ');
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
        return dateStr;
    }
}

function hitungUsia(tanggalLahir, ttlString = '') {
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        if (!isNaN(birthDate.getTime())) {
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 ? `${age} Tahun` : '-';
        }
    }

    if (ttlString) {
        const indonesianMonths = {
            januari: 0, februari: 1, maret: 2, april: 3, mei: 4, juni: 5,
            juli: 6, agustus: 7, september: 8, oktober: 9, november: 10, desember: 11
        };
        const match = ttlString.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/);
        if (match) {
            const day = parseInt(match[1]);
            const monthName = match[2].toLowerCase();
            const year = parseInt(match[3]);
            if (indonesianMonths[monthName] !== undefined) {
                const birthDate = new Date(year, indonesianMonths[monthName], day);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }

        const numMatch = ttlString.match(/(\d{1,2})[-/](\d{1,2})[-/](\d{4})/);
        if (numMatch) {
            const day = parseInt(numMatch[1]);
            const month = parseInt(numMatch[2]) - 1;
            const year = parseInt(numMatch[3]);
            const birthDate = new Date(year, month, day);
            if (!isNaN(birthDate.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age >= 0 ? `${age} Tahun` : '-';
            }
        }
    }

    return '-';
}

// ==========================================
// DOKUMEN PEGAWAI (PRIVATE STORAGE - OPSI A)
// ==========================================
const showUploadModal = ref(false);
const showPreviewModal = ref(false);
const uploadCategory = ref('');
const uploadRecordType = ref(null);
const uploadRecordId = ref(null);
const uploadTitle = ref('');
const uploadFile = ref(null);
const uploadFileError = ref('');
const isUploading = ref(false);
const isDragging = ref(false);
const fileInput = ref(null);

const previewDoc = ref(null);

const dokumenPribadiList = computed(() => {
    const list = [];
    let no = 1;

    // 1. KTP
    list.push({
        id: 'ktp',
        no: no++,
        nama: 'KTP (Kartu Tanda Penduduk)',
        keterangan: props.employee.nik ? `NIK: ${props.employee.nik}` : 'NIK belum diisi',
        document: props.employee.dokumen_ktp,
        uploadCategory: 'ktp',
        recordType: null,
        recordId: null,
        uploadTitle: `KTP - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. KK
    list.push({
        id: 'kk',
        no: no++,
        nama: 'KK (Kartu Keluarga)',
        keterangan: 'Dokumen resmi Kartu Keluarga',
        document: props.employee.dokumen_kk,
        uploadCategory: 'kk',
        recordType: null,
        recordId: null,
        uploadTitle: `KK - ${props.employee.nama}`,
        isAdditional: false,
    });

    return list;
});

const dokumenKepegawaianList = computed(() => {
    const list = [];
    let no = 1;

    // 1. SK Pengangkatan Awal
    const tmtAwal = props.employee.formatted_tmt_pengangkatan || formatTanggalIndo(props.employee.tmt_pengangkatan) || '-';
    const tstAwal = props.employee.formatted_tst_pengangkatan || formatTanggalIndo(props.employee.tst_pengangkatan) || 'Tetap';
    list.push({
        id: 'sk_pengangkatan',
        no: no++,
        nama: 'SK Pengangkatan Awal',
        keterangan: `TMT: ${tmtAwal} • TST: ${tstAwal}`,
        document: props.employee.dokumen_sk_pengangkatan,
        uploadCategory: 'sk_pengangkatan',
        recordType: null,
        recordId: null,
        uploadTitle: `SK Pengangkatan - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. SK Jenjang Kepegawaian (Terkoneksi Tab Kepegawaian)
    if (props.employee.riwayat_kepegawaian && props.employee.riwayat_kepegawaian.length > 0) {
        props.employee.riwayat_kepegawaian.forEach((rec) => {
            const jenjang = rec.jenjang_kepegawaian || '-';
            const tmt = rec.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(rec.tmt_kenaikan_jenjang) || '-';
            const tst = rec.formatted_tst_kenaikan_jenjang || rec.formatted_tst_jenjang || formatTanggalIndo(rec.tst_kenaikan_jenjang || rec.tst_jenjang) || 'Sekarang / Tetap';

            const rincianRec = [];
            if (rec.no_sk) rincianRec.push(`No SK: ${rec.no_sk}`);
            rincianRec.push(`TMT: ${tmt} • TST: ${tst}`);

            list.push({
                id: `rec_${rec.id}`,
                no: no++,
                nama: `SK Jenjang ${jenjang}`,
                keterangan: rincianRec.join(' • '),
                document: rec.document,
                uploadCategory: 'sk_jenjang',
                recordType: 'record',
                recordId: rec.id,
                uploadTitle: `SK Jenjang ${jenjang}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

const dokumenJabatanList = computed(() => {
    const list = [];
    let no = 1;

    // Riwayat Jabatan (Terkoneksi Tab Penugasan / Jabatan)
    if (props.employee.riwayat_jabatan && props.employee.riwayat_jabatan.length > 0) {
        props.employee.riwayat_jabatan.forEach((pos) => {
            const jabatan = pos.jabatan || '-';
            const unit = pos.unit_kerja ? `Unit: ${pos.unit_kerja}` : '';
            const jenis = pos.jenis_kepegawaian ? `${pos.jenis_kepegawaian}` : '';
            const tmt = pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-';
            const tst = pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif';

            const rincianParts = [];
            if (unit) rincianParts.push(unit);
            if (jenis) rincianParts.push(jenis);
            if (pos.no_sk) rincianParts.push(`No SK: ${pos.no_sk}`);
            rincianParts.push(`TMT: ${tmt} • TST: ${tst}`);

            list.push({
                id: `pos_${pos.id}`,
                no: no++,
                nama: `SK Penugasan: ${jabatan}`,
                keterangan: rincianParts.join(' • '),
                document: pos.document,
                uploadCategory: 'sk_penugasan',
                recordType: 'position',
                recordId: pos.id,
                uploadTitle: `SK Penugasan ${jabatan}${pos.unit_kerja ? ' (' + pos.unit_kerja + ')' : ''}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

const dokumenPendidikanList = computed(() => {
    const list = [];
    let no = 1;

    // 1. Ijazah Formal dari Riwayat Pendidikan (Terkoneksi Tab Pendidikan)
    if (props.employee.riwayat_pendidikan && props.employee.riwayat_pendidikan.length > 0) {
        props.employee.riwayat_pendidikan.forEach((edu) => {
            const jenjang = edu.jenjang_pendidikan || 'Pendidikan';
            const jurusan = edu.jurusan ? ` - ${edu.jurusan}` : '';
            const instansi = edu.instansi_pendidikan || '-';
            const tahun = edu.tahun_lulus ? `Lulus ${edu.tahun_lulus}` : '';
            const biaya = edu.pembiayaan ? `Pembiayaan: ${edu.pembiayaan}` : '';

            const rincianParts = [instansi];
            if (tahun) rincianParts.push(tahun);
            if (biaya) rincianParts.push(biaya);

            list.push({
                id: `edu_${edu.id}`,
                no: no++,
                nama: `Ijazah ${jenjang}${jurusan}`,
                keterangan: rincianParts.join(' • '),
                document: edu.document,
                uploadCategory: 'ijazah',
                recordType: 'education',
                recordId: edu.id,
                uploadTitle: `Ijazah ${jenjang}${jurusan}`,
                isAdditional: false,
            });
        });
    }

    // 2. Ijazah / Sertifikat Tambahan Lainnya
    if (props.employee.dokumen_ijazah_tambahan && props.employee.dokumen_ijazah_tambahan.length > 0) {
        props.employee.dokumen_ijazah_tambahan.forEach((doc) => {
            list.push({
                id: `doc_${doc.id}`,
                no: no++,
                nama: doc.title || 'Ijazah Tambahan',
                keterangan: 'Dokumen pendidikan tambahan / sertifikat profesi',
                document: doc,
                uploadCategory: 'ijazah',
                recordType: null,
                recordId: null,
                uploadTitle: doc.title,
                isAdditional: true,
            });
        });
    }

    return list;
});

const dokumenPelatihanList = computed(() => {
    const list = [];
    let no = 1;

    // Sertifikat Pelatihan dan Pendidikan Nonformal (Terkoneksi Tab Pendidikan & Pelatihan)
    if (props.employee.riwayat_pelatihan && props.employee.riwayat_pelatihan.length > 0) {
        props.employee.riwayat_pelatihan.forEach((tr) => {
            const judul = tr.keahlian || tr.bidang || 'Pelatihan';
            const bidang = tr.bidang ? `Bidang: ${tr.bidang}` : '';
            const instansi = tr.instansi_penyelenggara || '-';
            const tahun = tr.tahun ? `Tahun ${tr.tahun}` : '';
            const biaya = tr.pembiayaan ? `Pembiayaan: ${tr.pembiayaan}` : '';

            const rincianParts = [instansi];
            if (bidang) rincianParts.push(bidang);
            if (tahun) rincianParts.push(tahun);
            if (biaya) rincianParts.push(biaya);

            list.push({
                id: `tr_${tr.id}`,
                no: no++,
                nama: `Sertifikat ${judul}`,
                keterangan: rincianParts.join(' • '),
                document: tr.document,
                uploadCategory: 'sertifikat_pelatihan',
                recordType: 'training',
                recordId: tr.id,
                uploadTitle: `Sertifikat ${judul}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

function openUploadModal({ category, recordType = null, recordId = null, title = '' }) {
    uploadCategory.value = category;
    uploadRecordType.value = recordType;
    uploadRecordId.value = recordId;
    uploadTitle.value = title;
    uploadFile.value = null;
    uploadFileError.value = '';
    showUploadModal.value = true;
}

function closeUploadModal() {
    if (isUploading.value) return;
    showUploadModal.value = false;
    uploadFile.value = null;
    uploadFileError.value = '';
}

function handleFileChange(event) {
    const file = event.target.files?.[0];
    validateAndSetFile(file);
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files?.[0];
    validateAndSetFile(file);
}

function validateAndSetFile(file) {
    if (!file) return;
    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
        uploadFileError.value = 'Format berkas tidak didukung. Harap pilih berkas PDF, JPG, PNG, atau WEBP.';
        uploadFile.value = null;
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        uploadFileError.value = 'Ukuran berkas melebihi batas maksimal 10 MB.';
        uploadFile.value = null;
        return;
    }
    uploadFileError.value = '';
    uploadFile.value = file;
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function submitDocument() {
    if (!uploadFile.value) {
        uploadFileError.value = 'Silakan pilih berkas dokumen terlebih dahulu.';
        return;
    }

    const formData = new FormData();
    formData.append('file', uploadFile.value);
    formData.append('category', uploadCategory.value);
    if (uploadRecordType.value) formData.append('record_type', uploadRecordType.value);
    if (uploadRecordId.value) formData.append('record_id', uploadRecordId.value);
    if (uploadTitle.value) formData.append('title', uploadTitle.value);

    isUploading.value = true;
    router.post(`/staff/${props.employee.id}/documents`, formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadFile.value = null;
            isUploading.value = false;
        },
        onError: (errors) => {
            isUploading.value = false;
            uploadFileError.value = Object.values(errors).join(' ') || 'Gagal mengunggah dokumen.';
        },
    });
}

function openPreview(doc) {
    if (!doc) return;
    previewDoc.value = doc;
    showPreviewModal.value = true;
}

function closePreviewModal() {
    showPreviewModal.value = false;
    previewDoc.value = null;
}

function deleteDocument(doc) {
    if (!doc) return;
    if (!confirm(`Apakah Anda yakin ingin menghapus dokumen "${doc.title}"?`)) {
        return;
    }
    router.delete(`/staff/${props.employee.id}/documents/${doc.id}`, {
        preserveScroll: true,
    });
}
function getInitials(name) {
    if (!name) return '??';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map(w => w[0]?.toUpperCase() || '')
        .join('');
}

function getAvatarStyle(emp) {
    if (emp?.jk === 'L') {
        return 'bg-blue-50 text-blue-800 border-blue-200/80';
    }
    return 'bg-rose-50 text-rose-800 border-rose-200/80';
}

function getJenjangBadgeClass(jenjang) {
    if (!jenjang) return 'bg-slate-100 text-slate-600 border border-slate-200';
    const j = jenjang.toUpperCase();
    if (j.startsWith('GTY')) {
        return 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold';
    }
    if (j.startsWith('GTT')) {
        return 'bg-teal-50 text-teal-800 border border-teal-200 font-semibold';
    }
    if (j.startsWith('PTY')) {
        return 'bg-indigo-50 text-indigo-800 border border-indigo-200 font-semibold';
    }
    if (j.startsWith('PTT')) {
        return 'bg-amber-50 text-amber-800 border border-amber-200 font-semibold';
    }
    return 'bg-slate-100 text-slate-700 border border-slate-200 font-medium';
}

function getStatusStyle(status) {
    const s = (status || 'Aktif').toLowerCase();
    if (s === 'aktif') {
        return {
            wrapper: 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold',
            dot: 'bg-emerald-600',
        };
    }
    if (s === 'pensiun') {
        return {
            wrapper: 'bg-amber-50 text-amber-800 border border-amber-200 font-bold',
            dot: 'bg-amber-500',
        };
    }
    return {
        wrapper: 'bg-rose-50 text-rose-700 border border-rose-200 font-bold',
        dot: 'bg-rose-600',
    };
}

const tabsContainerRef = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function checkScroll() {
    if (!tabsContainerRef.value) return;
    const { scrollLeft, scrollWidth, clientWidth } = tabsContainerRef.value;
    canScrollLeft.value = scrollLeft > 10;
    canScrollRight.value = scrollLeft + clientWidth < scrollWidth - 10;
}

function scrollTabs(direction) {
    if (!tabsContainerRef.value) return;
    const scrollAmount = 200;
    tabsContainerRef.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
}

function selectTab(id) {
    if (isEditing.value) {
        if (id === activeTab.value) return;
        alert('Anda sedang dalam mode edit. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum berpindah ke tab lain.');
        return;
    }
    if (isManagingRiwayat.value) {
        if (id === activeTab.value) return;
        alert('Anda sedang dalam mode kelola Riwayat Jenjang. Selesaikan (klik Selesai Kelola) terlebih dahulu sebelum berpindah ke tab lain.');
        return;
    }
    activeTab.value = id;
    nextTick(() => {
        const activeBtn = tabsContainerRef.value?.querySelector(`[data-tab-id="${id}"]`);
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        checkScroll();
    });
}

function handleBackClick(e) {
    if (isEditing.value) {
        if (!confirm('Perubahan yang sedang Anda edit belum disimpan. Yakin ingin keluar dari halaman edit?')) {
            e.preventDefault();
        }
    } else if (isManagingRiwayat.value) {
        if (!confirm('Anda sedang dalam mode kelola Riwayat Jenjang. Yakin ingin keluar dari halaman ini?')) {
            e.preventDefault();
        }
    }
}
</script>

<template>
    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop pb-24 md:pb-margin-desktop bg-white min-h-screen">
        <Head :title="`${isEditing ? 'Edit' : 'Detail'} Pegawai - ${employee.nama}`" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Back Button -->
            <div class="mb-5 flex items-center justify-between gap-4">
                <Link
                    href="/staff"
                    @click="handleBackClick"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[18px] text-slate-500">arrow_back</span>
                    <span>Kembali ke Data Pegawai</span>
                </Link>
            </div>

            <!-- Flash Notifications -->
            <div
                v-if="flashSuccess"
                class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">check_circle</span>
                    <div>
                        <h4 class="text-sm font-bold text-emerald-950">Berhasil</h4>
                        <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">{{ flashSuccess }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-emerald-700 hover:text-emerald-950 p-1 rounded-full hover:bg-emerald-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <div
                v-if="flashError"
                class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-2xl shrink-0 mt-0.5">error</span>
                    <div>
                        <h4 class="text-sm font-bold text-rose-950">Terjadi Kesalahan</h4>
                        <p class="text-xs text-rose-800 mt-0.5 leading-relaxed">{{ flashError }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-rose-600 hover:text-rose-950 p-1 rounded-full hover:bg-rose-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Header Card Identitas Profil Pegawai -->
            <div
                :class="[
                    'relative bg-gradient-to-br from-white via-emerald-50/25 to-slate-50/70 rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4',
                    isEmployeeDropdownOpen ? 'z-50' : 'z-20'
                ]"
            >
                <!-- Efek Cahaya Lembut Statis di Latar Belakang (Container rounded terpisah dengan overflow-hidden agar dropdown tidak terpotong) -->
                <div class="absolute inset-0 rounded-3xl overflow-hidden pointer-events-none">
                    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-gradient-to-br from-emerald-200/40 via-teal-100/25 to-transparent blur-3xl"></div>
                    <div class="absolute -bottom-20 -left-16 w-64 h-64 rounded-full bg-gradient-to-tr from-sky-200/35 via-emerald-100/20 to-transparent blur-3xl"></div>
                    <div class="absolute top-1/2 left-1/3 -translate-y-1/2 w-48 h-48 rounded-full bg-amber-100/30 blur-2xl"></div>
                </div>

                <div
                    :class="[
                        'relative flex items-center gap-4 sm:gap-5 min-w-0 flex-1',
                        isEmployeeDropdownOpen ? 'z-50' : 'z-10'
                    ]"
                >
                    <img
                        v-if="employee.foto"
                        :src="employee.foto"
                        :alt="employee.nama"
                        class="w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl object-cover border border-slate-200 shadow-2xs shrink-0"
                    />
                    <div
                        v-else
                        :class="[
                            'w-16 h-16 sm:w-20 sm:h-20 max-w-[64px] max-h-[64px] sm:max-w-[80px] sm:max-h-[80px] aspect-square rounded-2xl flex items-center justify-center font-bold text-xl sm:text-2xl border shadow-2xs shrink-0',
                            getAvatarStyle(employee)
                        ]"
                    >
                        <span>{{ getInitials(employee.nama) }}</span>
                    </div>

                    <div class="space-y-1.5 min-w-0 flex-1">
                        <!-- Dropdown Pemilih Pegawai Lain -->
                        <div class="relative" ref="employeeDropdownRef">
                            <button
                                v-if="allEmployees.length > 1"
                                type="button"
                                @click="toggleEmployeeDropdown"
                                class="inline-flex items-center gap-1.5 text-left text-xl sm:text-2xl font-extrabold text-slate-900 hover:text-emerald-950 transition-colors cursor-pointer group rounded-xl -ml-2 px-2 py-0.5 hover:bg-slate-100/80 active:scale-[0.99] max-w-full"
                                title="Klik untuk beralih memilih pegawai lain"
                            >
                                <span class="break-words leading-tight">{{ employee.nama }}</span>
                                <span
                                    class="material-symbols-outlined text-[24px] text-slate-400 group-hover:text-emerald-950 transition-transform duration-200 shrink-0"
                                    :class="{ 'rotate-180 text-emerald-950': isEmployeeDropdownOpen }"
                                >
                                    arrow_drop_down
                                </span>
                            </button>
                            <h1 v-else class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight break-words">
                                {{ employee.nama }}
                            </h1>

                            <!-- Floating Searchable Dropdown Menu (Desktop Tampilan Layar Lebar) -->
                            <div
                                v-if="isEmployeeDropdownOpen"
                                class="hidden sm:block absolute left-0 top-full mt-2 w-80 sm:w-96 max-w-[92vw] bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 p-2 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150"
                            >
                                <div class="px-2 pt-1 pb-2 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Pilih Pegawai Lain</span>
                                    <span class="text-[10px] text-slate-400">{{ filteredEmployees.length }} dari {{ allEmployees.length }} pegawai</span>
                                </div>

                                <!-- Search Input -->
                                <div class="relative p-1 my-1">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-slate-400">search</span>
                                    <input
                                        ref="employeeSearchInputRef"
                                        v-model="employeeSearchQuery"
                                        type="text"
                                        placeholder="Cari nama atau NIPY pegawai..."
                                        class="w-full pl-8 pr-7 py-1.5 rounded-xl border border-slate-300 bg-white text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                        @keydown.esc="closeEmployeeDropdown"
                                    />
                                    <button
                                        v-if="employeeSearchQuery"
                                        type="button"
                                        @click="employeeSearchQuery = ''"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">close</span>
                                    </button>
                                </div>

                                <!-- Scrollable List -->
                                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                                    <div
                                        v-for="emp in filteredEmployees"
                                        :key="emp.id"
                                        @click="selectEmployee(emp.id)"
                                        class="p-2.5 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors flex items-center justify-between gap-2.5"
                                        :class="{ 'bg-emerald-50 hover:bg-emerald-100/70': String(emp.id) === String(employee.id) }"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-slate-900 truncate" :class="{ 'text-emerald-950 font-extrabold': String(emp.id) === String(employee.id) }">
                                                {{ emp.nama }}
                                            </p>
                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 truncate mt-0.5">
                                                <span v-if="emp.nipy && canViewKepegawaian" class="font-mono">NIPY: {{ emp.nipy }}</span>
                                                <span v-if="emp.nipy && canViewKepegawaian && (emp.jabatan || emp.unit_kerja)">·</span>
                                                <span v-if="emp.jabatan || emp.unit_kerja" class="truncate">{{ emp.jabatan || emp.unit_kerja }}</span>
                                            </div>
                                        </div>
                                        <span v-if="String(emp.id) === String(employee.id)" class="material-symbols-outlined text-emerald-700 text-[18px] shrink-0">check</span>
                                    </div>

                                    <div v-if="filteredEmployees.length === 0" class="py-6 text-center text-xs text-slate-400 italic">
                                        Pegawai tidak ditemukan.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Bottom Sheet Pemilih Pegawai -->
                        <Teleport to="body">
                            <div
                                v-if="isEmployeeDropdownOpen"
                                class="mobile-employee-sheet fixed inset-0 z-[100] sm:hidden flex items-end justify-center bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                                @click.self="closeEmployeeDropdown"
                            >
                                <div class="w-full bg-white rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl border-t border-slate-200 animate-in slide-in-from-bottom duration-200 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
                                    <!-- Top Drag Indicator Bar -->
                                    <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto my-3 shrink-0"></div>

                                    <!-- Bottom Sheet Header -->
                                    <div class="px-5 pb-3 border-b border-slate-100 flex items-center justify-between shrink-0">
                                        <div>
                                            <h3 class="text-base font-extrabold text-slate-900">Pilih Pegawai Lain</h3>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ filteredEmployees.length }} dari {{ allEmployees.length }} pegawai</p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="closeEmployeeDropdown"
                                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">close</span>
                                        </button>
                                    </div>

                                    <!-- Search Input -->
                                    <div class="p-4 border-b border-slate-100 shrink-0">
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-slate-400">search</span>
                                            <input
                                                ref="mobileEmployeeSearchInputRef"
                                                v-model="employeeSearchQuery"
                                                type="text"
                                                placeholder="Cari nama atau NIPY pegawai..."
                                                class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-colors"
                                            />
                                            <button
                                                v-if="employeeSearchQuery"
                                                type="button"
                                                @click="employeeSearchQuery = ''"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">close</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Scrollable List Pegawai -->
                                    <div class="flex-1 overflow-y-auto p-3 space-y-1 divide-y divide-slate-50">
                                        <div
                                            v-for="emp in filteredEmployees"
                                            :key="emp.id"
                                            @click="selectEmployee(emp.id)"
                                            class="p-3 rounded-2xl transition-all cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]"
                                            :class="String(emp.id) === String(employee.id) ? 'bg-emerald-50 text-emerald-950 font-bold border border-emerald-200/60' : 'hover:bg-slate-50 text-slate-800'"
                                        >
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <img
                                                    v-if="emp.foto"
                                                    :src="emp.foto"
                                                    :alt="emp.nama"
                                                    class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0"
                                                />
                                                <div
                                                    v-else
                                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border"
                                                    :class="String(emp.id) === String(employee.id) ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                                >
                                                    {{ getInitials(emp.nama) }}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-bold truncate" :class="{ 'text-emerald-950': String(emp.id) === String(employee.id) }">
                                                        {{ emp.nama }}
                                                    </p>
                                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 truncate mt-0.5">
                                                        <span v-if="emp.nipy && canViewKepegawaian" class="font-mono">NIPY: {{ emp.nipy }}</span>
                                                        <span v-if="emp.nipy && canViewKepegawaian && (emp.jabatan || emp.unit_kerja)">·</span>
                                                        <span v-if="emp.jabatan || emp.unit_kerja" class="truncate">{{ emp.jabatan || emp.unit_kerja }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span v-if="String(emp.id) === String(employee.id)" class="material-symbols-outlined text-emerald-700 text-[22px] shrink-0">
                                                check_circle
                                            </span>
                                        </div>

                                        <div v-if="filteredEmployees.length === 0" class="py-12 text-center text-sm text-slate-400 italic">
                                            Pegawai tidak ditemukan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Teleport>

                        <!-- Info Jabatan & Unit Kerja (Tanpa NIPY) -->
                        <div v-if="employee.jabatan || employee.unit_kerja" class="flex items-center gap-1.5 text-xs sm:text-sm text-slate-500 font-medium flex-wrap">
                            <span v-if="employee.jabatan">{{ employee.jabatan }}</span>
                            <span v-if="employee.jabatan && employee.unit_kerja">•</span>
                            <span v-if="employee.unit_kerja">{{ employee.unit_kerja }}</span>
                        </div>

                        <!-- Badges Identitas: Warna Disamakan dengan Tabel -->
                        <div class="flex flex-wrap items-center gap-2 pt-0.5">
                            <!-- Badge Status Keaktifan (Warna Sama dengan Tabel) -->
                            <span
                                v-if="employee.status_keaktifan"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs shadow-2xs',
                                    getStatusStyle(employee.status_keaktifan).wrapper
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-2 h-2 rounded-full shrink-0',
                                        getStatusStyle(employee.status_keaktifan).dot
                                    ]"
                                ></span>
                                <span>{{ employee.status_keaktifan }}</span>
                            </span>

                            <!-- Badge Jenjang (Warna Sama dengan Tabel) -->
                            <span
                                v-if="employee.jenjang_kepegawaian"
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs shadow-2xs',
                                    getJenjangBadgeClass(employee.jenjang_kepegawaian)
                                ]"
                            >
                                {{ employee.jenjang_kepegawaian }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Navigation -->
            <div class="relative z-10 mb-5">
                <!-- Left Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 -translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-2"
                >
                    <div
                        v-if="canScrollLeft"
                        class="absolute left-0 top-0 bottom-0 z-20 flex items-center pr-6 pl-1 bg-gradient-to-r from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('left')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Gulir tab ke kiri"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>
                    </div>
                </Transition>

                <!-- Tabs Scroll Container -->
                <div
                    ref="tabsContainerRef"
                    @scroll="checkScroll"
                    class="overflow-x-auto no-scrollbar scroll-smooth py-1"
                >
                    <div class="bg-slate-100 p-1.5 rounded-2xl sm:rounded-full inline-flex items-center gap-1 border border-slate-200/90 min-w-max">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :data-tab-id="tab.id"
                            type="button"
                            @click="selectTab(tab.id)"
                            :disabled="(isEditing || isManagingRiwayat) && activeTab !== tab.id"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-150 shrink-0 whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : (isEditing || isManagingRiwayat)
                                        ? 'text-slate-400 opacity-40 cursor-not-allowed border border-transparent'
                                        : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent hover:bg-slate-200/60 cursor-pointer'
                            ]"
                            :title="(isEditing || isManagingRiwayat) && activeTab !== tab.id ? 'Selesaikan atau batalkan mode edit/kelola terlebih dahulu untuk berpindah tab' : ''"
                        >
                            <span class="material-symbols-outlined text-[18px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                            <span v-if="(isEditing || isManagingRiwayat) && activeTab !== tab.id" class="material-symbols-outlined text-[14px] text-slate-400">lock</span>
                        </button>
                    </div>
                </div>

                <!-- Right Scroll Arrow & Fade Hint -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <div
                        v-if="canScrollRight"
                        class="absolute right-0 top-0 bottom-0 z-20 flex items-center pl-6 pr-1 bg-gradient-to-l from-white via-white/95 to-transparent pointer-events-none"
                    >
                        <button
                            type="button"
                            @click="scrollTabs('right')"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white shadow-md border border-slate-200 text-slate-700 flex items-center justify-center hover:bg-slate-50 pointer-events-auto active:scale-95 transition-transform"
                            title="Ada tab lain di sebelah kanan"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    </div>
                </Transition>
            </div>

            <!-- Card Konten Tab Pegawai (Tab 1, 2, 3) -->
            <div
                v-if="['identitas', 'alamat', 'keluarga'].includes(activeTab)"
                class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs mb-8"
            >

                <!-- Warning NIK Kosong (View Mode Only) -->
                <div v-if="!isEditing && !employee.nik" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 mt-0.5">warning</span>
                    <div>
                        <h4 class="font-bold text-sm">NIK Belum Dimasukkan</h4>
                        <p class="text-xs mt-1 leading-relaxed text-error/90">
                            Pegawai ini belum memiliki data NIK. Relasi data (Riwayat Pendidikan dan Catatan Kepegawaian) sangat bergantung pada NIK sebagai pengenal unik. Klik tombol <strong>Edit Data</strong> untuk melengkapi NIK.
                        </p>
                    </div>
                </div>

                <!-- Validation Error Alert in Edit Mode -->
                <div v-if="isEditing && Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                    <div class="text-xs sm:text-sm">
                        <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>
                </div>

                <!-- TAB 1: IDENTITAS -->
                <div v-if="activeTab === 'identitas'" class="space-y-4">
                    <!-- Section Header Identitas Pegawai -->
                    <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Data Identitas Pegawai
                            </h4>
                        </div>

                        <!-- Tombol Edit di Pojok Kanan Atas Section -->
                        <div v-if="canEdit" class="flex items-center gap-2">
                            <!-- Saat View Mode: Tombol Edit Data -->
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="startEdit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                title="Edit data identitas pegawai"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                <span>Edit Data</span>
                            </button>

                            <!-- Saat Edit Mode: Tombol Batal & Simpan di Header Section -->
                            <template v-else>
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="form.processing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveChanges"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>Simpan</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- VIEW MODE -->
                    <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama }}</p>
                        </div>
                        <div v-if="canViewKepegawaian">
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIPY</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.nipy || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Gelar Depan</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.gelar_depan || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Gelar Belakang</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.gelar_belakang || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jenis Kelamin</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                {{ employee.jk === 'L' ? 'Laki-laki' : employee.jk === 'P' ? 'Perempuan' : '-' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tempat, Tanggal Lahir</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.ttl || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Usia</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                <span v-if="hitungUsia(employee.tanggal_lahir, employee.ttl) !== '-'" class="inline-flex items-center gap-1.5 text-emerald-800 font-bold">
                                    <span class="material-symbols-outlined text-[18px]">cake</span>
                                    <span>{{ hitungUsia(employee.tanggal_lahir, employee.ttl) }}</span>
                                </span>
                                <span v-else class="text-slate-400">-</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">NIK</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.nik || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">No. WhatsApp</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.no_wa || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.email || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kepemilikan BPJS</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kepemilikan_bpjs || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Penanggung BPJS</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.penanggung_bpjs || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Skill / Keahlian</label>
                            <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.skill || '-' }}</p>
                        </div>
                    </div>

                    <!-- EDIT MODE -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Gelar Depan
                            </label>
                            <input
                                v-model="form.gelar_depan"
                                type="text"
                                placeholder="Contoh: Dr., Ir., Ust."
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.gelar_depan }"
                            />
                            <div v-if="form.errors.gelar_depan" class="text-error text-xs mt-1">{{ form.errors.gelar_depan }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Gelar Belakang
                            </label>
                            <input
                                v-model="form.gelar_belakang"
                                type="text"
                                placeholder="Contoh: S.Pd, M.Pd, Lc."
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.gelar_belakang }"
                            />
                            <div v-if="form.errors.gelar_belakang" class="text-error text-xs mt-1">{{ form.errors.gelar_belakang }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nama Lengkap <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="form.nama"
                                type="text"
                                required
                                placeholder="Nama lengkap tanpa gelar"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.nama }"
                            />
                            <div v-if="form.errors.nama" class="text-error text-xs mt-1">{{ form.errors.nama }}</div>
                        </div>

                        <div v-if="canViewKepegawaian">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    NIPY (Nomor Induk Pegawai Yayasan)
                                </label>
                                <span v-if="canUpdateKepegawaian" class="inline-flex items-center gap-1 text-[10px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20">
                                    <span class="material-symbols-outlined text-[13px]">badge</span>
                                    Data Kepegawaian
                                </span>
                                <span v-else class="inline-flex items-center gap-1 text-[10px] font-semibold text-on-surface-variant/80 bg-slate-100 px-2 py-0.5 rounded-md border border-outline-variant/40">
                                    <span class="material-symbols-outlined text-[13px]">lock</span>
                                    Terkunci
                                </span>
                            </div>
                            <input
                                v-if="canUpdateKepegawaian"
                                v-model="form.nipy"
                                type="text"
                                placeholder="Nomor Induk Pegawai Yayasan"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 font-mono transition-all"
                                :class="{ 'border-error': form.errors.nipy }"
                            />
                            <input
                                v-else
                                :value="form.nipy"
                                type="text"
                                placeholder="Nomor Induk Pegawai Yayasan"
                                disabled
                                readonly
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-slate-100/90 border border-outline-variant/50 rounded-xl text-xs sm:text-sm text-slate-500 placeholder:text-outline cursor-not-allowed font-mono select-none"
                            />
                            <p v-if="!canUpdateKepegawaian" class="text-[11px] text-on-surface-variant/75 mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">lock</span>
                                Terkunci — hanya role dengan hak akses ubah Data Kepegawaian yang dapat mengubah NIPY.
                            </p>
                            <div v-if="form.errors.nipy" class="text-error text-xs mt-1">{{ form.errors.nipy }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                NIK (Nomor Induk Kependudukan)
                            </label>
                            <input
                                v-model="form.nik"
                                type="text"
                                placeholder="16 digit NIK"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-mono"
                                :class="{ 'border-error': form.errors.nik }"
                            />
                            <div v-if="form.errors.nik" class="text-error text-xs mt-1">{{ form.errors.nik }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Jenis Kelamin
                            </label>
                            <select
                                v-model="form.jk"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.jk }"
                            >
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                            <div v-if="form.errors.jk" class="text-error text-xs mt-1">{{ form.errors.jk }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nomor WhatsApp
                            </label>
                            <input
                                v-model="form.no_wa"
                                type="text"
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.no_wa }"
                            />
                            <div v-if="form.errors.no_wa" class="text-error text-xs mt-1">{{ form.errors.no_wa }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Tempat Lahir
                            </label>
                            <input
                                v-model="form.tempat_lahir"
                                type="text"
                                placeholder="Kota / Kabupaten Kelahiran"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.tempat_lahir }"
                            />
                            <div v-if="form.errors.tempat_lahir" class="text-error text-xs mt-1">{{ form.errors.tempat_lahir }}</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                    Tanggal Lahir
                                </label>
                                <span v-if="hitungUsia(form.tanggal_lahir) !== '-'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-outlined text-[13px]">cake</span>
                                    <span>Usia: {{ hitungUsia(form.tanggal_lahir) }}</span>
                                </span>
                            </div>
                            <input
                                v-model="form.tanggal_lahir"
                                type="date"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.tanggal_lahir }"
                            />
                            <div v-if="form.errors.tanggal_lahir" class="text-error text-xs mt-1">{{ form.errors.tanggal_lahir }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Email
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="nama@email.com"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.email }"
                            />
                            <div v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Kepemilikan BPJS
                            </label>
                            <select
                                v-model="form.kepemilikan_bpjs"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                            >
                                <option value="">-- Pilih --</option>
                                <option value="Ya">Ya</option>
                                <option value="Tidak">Tidak</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Penanggung BPJS
                            </label>
                            <select
                                v-model="form.penanggung_bpjs"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                                :class="{ 'border-error': form.errors.penanggung_bpjs }"
                            >
                                <option value="">-- Pilih Penanggung BPJS --</option>
                                <option value="Yayasan">Yayasan</option>
                                <option value="Pemerintah">Pemerintah</option>
                                <option value="Suami/Istri">Suami/Istri</option>
                                <option value="Mandiri">Mandiri</option>
                                <option
                                    v-if="form.penanggung_bpjs && !['Yayasan', 'Pemerintah', 'Suami/Istri', 'Mandiri'].includes(form.penanggung_bpjs)"
                                    :value="form.penanggung_bpjs"
                                >
                                    {{ form.penanggung_bpjs }}
                                </option>
                            </select>
                            <div v-if="form.errors.penanggung_bpjs" class="text-error text-xs mt-1">{{ form.errors.penanggung_bpjs }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Skill / Keahlian
                            </label>
                            <input
                                v-model="form.skill"
                                type="text"
                                placeholder="Contoh: Matematika, Desain Grafis, IT"
                                class="w-full px-3.5 py-2 sm:py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                :class="{ 'border-error': form.errors.skill }"
                            />
                            <div v-if="form.errors.skill" class="text-error text-xs mt-1">{{ form.errors.skill }}</div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: ALAMAT -->
                <div v-if="activeTab === 'alamat'" class="space-y-6">
                    <!-- Section Header Alamat Pegawai -->
                    <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Data Alamat Pegawai
                            </h4>
                        </div>

                        <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section (Satu-satunya posisi tombol aksi) -->
                        <div v-if="canEdit" class="flex items-center gap-2">
                            <!-- Saat View Mode: Tombol Edit Data -->
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="startEdit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                title="Edit data alamat pegawai"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                <span>Edit Data</span>
                            </button>

                            <!-- Saat Edit Mode: Tombol Batal & Simpan di Pojok Kanan Atas Section yang Sama -->
                            <template v-else>
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="form.processing"
                                    title="Batalkan perubahan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveChanges"
                                    class="inline-flex items-center gap-1.5 px-4 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="form.processing"
                                    title="Simpan perubahan data alamat"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- VIEW MODE -->
                    <template v-if="!isEditing">
                        <!-- Domisili -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700">location_on</span>
                                <h5>Alamat Domisili (KTP)</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                                <div class="md:col-span-2">
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Lengkap</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ getAlamatDomisili(employee) }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jalan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.jalan_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">RT / RW</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.rt_rw_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Dusun</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.dusun_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Desa / Kelurahan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.desa_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kecamatan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kecamatan_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kabupaten / Kota</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kab_domisili || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Provinsi</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.provinsi_domisili || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100 my-6" />

                        <!-- Tinggal -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700">home</span>
                                <h5>Alamat Tempat Tinggal Saat Ini</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                                <div class="md:col-span-2">
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Lengkap</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ getAlamatTinggal(employee) }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jalan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.jalan_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">RT / RW</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.rt_rw_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Dusun</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.dusun_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Desa / Kelurahan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.desa_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kecamatan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kecamatan_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kabupaten / Kota</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.kabupaten_tinggal || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Provinsi</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.provinsi_tinggal || '-' }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Kepemilikan Rumah</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.status_rumah || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT MODE -->
                    <template v-else>
                        <!-- Domisili Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">location_on</span>
                                <h4>Alamat Domisili (KTP)</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <!-- 1. Provinsi -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Provinsi</label>
                                        <span v-if="loadingDomisili.prov" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedProvDomisiliId"
                                        @change="onProvinsiDomisiliChange"
                                        :disabled="loadingDomisili.prov"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400"
                                        :class="{ 'border-error': form.errors.provinsi_domisili }"
                                    >
                                        <option value="">{{ loadingDomisili.prov ? 'Memuat Provinsi...' : '-- Pilih Provinsi --' }}</option>
                                        <option
                                            v-if="form.provinsi_domisili && !selectedProvDomisiliId"
                                            :value="form.provinsi_domisili"
                                        >
                                            {{ form.provinsi_domisili }} (Saat Ini)
                                        </option>
                                        <option v-for="prov in provincesList" :key="prov.id" :value="prov.id">
                                            {{ prov.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.provinsi_domisili" class="text-error text-xs mt-1">{{ form.errors.provinsi_domisili }}</div>
                                </div>

                                <!-- 2. Kabupaten / Kota -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Kabupaten / Kota</label>
                                        <span v-if="loadingDomisili.kab" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedKabDomisiliId"
                                        @change="onKabupatenDomisiliChange"
                                        :disabled="!selectedProvDomisiliId || loadingDomisili.kab"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.kab_domisili }"
                                    >
                                        <option value="">{{ !selectedProvDomisiliId ? '-- Pilih Provinsi Terlebih Dahulu --' : (loadingDomisili.kab ? 'Memuat Kab/Kota...' : '-- Pilih Kabupaten / Kota --') }}</option>
                                        <option
                                            v-if="form.kab_domisili && !selectedKabDomisiliId"
                                            :value="form.kab_domisili"
                                        >
                                            {{ form.kab_domisili }} (Saat Ini)
                                        </option>
                                        <option v-for="kab in regenciesDomisili" :key="kab.id" :value="kab.id">
                                            {{ kab.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.kab_domisili" class="text-error text-xs mt-1">{{ form.errors.kab_domisili }}</div>
                                </div>

                                <!-- 3. Kecamatan -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Kecamatan</label>
                                        <span v-if="loadingDomisili.kec" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedKecDomisiliId"
                                        @change="onKecamatanDomisiliChange"
                                        :disabled="!selectedKabDomisiliId || loadingDomisili.kec"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.kecamatan_domisili }"
                                    >
                                        <option value="">{{ !selectedKabDomisiliId ? '-- Pilih Kab/Kota Terlebih Dahulu --' : (loadingDomisili.kec ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --') }}</option>
                                        <option
                                            v-if="form.kecamatan_domisili && !selectedKecDomisiliId"
                                            :value="form.kecamatan_domisili"
                                        >
                                            {{ form.kecamatan_domisili }} (Saat Ini)
                                        </option>
                                        <option v-for="kec in districtsDomisili" :key="kec.id" :value="kec.id">
                                            {{ kec.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.kecamatan_domisili" class="text-error text-xs mt-1">{{ form.errors.kecamatan_domisili }}</div>
                                </div>

                                <!-- 4. Desa / Kelurahan -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Desa / Kelurahan</label>
                                        <span v-if="loadingDomisili.des" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedDesDomisiliId"
                                        @change="onDesaDomisiliChange"
                                        :disabled="!selectedKecDomisiliId || loadingDomisili.des"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.desa_domisili }"
                                    >
                                        <option value="">{{ !selectedKecDomisiliId ? '-- Pilih Kecamatan Terlebih Dahulu --' : (loadingDomisili.des ? 'Memuat Desa...' : '-- Pilih Desa / Kelurahan --') }}</option>
                                        <option
                                            v-if="form.desa_domisili && !selectedDesDomisiliId"
                                            :value="form.desa_domisili"
                                        >
                                            {{ form.desa_domisili }} (Saat Ini)
                                        </option>
                                        <option v-for="des in villagesDomisili" :key="des.id" :value="des.id">
                                            {{ des.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.desa_domisili" class="text-error text-xs mt-1">{{ form.errors.desa_domisili }}</div>
                                </div>

                                <!-- 5. Dusun -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun / Dukuh</label>
                                    <input
                                        v-model="form.dusun_domisili"
                                        type="text"
                                        placeholder="Nama dusun / dukuh / kampung"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <!-- 6. RT / RW -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_domisili"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <!-- 7. Jalan Domisili (span 2) -->
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jalan / Alamat Tambahan</label>
                                    <input
                                        v-model="form.jalan_domisili"
                                        type="text"
                                        placeholder="Nama jalan, nomor rumah / blok / gang"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tinggal Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                    <span class="material-symbols-outlined text-[18px]">home</span>
                                    <h4>Alamat Tempat Tinggal Saat Ini</h4>
                                </div>
                                <button
                                    type="button"
                                    @click="copyDomisiliToTinggal"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-primary/30 text-primary bg-primary/5 hover:bg-primary/10 text-xs font-semibold transition-all cursor-pointer self-start sm:self-auto"
                                >
                                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                    <span>Salin dari Domisili (KTP)</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <!-- 1. Provinsi -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Provinsi</label>
                                        <span v-if="loadingTinggal.prov" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedProvTinggalId"
                                        @change="onProvinsiTinggalChange"
                                        :disabled="loadingTinggal.prov"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400"
                                        :class="{ 'border-error': form.errors.provinsi_tinggal }"
                                    >
                                        <option value="">{{ loadingTinggal.prov ? 'Memuat Provinsi...' : '-- Pilih Provinsi --' }}</option>
                                        <option
                                            v-if="form.provinsi_tinggal && !selectedProvTinggalId"
                                            :value="form.provinsi_tinggal"
                                        >
                                            {{ form.provinsi_tinggal }} (Saat Ini)
                                        </option>
                                        <option v-for="prov in provincesList" :key="prov.id" :value="prov.id">
                                            {{ prov.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.provinsi_tinggal" class="text-error text-xs mt-1">{{ form.errors.provinsi_tinggal }}</div>
                                </div>

                                <!-- 2. Kabupaten / Kota -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Kabupaten / Kota</label>
                                        <span v-if="loadingTinggal.kab" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedKabTinggalId"
                                        @change="onKabupatenTinggalChange"
                                        :disabled="!selectedProvTinggalId || loadingTinggal.kab"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.kabupaten_tinggal }"
                                    >
                                        <option value="">{{ !selectedProvTinggalId ? '-- Pilih Provinsi Terlebih Dahulu --' : (loadingTinggal.kab ? 'Memuat Kab/Kota...' : '-- Pilih Kabupaten / Kota --') }}</option>
                                        <option
                                            v-if="form.kabupaten_tinggal && !selectedKabTinggalId"
                                            :value="form.kabupaten_tinggal"
                                        >
                                            {{ form.kabupaten_tinggal }} (Saat Ini)
                                        </option>
                                        <option v-for="kab in regenciesTinggal" :key="kab.id" :value="kab.id">
                                            {{ kab.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.kabupaten_tinggal" class="text-error text-xs mt-1">{{ form.errors.kabupaten_tinggal }}</div>
                                </div>

                                <!-- 3. Kecamatan -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Kecamatan</label>
                                        <span v-if="loadingTinggal.kec" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedKecTinggalId"
                                        @change="onKecamatanTinggalChange"
                                        :disabled="!selectedKabTinggalId || loadingTinggal.kec"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.kecamatan_tinggal }"
                                    >
                                        <option value="">{{ !selectedKabTinggalId ? '-- Pilih Kab/Kota Terlebih Dahulu --' : (loadingTinggal.kec ? 'Memuat Kecamatan...' : '-- Pilih Kecamatan --') }}</option>
                                        <option
                                            v-if="form.kecamatan_tinggal && !selectedKecTinggalId"
                                            :value="form.kecamatan_tinggal"
                                        >
                                            {{ form.kecamatan_tinggal }} (Saat Ini)
                                        </option>
                                        <option v-for="kec in districtsTinggal" :key="kec.id" :value="kec.id">
                                            {{ kec.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.kecamatan_tinggal" class="text-error text-xs mt-1">{{ form.errors.kecamatan_tinggal }}</div>
                                </div>

                                <!-- 4. Desa / Kelurahan -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-on-surface-variant">Desa / Kelurahan</label>
                                        <span v-if="loadingTinggal.des" class="text-[11px] text-primary flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px] animate-spin">sync</span>
                                            Memuat...
                                        </span>
                                    </div>
                                    <select
                                        v-model="selectedDesTinggalId"
                                        @change="onDesaTinggalChange"
                                        :disabled="!selectedKecTinggalId || loadingTinggal.des"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        :class="{ 'border-error': form.errors.desa_tinggal }"
                                    >
                                        <option value="">{{ !selectedKecTinggalId ? '-- Pilih Kecamatan Terlebih Dahulu --' : (loadingTinggal.des ? 'Memuat Desa...' : '-- Pilih Desa / Kelurahan --') }}</option>
                                        <option
                                            v-if="form.desa_tinggal && !selectedDesTinggalId"
                                            :value="form.desa_tinggal"
                                        >
                                            {{ form.desa_tinggal }} (Saat Ini)
                                        </option>
                                        <option v-for="des in villagesTinggal" :key="des.id" :value="des.id">
                                            {{ des.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.desa_tinggal" class="text-error text-xs mt-1">{{ form.errors.desa_tinggal }}</div>
                                </div>

                                <!-- 5. Dusun -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dusun / Dukuh</label>
                                    <input
                                        v-model="form.dusun_tinggal"
                                        type="text"
                                        placeholder="Nama dusun / dukuh / kampung"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <!-- 6. RT / RW -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">RT / RW</label>
                                    <input
                                        v-model="form.rt_rw_tinggal"
                                        type="text"
                                        placeholder="001/002"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <!-- 7. Jalan Tinggal -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jalan / Alamat Tambahan</label>
                                    <input
                                        v-model="form.jalan_tinggal"
                                        type="text"
                                        placeholder="Nama jalan, nomor rumah / kontrakan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <!-- 8. Status Kepemilikan Rumah -->
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Kepemilikan Rumah</label>
                                    <select
                                        v-model="form.status_rumah"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Status Rumah --</option>
                                        <option value="Milik Sendiri">Milik Sendiri</option>
                                        <option value="Sewa / Kontrak">Sewa / Kontrak</option>
                                        <option value="Menumpang / Orang Tua">Menumpang / Orang Tua</option>
                                        <option value="Rumah Dinas">Rumah Dinas</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- TAB 3: KELUARGA -->
                <div v-if="activeTab === 'keluarga'" class="space-y-6">
                    <!-- Section Header Keluarga Pegawai -->
                    <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Data Keluarga Pegawai
                            </h4>
                        </div>

                        <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section (Satu-satunya posisi tombol aksi) -->
                        <div v-if="canEdit" class="flex items-center gap-2">
                            <!-- Saat View Mode: Tombol Edit Data -->
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="startEdit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                title="Edit data keluarga pegawai"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                <span>Edit Data</span>
                            </button>

                            <!-- Saat Edit Mode: Tombol Batal & Simpan di Pojok Kanan Atas Section yang Sama -->
                            <template v-else>
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="form.processing"
                                    title="Batalkan perubahan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveChanges"
                                    class="inline-flex items-center gap-1.5 px-4 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="form.processing"
                                    title="Simpan perubahan data keluarga"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- VIEW MODE -->
                    <template v-if="!isEditing">
                        <!-- Pernikahan -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700">favorite</span>
                                <h5>Status Pernikahan & Pasangan</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Pernikahan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.status_pernikahan || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal Menikah</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.formatted_tanggal_menikah || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Suami/Istri</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TTL Suami/Istri</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.ttl_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Pekerjaan Suami/Istri</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.pekerjaan_suami_istri || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Jumlah Anak</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                        {{ employee.jumlah_anak !== null && employee.jumlah_anak !== undefined ? employee.jumlah_anak + ' anak' : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100 my-6" />

                        <!-- Orangtua -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700">family_restroom</span>
                                <h5>Data Orangtua</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Ibu</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_ibu || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nama Ayah</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.nama_ayah || '-' }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Alamat Orangtua</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.alamat_orangtua || '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100 my-6" />

                        <!-- Kontak Darurat -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 text-emerald-950 font-bold text-xs sm:text-sm">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700">contact_phone</span>
                                <h5>Kontak Darurat</h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 pl-1">
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Kontak</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1 font-mono">{{ employee.kontak_darurat || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Hubungan</label>
                                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">{{ employee.hubungan_kontak_darurat || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- EDIT MODE -->
                    <template v-else>
                        <!-- Pernikahan Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">favorite</span>
                                <h4>Status Pernikahan & Pasangan</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status Pernikahan</label>
                                    <select
                                        v-model="form.status_pernikahan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Tanggal Menikah</label>
                                    <input
                                        v-model="form.tanggal_menikah"
                                        type="date"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Suami / Istri</label>
                                    <input
                                        v-model="form.nama_suami_istri"
                                        type="text"
                                        placeholder="Nama lengkap pasangan"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">TTL Suami / Istri</label>
                                    <input
                                        v-model="form.ttl_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Solo, 10 Maret 1987"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pekerjaan Suami / Istri</label>
                                    <input
                                        v-model="form.pekerjaan_suami_istri"
                                        type="text"
                                        placeholder="Contoh: Guru / Wiraswasta / PNS"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Jumlah Anak</label>
                                    <input
                                        v-model="form.jumlah_anak"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Data Orangtua Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">family_restroom</span>
                                <h4>Data Orangtua</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ibu</label>
                                    <input
                                        v-model="form.nama_ibu"
                                        type="text"
                                        placeholder="Nama lengkap ibu"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nama Ayah</label>
                                    <input
                                        v-model="form.nama_ayah"
                                        type="text"
                                        placeholder="Nama lengkap ayah"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Alamat Orangtua</label>
                                    <input
                                        v-model="form.alamat_orangtua"
                                        type="text"
                                        placeholder="Alamat tempat tinggal orangtua"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Kontak Darurat Form -->
                        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-outline-variant/60 shadow-2xs space-y-4">
                            <div class="flex items-center gap-2 text-primary font-bold text-sm">
                                <span class="material-symbols-outlined text-[18px]">contact_phone</span>
                                <h4>Kontak Darurat</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Nomor Kontak Darurat</label>
                                    <input
                                        v-model="form.kontak_darurat"
                                        type="text"
                                        placeholder="Contoh: 082345678901"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-on-surface-variant mb-1">Hubungan</label>
                                    <input
                                        v-model="form.hubungan_kontak_darurat"
                                        type="text"
                                        placeholder="Contoh: Saudara Kandung / Paman / Kerabat"
                                        class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- TAB 4: KEPEGAWAIAN (2 Kontainer / Wadah Terpisah) -->
            <div v-else-if="activeTab === 'kepegawaian' && canViewKepegawaian" class="space-y-6 mb-8">
                <!-- Validation Error Alert in Edit Mode (Jika form kepegawaian ada error) -->
                <div v-if="isEditing && Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                    <div class="text-xs sm:text-sm">
                        <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Banner jika user tidak memiliki izin edit kepegawaian -->
                <div v-if="!canUpdateKepegawaian" class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[18px] text-amber-700 shrink-0">lock</span>
                    <div>
                        <strong>Mode Lihat Saja:</strong> Anda tidak memiliki hak akses untuk mengubah Data Kepegawaian. Data pada tab ini terkunci dan hanya dapat diubah oleh role yang memiliki hak akses Ubah Data Kepegawaian.
                    </div>
                </div>

                <!-- WADAH 1: INFORMASI UMUM KEPEGAWAIAN -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                    <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                    Informasi Umum Kepegawaian
                                </h4>
                            </div>
                            <div v-if="isManagingRiwayat" class="mt-1">
                                <span class="text-[10px] sm:text-[10.5px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[13px]">lock</span>
                                    <span>Terkunci saat mengelola riwayat</span>
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section (Terkunci jika sedang Kelola Riwayat) -->
                        <div v-if="canUpdateKepegawaian" class="flex items-center gap-2 shrink-0">
                            <!-- Saat View Mode: Tombol Edit Data -->
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="startEdit"
                                :disabled="isManagingRiwayat"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                                    isManagingRiwayat
                                        ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                        : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                                ]"
                                :title="isManagingRiwayat ? 'Selesaikan kelola riwayat jenjang terlebih dahulu untuk mengedit informasi umum' : 'Edit informasi umum kepegawaian'"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                                    {{ isManagingRiwayat ? 'lock' : 'edit_note' }}
                                </span>
                                <span>Edit Data</span>
                            </button>

                            <!-- Saat Edit Mode: Tombol Batal & Simpan di Pojok Kanan Atas Section yang Sama -->
                            <template v-else>
                                <button
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                    :disabled="form.processing"
                                    title="Batalkan perubahan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveChanges"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                                    :disabled="form.processing"
                                    title="Simpan perubahan informasi umum kepegawaian"
                                >
                                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                        <!-- View Mode Data Statis -->
                        <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <!-- TMT Pengangkatan -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TMT Pengangkatan</label>
                                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                    {{ employee.formatted_tmt_pengangkatan || formatTanggalIndo(employee.tmt_pengangkatan) || '-' }}
                                </p>
                            </div>

                            <!-- TST Pengangkatan -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TST Pengangkatan</label>
                                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                    {{ employee.formatted_tst_pengangkatan || formatTanggalIndo(employee.tst_pengangkatan) || 'Sekarang / Tetap' }}
                                </p>
                            </div>

                            <!-- Masa Kerja -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Masa Kerja</label>
                                <p class="text-sm sm:text-base font-semibold text-emerald-800 mt-1">
                                    {{ hitungMasaKerja(employee.tmt_pengangkatan, employee.tst_pengangkatan) || employee.masa_kerja || '-' }}
                                </p>
                            </div>

                            <!-- Status Keaktifan -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Keaktifan</label>
                                <div class="mt-1 flex items-center gap-2 flex-wrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs shadow-2xs',
                                            getStatusStyle(employee.status_keaktifan).wrapper
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-2 h-2 rounded-full shrink-0',
                                                getStatusStyle(employee.status_keaktifan).dot
                                            ]"
                                        ></span>
                                        <span>{{ employee.status_keaktifan || 'Aktif' }}</span>
                                    </span>
                                    <span v-if="employee.keterangan_tidak_aktif" class="text-xs text-slate-500 font-medium">
                                        ({{ employee.keterangan_tidak_aktif }})
                                    </span>
                                </div>
                            </div>

                            <!-- Keaktifan Dapodik -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Keaktifan Dapodik</label>
                                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                    {{ employee.keaktifan_dapodik || '-' }}
                                </p>
                            </div>

                            <!-- Unit Keaktifan Dapodik -->
                            <div>
                                <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Keaktifan Dapodik</label>
                                <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                                    {{ employee.unit_keaktifan_dapodik || '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Edit Mode Data Statis -->
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">TMT Pengangkatan</label>
                                <input
                                    v-model="form.tmt_pengangkatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">TST Pengangkatan</label>
                                <input
                                    v-model="form.tst_pengangkatan"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                />
                                <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan jika aktif / tetap bekerja</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Keaktifan</label>
                                <select
                                    v-model="form.status_keaktifan"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Non-Aktif">Non-Aktif</option>
                                    <option value="Pensiun">Pensiun</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tidak Aktif</label>
                                <input
                                    v-model="form.keterangan_tidak_aktif"
                                    type="text"
                                    placeholder="Keterangan jika non-aktif / pensiun"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keaktifan Dapodik</label>
                                <select
                                    v-model="form.keaktifan_dapodik"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="">-- Pilih Status Dapodik --</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Tidak Aktif">Tidak Aktif</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Unit Keaktifan Dapodik</label>
                                <input
                                    v-model="form.unit_keaktifan_dapodik"
                                    type="text"
                                    placeholder="Unit yang terdaftar pada Dapodik"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>
                        </div>
                    </div>

                <!-- WADAH 2: RIWAYAT JENJANG KEPEGAWAIAN -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                    <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="inline-flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                        Riwayat Jenjang Kepegawaian
                                    </h4>
                                </div>
                                <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                                    {{ currentRiwayatList.length }} Catatan
                                </span>
                            </div>
                            <div v-if="isEditing" class="mt-1">
                                <span class="text-[10px] sm:text-[10.5px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[12px] sm:text-[13px]">lock</span>
                                    <span>Terkunci saat mengedit informasi umum</span>
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Kelola di Pojok Kanan Atas Section (Terkunci jika sedang Edit Informasi Umum) -->
                        <div v-if="canUpdateKepegawaian" class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="!isManagingRiwayat"
                                type="button"
                                @click="startManageRiwayat"
                                :disabled="isEditing"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                                    isEditing
                                        ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                        : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                                ]"
                                :title="isEditing ? 'Selesaikan atau batalkan mode edit terlebih dahulu untuk mengelola riwayat jenjang' : 'Kelola riwayat jenjang kepegawaian'"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                                    {{ isEditing ? 'lock' : 'tune' }}
                                </span>
                                <span>Kelola</span>
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="finishManageRiwayat"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                                title="Selesai mengelola riwayat jenjang"
                            >
                                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                                <span>Selesai Kelola</span>
                            </button>
                        </div>
                    </div>

                        <!-- TAMPILAN DESKTOP (TABLE VIEW) -->
                        <div class="hidden md:block">
                            <!-- Tombol Tambah Riwayat di Atas Tabel (Saat Kelola Aktif) -->
                            <div v-if="isManagingRiwayat" class="mb-3.5 flex items-center justify-between gap-3">
                                <button
                                    type="button"
                                    @click="openAddRiwayatModal"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat</span>
                                </button>
                                <span class="text-xs text-slate-500 font-medium">
                                    Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                                </span>
                            </div>

                            <!-- Table Container -->
                            <div v-if="currentRiwayatList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                            <!-- Kolom Hapus di Kolom Pertama (Saat Kelola Aktif) -->
                                            <th v-if="isManagingRiwayat" class="px-3 py-3 text-center w-14">Hapus</th>
                                            <th class="px-4 py-3 text-center w-12">No</th>
                                            <th class="px-4 py-3 text-left">Jenjang Kepegawaian</th>
                                            <th class="px-4 py-3 text-left">No. SK</th>
                                            <th class="px-4 py-3 text-left">TMT Kenaikan Jenjang</th>
                                            <th class="px-4 py-3 text-left">TST Kenaikan Jenjang</th>
                                            <th class="px-4 py-3 text-left">Status Masa Berlaku</th>
                                            <th class="px-4 py-3 text-center">Berkas SK</th>
                                            <th v-if="isManagingRiwayat" class="px-4 py-3 text-center w-20">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="(record, idx) in currentRiwayatList"
                                            :key="record.id || idx"
                                            class="hover:bg-slate-50/80 transition-colors"
                                        >
                                            <!-- Tombol Hapus di Kolom Pertama -->
                                            <td v-if="isManagingRiwayat" class="px-3 py-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="deleteRiwayatRecord(idx)"
                                                    class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                                    title="Hapus riwayat jenjang ini"
                                                    :disabled="isSavingRiwayat"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>

                                            <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs shadow-2xs', getJenjangBadgeClass(record.jenjang_kepegawaian)]">
                                                    {{ record.jenjang_kepegawaian || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-800 font-medium font-mono text-xs">
                                                {{ record.no_sk || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">
                                                {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">
                                                {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span
                                                    v-if="!record.tst_kenaikan_jenjang && !record.tst_jenjang"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Aktif Berlaku
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                                >
                                                    Selesai
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div v-if="record.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(record.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat / Preview SK Jenjang"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-slate-400 text-xs">-</span>
                                            </td>

                                            <!-- Tombol Edit Baris (Saat Kelola Aktif) -->
                                            <td v-if="isManagingRiwayat" class="px-4 py-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openEditRiwayatModal(record, idx)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                                    title="Edit riwayat ini"
                                                    :disabled="isSavingRiwayat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">edit</span>
                                                    <span>Edit</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">work_history</span>
                                <p class="text-sm text-slate-500">Belum ada riwayat jenjang kepegawaian</p>
                            </div>
                        </div>

                        <!-- TAMPILAN MOBILE (CARD VIEW) -->
                        <div class="md:hidden">
                            <!-- Tombol Tambah Riwayat di Atas Kumpulan Card (Saat Kelola Aktif) -->
                            <div v-if="isManagingRiwayat" class="mb-4">
                                <button
                                    type="button"
                                    @click="openAddRiwayatModal"
                                    class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat</span>
                                </button>
                            </div>

                            <!-- Kumpulan Card Riwayat Jenjang -->
                            <div class="space-y-3">
                                <div
                                    v-for="(record, idx) in currentRiwayatList"
                                    :key="record.id || idx"
                                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                                >
                                    <!-- Header Card: Jenjang Badge & Status -->
                                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                        <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shadow-2xs', getJenjangBadgeClass(record.jenjang_kepegawaian)]">
                                            {{ record.jenjang_kepegawaian || 'Jenjang -' }}
                                        </span>
                                        <span
                                            v-if="!record.tst_kenaikan_jenjang && !record.tst_jenjang"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Aktif Berlaku
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                        >
                                            Selesai
                                        </span>
                                    </div>

                                    <!-- Isi Data Card -->
                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">No. SK</label>
                                            <p class="text-sm font-semibold text-slate-900 mt-0.5 font-mono">{{ record.no_sk || '-' }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TMT Kenaikan Jenjang</label>
                                                <p class="text-sm font-semibold text-slate-900 mt-0.5">
                                                    {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TST Kenaikan Jenjang</label>
                                                <p class="text-sm font-semibold text-slate-900 mt-0.5">
                                                    {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div v-if="record.document" class="pt-1">
                                            <button
                                                type="button"
                                                @click="openPreview(record.document)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                <span>Lihat SK</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tombol Hapus & Edit di Bawah Masing-masing Card (Saat Kelola Aktif) -->
                                    <div v-if="isManagingRiwayat" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                                        <button
                                            type="button"
                                            @click="deleteRiwayatRecord(idx)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                            :disabled="isSavingRiwayat"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditRiwayatModal(record, idx)"
                                            class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                            :disabled="isSavingRiwayat"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                            <span>Edit</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="currentRiwayatList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">work_history</span>
                                    <p class="text-sm text-slate-500">Belum ada riwayat jenjang kepegawaian</p>
                                </div>
                            </div>
                        </div>

                        <!-- POP-UP MODAL TAMBAH & EDIT RIWAYAT JENJANG -->
                        <Teleport to="body">
                            <div
                                v-if="isRiwayatModalOpen"
                                class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                                @click.self="closeRiwayatModal"
                            >
                                <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200">
                                    <!-- Modal Header -->
                                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[20px]">
                                                    {{ modalRiwayatMode === 'create' ? 'add_circle' : 'edit' }}
                                                </span>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-extrabold text-slate-900">
                                                    {{ modalRiwayatMode === 'create' ? 'Tambah Riwayat Jenjang' : 'Edit Riwayat Jenjang' }}
                                                </h3>
                                                <p class="text-xs text-slate-400 mt-0.5">
                                                    {{ modalRiwayatMode === 'create' ? 'Tambahkan catatan kenaikan jenjang kepegawaian baru' : 'Perbarui data catatan jenjang kepegawaian' }}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="closeRiwayatModal"
                                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">close</span>
                                        </button>
                                    </div>

                                    <!-- Modal Form Fields -->
                                    <form @submit.prevent="submitRiwayatModal" class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                Jenjang Kepegawaian <span class="text-rose-500">*</span>
                                            </label>
                                            <select
                                                v-model="riwayatModalForm.jenjang_kepegawaian"
                                                required
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                            >
                                                <option value="">-- Pilih Jenjang --</option>
                                                <option value="GTY">GTY (Guru Tetap Yayasan)</option>
                                                <option value="GTT">GTT (Guru Tidak Tetap)</option>
                                                <option value="PTY">PTY (Pegawai Tetap Yayasan)</option>
                                                <option value="PTT">PTT (Pegawai Tidak Tetap)</option>
                                                <option value="PJT">PJT</option>
                                                <option value="PTTY">PTTY</option>
                                                <option
                                                    v-if="riwayatModalForm.jenjang_kepegawaian && !['GTY', 'GTT', 'PTY', 'PTT', 'PJT', 'PTTY'].includes(riwayatModalForm.jenjang_kepegawaian)"
                                                    :value="riwayatModalForm.jenjang_kepegawaian"
                                                >
                                                    {{ riwayatModalForm.jenjang_kepegawaian }} (Lainnya)
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                Nomor SK
                                            </label>
                                            <input
                                                v-model="riwayatModalForm.no_sk"
                                                type="text"
                                                placeholder="Contoh: 045/SK-YUA/VII/2023"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                            />
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    TMT Kenaikan Jenjang <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="riwayatModalForm.tmt_kenaikan_jenjang"
                                                    type="date"
                                                    required
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                    TST Kenaikan Jenjang
                                                </label>
                                                <input
                                                    v-model="riwayatModalForm.tst_kenaikan_jenjang"
                                                    type="date"
                                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                />
                                                <span class="text-[10px] text-slate-400 mt-1 block">Kosongkan jika aktif / tetap</span>
                                            </div>
                                        </div>

                                        <!-- Modal Actions -->
                                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                                            <button
                                                type="button"
                                                @click="closeRiwayatModal"
                                                class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                                :disabled="isSavingRiwayat"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                                :disabled="isSavingRiwayat"
                                            >
                                                <span v-if="isSavingRiwayat" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                                <span v-else class="material-symbols-outlined text-[18px]">save</span>
                                                <span>{{ isSavingRiwayat ? 'Menyimpan...' : 'Simpan Riwayat' }}</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </Teleport>
                    </div>
                </div>

            <!-- Card Konten Tab Pegawai (Tab 5, 6, 7, 8) -->
            <div
                v-else
                class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs mb-8"
            >
                <!-- Validation Error Alert in Edit Mode -->
                <div v-if="isEditing && Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                    <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                    <div class="text-xs sm:text-sm">
                        <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                        <ul class="list-disc list-inside mt-1 space-y-0.5">
                            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                        </ul>
                    </div>
                </div>

                <!-- TAB 5: PENUGASAN (RIWAYAT JABATAN) -->
                <div v-if="activeTab === 'penugasan'" class="space-y-6 mb-8">
                    <!-- Validation Error Alert in Edit Mode (Jika form penugasan ada error) -->
                    <div v-if="isEditing && Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                        <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                        <div class="text-xs sm:text-sm">
                            <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                            <ul class="list-disc list-inside mt-1 space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- WADAH UTAMA: RIWAYAT PENUGASAN & JABATAN -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                        <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                            Riwayat Penugasan &amp; Jabatan
                                        </h4>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                                        {{ (isEditing ? form.riwayat_jabatan?.length : employee.riwayat_jabatan?.length) || 0 }} Jabatan
                                    </span>
                                </div>
                                <p v-if="isEditing" class="text-xs text-slate-500 mt-1">
                                    Ubah data langsung pada baris tabel (desktop) atau formulir kartu (mobile) di bawah ini
                                </p>
                            </div>

                            <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Saat View Mode: Tombol Edit Data -->
                                <button
                                    v-if="!isEditing"
                                    type="button"
                                    @click="startEdit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95"
                                    title="Edit riwayat penugasan &amp; jabatan"
                                >
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                    <span>Edit Data</span>
                                </button>

                                <!-- Saat Edit Mode: Tombol Batal & Simpan -->
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Batalkan perubahan"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveChanges"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Simpan perubahan riwayat penugasan"
                                    >
                                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 1. TAMPILAN DESKTOP (TABLE VIEW) -->
                        <div class="hidden md:block">
                            <!-- View Mode (Tabel Bersih Tanpa Border Kaku) -->
                            <div v-if="!isEditing">
                                <div v-if="employee.riwayat_jabatan && employee.riwayat_jabatan.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                    <table class="w-full text-xs sm:text-sm border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3 text-left">Unit Kerja</th>
                                                <th class="px-4 py-3 text-left">Jabatan</th>
                                                <th class="px-4 py-3 text-left">Jenis Kepegawaian</th>
                                                <th class="px-4 py-3 text-left">No. SK</th>
                                                <th class="px-4 py-3 text-left">TMT Jabatan</th>
                                                <th class="px-4 py-3 text-left">TST Jabatan</th>
                                                <th class="px-4 py-3 text-left">Status</th>
                                                <th class="px-4 py-3 text-center">Berkas SK</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="(pos, idx) in employee.riwayat_jabatan"
                                                :key="pos.id || idx"
                                                class="hover:bg-slate-50/80 transition-colors"
                                            >
                                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                                <td class="px-4 py-3.5">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                                        {{ pos.unit_kerja || '-' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 font-bold text-slate-900">{{ pos.jabatan || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-700 font-medium">{{ pos.jenis_kepegawaian || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-800 font-medium font-mono text-xs">{{ pos.no_sk || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-700">
                                                    {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                                </td>
                                                <td class="px-4 py-3.5 text-slate-700">
                                                    {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                                </td>
                                                <td class="px-4 py-3.5">
                                                    <span
                                                        v-if="!pos.tst_jabatan"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                    >
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                        Menjabat
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                                    >
                                                        Selesai
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <div v-if="pos.document" class="inline-flex items-center justify-center">
                                                        <button
                                                            type="button"
                                                            @click="openPreview(pos.document)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                                            title="Lihat / Preview SK Penugasan"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                            <span>Lihat SK</span>
                                                        </button>
                                                    </div>
                                                    <span v-else class="text-slate-400 text-xs">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                                    <p class="text-sm text-slate-500">Belum ada riwayat jabatan untuk pegawai ini</p>
                                </div>
                            </div>

                            <!-- Edit Mode (Form Tabel Interaktif) -->
                            <div v-else class="overflow-x-auto rounded-2xl border border-emerald-200/80 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse min-w-[850px]">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                            <th class="px-3 py-3 text-center w-12">No</th>
                                            <th class="px-3 py-3 text-left min-w-[170px]">Unit Kerja</th>
                                            <th class="px-3 py-3 text-left min-w-[170px]">Jabatan</th>
                                            <th class="px-3 py-3 text-left min-w-[160px]">Jenis Kepegawaian</th>
                                            <th class="px-3 py-3 text-left min-w-[150px]">No. SK</th>
                                            <th class="px-3 py-3 text-left min-w-[150px]">TMT Jabatan</th>
                                            <th class="px-3 py-3 text-left min-w-[150px]">TST Jabatan</th>
                                            <th class="px-3 py-3 text-center min-w-[110px]">Berkas SK</th>
                                            <th class="px-3 py-3 text-center w-16">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-if="!form.riwayat_jabatan || form.riwayat_jabatan.length === 0">
                                            <td colspan="9" class="p-8 text-center text-xs text-slate-500 italic">
                                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">assignment_late</span>
                                                Belum ada riwayat jabatan. Klik tombol di bawah untuk menambah riwayat jabatan baru.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(pos, idx) in form.riwayat_jabatan"
                                            :key="pos.id || idx"
                                            class="hover:bg-slate-50/70 transition-colors"
                                        >
                                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono font-bold">
                                                {{ idx + 1 }}
                                            </td>
                                            <!-- UNIT KERJA -->
                                            <td class="px-3 py-2.5">
                                                <select
                                                    v-model="pos.unit_kerja"
                                                    @change="onUnitKerjaChange(pos)"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                >
                                                    <option value="" disabled>-- Pilih Unit Kerja --</option>
                                                    <option v-for="dept in bidangList" :key="'dept_' + dept" :value="dept">
                                                        {{ dept }}
                                                    </option>
                                                    <option
                                                        v-if="pos.unit_kerja && !allMasterUnitNames.includes(pos.unit_kerja)"
                                                        :value="pos.unit_kerja"
                                                    >
                                                        {{ pos.unit_kerja }} (Lainnya)
                                                    </option>
                                                </select>
                                            </td>
                                            <!-- JABATAN -->
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center gap-1.5">
                                                    <select
                                                        v-if="!pos.custom_jabatan && getJabatanOptionsForUnit(pos.unit_kerja).length > 0"
                                                        v-model="pos.jabatan"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                    >
                                                        <option value="" disabled>-- Pilih Jabatan --</option>
                                                        <option
                                                            v-for="jbt in getJabatanOptionsForUnit(pos.unit_kerja)"
                                                            :key="jbt"
                                                            :value="jbt"
                                                        >
                                                            {{ jbt }}
                                                        </option>
                                                        <option
                                                            v-if="pos.jabatan && !getJabatanOptionsForUnit(pos.unit_kerja).includes(pos.jabatan)"
                                                            :value="pos.jabatan"
                                                        >
                                                            {{ pos.jabatan }} (Lainnya)
                                                        </option>
                                                    </select>

                                                    <input
                                                        v-else
                                                        v-model="pos.jabatan"
                                                        type="text"
                                                        :placeholder="pos.unit_kerja ? 'Ketik nama jabatan' : 'Pilih unit kerja dahulu'"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                    />

                                                    <button
                                                        v-if="getJabatanOptionsForUnit(pos.unit_kerja).length > 0"
                                                        type="button"
                                                        @click="pos.custom_jabatan = !pos.custom_jabatan"
                                                        class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-800 hover:bg-emerald-50 transition-colors shrink-0 cursor-pointer border border-slate-200"
                                                        :title="pos.custom_jabatan ? 'Kembali ke pilihan dropdown jabatan' : 'Ketik nama jabatan secara manual'"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">
                                                            {{ pos.custom_jabatan ? 'list' : 'edit' }}
                                                        </span>
                                                    </button>
                                                </div>
                                            </td>
                                            <!-- JENIS KEPEGAWAIAN -->
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="pos.jenis_kepegawaian"
                                                    type="text"
                                                    placeholder="Tenaga Pendidik / Kependidikan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <!-- NO SK -->
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="pos.no_sk"
                                                    type="text"
                                                    placeholder="Nomor SK"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 font-mono"
                                                />
                                            </td>
                                            <!-- TMT JABATAN -->
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="pos.tmt_jabatan"
                                                    type="date"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                />
                                            </td>
                                            <!-- TST JABATAN -->
                                            <td class="px-3 py-2.5">
                                                <div>
                                                    <input
                                                        v-model="pos.tst_jabatan"
                                                        type="date"
                                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                    />
                                                    <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan jika aktif</span>
                                                </div>
                                            </td>
                                            <!-- DOKUMEN -->
                                            <td class="px-3 py-2.5 text-center">
                                                <div v-if="pos.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(pos.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                                        title="Lihat / Preview SK Penugasan"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat SK</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-[11px] text-slate-400 italic">Tab Dokumen</span>
                                            </td>
                                            <!-- HAPUS -->
                                            <td class="px-3 py-2.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeRiwayatJabatanRow(idx)"
                                                    class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 flex items-center justify-center mx-auto transition-colors cursor-pointer"
                                                    title="Hapus baris riwayat jabatan ini"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- BARIS TAMBAH RIWAYAT JABATAN -->
                                        <tr class="bg-slate-50/70 hover:bg-slate-100/70 transition-colors">
                                            <td colspan="9" class="p-2.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="addRiwayatJabatanRow"
                                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 text-emerald-900 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                                    <span>Tambah Riwayat Jabatan</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 2. TAMPILAN MOBILE (CARD VIEW) -->
                        <div class="md:hidden">
                            <!-- View Mode (Card Bersih Modern) -->
                            <div v-if="!isEditing">
                                <div v-if="employee.riwayat_jabatan && employee.riwayat_jabatan.length > 0" class="space-y-3">
                                    <div
                                        v-for="(pos, idx) in employee.riwayat_jabatan"
                                        :key="pos.id || idx"
                                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                                    >
                                        <!-- Header Card: Unit Kerja & Status Menjabat/Selesai -->
                                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                                {{ pos.unit_kerja || 'Unit Kerja -' }}
                                            </span>
                                            <span
                                                v-if="!pos.tst_jabatan"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Menjabat
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                            >
                                                Selesai
                                            </span>
                                        </div>

                                        <!-- Isi Data Card -->
                                        <div class="space-y-2.5">
                                            <div>
                                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jabatan</label>
                                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ pos.jabatan || '-' }}</p>
                                            </div>

                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jenis Kepegawaian</label>
                                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ pos.jenis_kepegawaian || '-' }}</p>
                                                </div>
                                                <div>
                                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">No. SK</label>
                                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5 font-mono">{{ pos.no_sk || '-' }}</p>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TMT Jabatan</label>
                                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">
                                                        {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TST Jabatan</label>
                                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">
                                                        {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-if="pos.document" class="pt-1">
                                                <button
                                                    type="button"
                                                    @click="openPreview(pos.document)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                    <span>Lihat SK Penugasan</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                                    <p class="text-sm text-slate-500">Belum ada riwayat jabatan untuk pegawai ini</p>
                                </div>
                            </div>

                            <!-- Edit Mode on Mobile (Form Kartu Interaktif) -->
                            <div v-else class="space-y-4">
                                <button
                                    type="button"
                                    @click="addRiwayatJabatanRow"
                                    class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Jabatan</span>
                                </button>

                                <div v-if="!form.riwayat_jabatan || form.riwayat_jabatan.length === 0" class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">assignment_late</span>
                                    <p class="text-xs text-slate-500">Belum ada jabatan. Klik tombol di atas untuk menambah.</p>
                                </div>

                                <div
                                    v-for="(pos, idx) in form.riwayat_jabatan"
                                    :key="pos.id || idx"
                                    class="bg-slate-50/80 rounded-2xl border border-slate-200 p-4 space-y-3 shadow-2xs"
                                >
                                    <!-- Card Header dengan No dan Tombol Hapus -->
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jabatan #{{ idx + 1 }}</span>
                                        <button
                                            type="button"
                                            @click="removeRiwayatJabatanRow(idx)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>

                                    <!-- Unit Kerja -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Unit Kerja</label>
                                        <select
                                            v-model="pos.unit_kerja"
                                            @change="onUnitKerjaChange(pos)"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                        >
                                            <option value="" disabled>-- Pilih Unit Kerja --</option>
                                            <option v-for="dept in bidangList" :key="'dept_m_' + dept" :value="dept">
                                                {{ dept }}
                                            </option>
                                            <option
                                                v-if="pos.unit_kerja && !allMasterUnitNames.includes(pos.unit_kerja)"
                                                :value="pos.unit_kerja"
                                            >
                                                {{ pos.unit_kerja }} (Lainnya)
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Jabatan -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jabatan</label>
                                        <div class="flex items-center gap-1.5">
                                            <select
                                                v-if="!pos.custom_jabatan && getJabatanOptionsForUnit(pos.unit_kerja).length > 0"
                                                v-model="pos.jabatan"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                            >
                                                <option value="" disabled>-- Pilih Jabatan --</option>
                                                <option
                                                    v-for="jbt in getJabatanOptionsForUnit(pos.unit_kerja)"
                                                    :key="'m_jbt_' + jbt"
                                                    :value="jbt"
                                                >
                                                    {{ jbt }}
                                                </option>
                                                <option
                                                    v-if="pos.jabatan && !getJabatanOptionsForUnit(pos.unit_kerja).includes(pos.jabatan)"
                                                    :value="pos.jabatan"
                                                >
                                                    {{ pos.jabatan }} (Lainnya)
                                                </option>
                                            </select>

                                            <input
                                                v-else
                                                v-model="pos.jabatan"
                                                type="text"
                                                :placeholder="pos.unit_kerja ? 'Ketik nama jabatan' : 'Pilih unit kerja dahulu'"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                            />

                                            <button
                                                v-if="getJabatanOptionsForUnit(pos.unit_kerja).length > 0"
                                                type="button"
                                                @click="pos.custom_jabatan = !pos.custom_jabatan"
                                                class="p-2 rounded-xl text-slate-600 hover:text-emerald-800 hover:bg-emerald-50 border border-slate-200 transition-colors shrink-0 cursor-pointer"
                                                :title="pos.custom_jabatan ? 'Kembali ke pilihan dropdown jabatan' : 'Ketik manual'"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">
                                                    {{ pos.custom_jabatan ? 'list' : 'edit' }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Jenis Kepegawaian & No. SK -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Kepegawaian</label>
                                            <input
                                                v-model="pos.jenis_kepegawaian"
                                                type="text"
                                                placeholder="Tenaga Pendidik / Kependidikan"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor SK</label>
                                            <input
                                                v-model="pos.no_sk"
                                                type="text"
                                                placeholder="Nomor SK"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 font-mono"
                                            />
                                        </div>
                                    </div>

                                    <!-- TMT & TST Jabatan -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">TMT Jabatan</label>
                                            <input
                                                v-model="pos.tmt_jabatan"
                                                type="date"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">TST Jabatan</label>
                                            <input
                                                v-model="pos.tst_jabatan"
                                                type="date"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                            />
                                            <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan jika aktif</span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addRiwayatJabatanRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 text-emerald-900 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Jabatan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: PENDIDIKAN FORMAL & NONFORMAL -->
                <div v-if="activeTab === 'pendidikan'" class="space-y-6 mb-8">
                    <!-- Validation Error Alert in Edit Mode -->
                    <div v-if="isEditing && Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                        <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                        <div class="text-xs sm:text-sm">
                            <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                            <ul class="list-disc list-inside mt-1 space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 1. KOTAK SECTION: RIWAYAT PENDIDIKAN FORMAL -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                        <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                            Riwayat Pendidikan Formal
                                        </h4>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                                        {{ (isEditing ? form.riwayat_pendidikan?.length : employee.riwayat_pendidikan?.length) || 0 }} Riwayat
                                    </span>
                                </div>
                                <p v-if="isEditing" class="text-xs text-slate-500 mt-1">
                                    Ubah data pendidikan pada baris tabel (desktop) atau formulir kartu (mobile) di bawah ini
                                </p>
                            </div>

                            <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    v-if="!isEditing"
                                    type="button"
                                    @click="startEdit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95"
                                    title="Edit data riwayat pendidikan"
                                >
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                    <span>Edit Data</span>
                                </button>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Batalkan perubahan"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveChanges"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Simpan perubahan riwayat pendidikan"
                                    >
                                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 1.A. DESKTOP VIEW: TABEL PENDIDIKAN FORMAL -->
                        <div class="hidden md:block">
                            <!-- View Mode -->
                            <div v-if="!isEditing">
                                <div v-if="employee.riwayat_pendidikan && employee.riwayat_pendidikan.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                    <table class="w-full text-xs sm:text-sm border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3 text-left">Jenjang</th>
                                                <th class="px-4 py-3 text-left">Jurusan</th>
                                                <th class="px-4 py-3 text-left">Instansi Pendidikan</th>
                                                <th class="px-4 py-3 text-center">Tahun Lulus</th>
                                                <th class="px-4 py-3 text-left">Pembiayaan</th>
                                                <th class="px-4 py-3 text-center">Berkas Ijazah</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="(edu, idx) in employee.riwayat_pendidikan"
                                                :key="edu.id || idx"
                                                class="hover:bg-slate-50/80 transition-colors"
                                            >
                                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                                <td class="px-4 py-3.5">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                                        {{ edu.jenjang_pendidikan || '-' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-slate-800 font-medium">{{ edu.jurusan || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-800 font-semibold">{{ edu.instansi_pendidikan || '-' }}</td>
                                                <td class="px-4 py-3.5 text-center font-bold text-slate-900 font-mono">{{ edu.tahun_lulus || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-600 text-xs">{{ edu.pembiayaan || '-' }}</td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <div v-if="edu.document" class="inline-flex items-center justify-center">
                                                        <button
                                                            type="button"
                                                            @click="openPreview(edu.document)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                            title="Lihat Berkas Ijazah"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                            <span>Lihat Ijazah</span>
                                                        </button>
                                                    </div>
                                                    <span v-else class="text-slate-400 text-xs">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">school</span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat pendidikan formal</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Klik tombol Edit Data di atas untuk menambahkan riwayat pendidikan</p>
                                </div>
                            </div>

                            <!-- Edit Mode (Desktop Table) -->
                            <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold text-[11px] uppercase tracking-wider">
                                            <th class="px-3 py-3 text-center w-12">No</th>
                                            <th class="px-3 py-3 text-left w-36">Jenjang</th>
                                            <th class="px-3 py-3 text-left">Jurusan</th>
                                            <th class="px-3 py-3 text-left">Instansi / Universitas</th>
                                            <th class="px-3 py-3 text-center w-28">Tahun Lulus</th>
                                            <th class="px-3 py-3 text-left w-36">Pembiayaan</th>
                                            <th class="px-3 py-3 text-center w-28">Berkas</th>
                                            <th class="px-3 py-3 text-center w-14">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-if="!form.riwayat_pendidikan || form.riwayat_pendidikan.length === 0">
                                            <td colspan="8" class="p-8 text-center text-xs text-slate-500 italic">
                                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">school</span>
                                                Belum ada riwayat pendidikan formal. Klik tombol di bawah untuk menambah data baru.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(edu, idx) in form.riwayat_pendidikan"
                                            :key="edu.id || idx"
                                            class="hover:bg-slate-50/60 transition-colors"
                                        >
                                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono font-bold">{{ idx + 1 }}</td>
                                            <td class="px-3 py-2.5">
                                                <select
                                                    v-model="edu.jenjang_pendidikan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                                >
                                                    <option value="">-- Pilih --</option>
                                                    <option value="SD">SD / Sederajat</option>
                                                    <option value="SMP">SMP / MTs</option>
                                                    <option value="SMA">SMA / SMK / MA</option>
                                                    <option value="D1">D1</option>
                                                    <option value="D2">D2</option>
                                                    <option value="D3">D3</option>
                                                    <option value="D4">D4</option>
                                                    <option value="S1">S1 (Sarjana)</option>
                                                    <option value="S2">S2 (Magister)</option>
                                                    <option value="S3">S3 (Doktor)</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="edu.jurusan"
                                                    type="text"
                                                    placeholder="Contoh: Pendidikan Matematika"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="edu.instansi_pendidikan"
                                                    type="text"
                                                    placeholder="Contoh: UNS Surakarta"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="edu.tahun_lulus"
                                                    type="text"
                                                    placeholder="2018"
                                                    class="w-full px-2.5 py-2 text-center bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-mono focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="edu.pembiayaan"
                                                    type="text"
                                                    placeholder="Mandiri / Beasiswa"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <div v-if="edu.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(edu.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat Berkas Ijazah"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-[11px] text-slate-400 italic">Tab Dokumen</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeEducationRow(idx)"
                                                    class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer active:scale-95"
                                                    title="Hapus baris ini"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="p-3 bg-slate-50 border-t border-slate-200">
                                    <button
                                        type="button"
                                        @click="addEducationRow"
                                        class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-white hover:bg-emerald-50 text-emerald-900 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                        <span>Tambah Riwayat Pendidikan Formal</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 1.B. MOBILE CARD VIEW: PENDIDIKAN FORMAL -->
                        <div class="md:hidden space-y-3">
                            <!-- Mobile View Mode -->
                            <div v-if="!isEditing" class="space-y-3">
                                <div
                                    v-for="(edu, idx) in employee.riwayat_pendidikan"
                                    :key="edu.id || idx"
                                    class="bg-slate-50/70 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-200/60">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                            {{ edu.jenjang_pendidikan || '-' }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-700 font-mono bg-white px-2 py-0.5 rounded-md border border-slate-200">
                                            Lulus: {{ edu.tahun_lulus || '-' }}
                                        </span>
                                    </div>
                                    <div class="space-y-1.5 text-xs">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Instansi / Universitas</span>
                                            <span class="font-bold text-slate-900 text-sm">{{ edu.instansi_pendidikan || '-' }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 pt-1">
                                            <div>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Jurusan</span>
                                                <span class="text-slate-700 font-medium">{{ edu.jurusan || '-' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pembiayaan</span>
                                                <span class="text-slate-700">{{ edu.pembiayaan || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="edu.document" class="pt-2 border-t border-slate-200/60 flex items-center justify-end">
                                        <button
                                            type="button"
                                            @click="openPreview(edu.document)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>Lihat Ijazah</span>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="!employee.riwayat_pendidikan || employee.riwayat_pendidikan.length === 0" class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">school</span>
                                    <p class="text-xs font-semibold text-slate-600">Belum ada riwayat pendidikan formal</p>
                                </div>
                            </div>

                            <!-- Mobile Edit Mode (Card Form) -->
                            <div v-else class="space-y-3">
                                <div
                                    v-for="(edu, idx) in form.riwayat_pendidikan"
                                    :key="edu.id || idx"
                                    class="bg-slate-50/60 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                            Pendidikan #{{ idx + 1 }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeEducationRow(idx)"
                                            class="text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1 p-1 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenjang</label>
                                                <select
                                                    v-model="edu.jenjang_pendidikan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 font-semibold focus:outline-none focus:border-emerald-600"
                                                >
                                                    <option value="">Pilih</option>
                                                    <option value="SD">SD</option>
                                                    <option value="SMP">SMP</option>
                                                    <option value="SMA">SMA</option>
                                                    <option value="D1">D1</option>
                                                    <option value="D2">D2</option>
                                                    <option value="D3">D3</option>
                                                    <option value="D4">D4</option>
                                                    <option value="S1">S1</option>
                                                    <option value="S2">S2</option>
                                                    <option value="S3">S3</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun Lulus</label>
                                                <input
                                                    v-model="edu.tahun_lulus"
                                                    type="text"
                                                    placeholder="2018"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 font-mono focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Instansi / Kampus</label>
                                            <input
                                                v-model="edu.instansi_pendidikan"
                                                type="text"
                                                placeholder="Nama instansi pendidikan"
                                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                            />
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jurusan</label>
                                                <input
                                                    v-model="edu.jurusan"
                                                    type="text"
                                                    placeholder="Jurusan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pembiayaan</label>
                                                <input
                                                    v-model="edu.pembiayaan"
                                                    type="text"
                                                    placeholder="Mandiri / Beasiswa"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addEducationRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 text-emerald-900 font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Pendidikan Formal</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. KOTAK SECTION: RIWAYAT PELATIHAN DAN PENDIDIKAN NONFORMAL -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                        <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-teal-600 shrink-0"></span>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                            Riwayat Pelatihan &amp; Pendidikan Nonformal
                                        </h4>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                                        {{ (isEditing ? form.riwayat_pelatihan?.length : employee.riwayat_pelatihan?.length) || 0 }} Catatan
                                    </span>
                                </div>
                                <p v-if="isEditing" class="text-xs text-slate-500 mt-1">
                                    Ubah data pelatihan pada baris tabel (desktop) atau formulir kartu (mobile) di bawah ini
                                </p>
                            </div>

                            <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    v-if="!isEditing"
                                    type="button"
                                    @click="startEdit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95"
                                    title="Edit data pelatihan &amp; nonformal"
                                >
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                                    <span>Edit Data</span>
                                </button>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Batalkan perubahan"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveChanges"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Simpan perubahan pelatihan"
                                    >
                                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 2.A. DESKTOP VIEW: TABEL PELATIHAN -->
                        <div class="hidden md:block">
                            <!-- View Mode -->
                            <div v-if="!isEditing">
                                <div v-if="employee.riwayat_pelatihan && employee.riwayat_pelatihan.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                    <table class="w-full text-xs sm:text-sm border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3 text-left">Bidang</th>
                                                <th class="px-4 py-3 text-left">Keahlian</th>
                                                <th class="px-4 py-3 text-left">Instansi Penyelenggara</th>
                                                <th class="px-4 py-3 text-center">Tahun</th>
                                                <th class="px-4 py-3 text-left">Pembiayaan</th>
                                                <th class="px-4 py-3 text-center">Berkas Sertifikat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="(tr, idx) in employee.riwayat_pelatihan"
                                                :key="tr.id || idx"
                                                class="hover:bg-slate-50/80 transition-colors"
                                            >
                                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                                <td class="px-4 py-3.5">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80 shadow-2xs">
                                                        {{ tr.bidang || '-' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-slate-800 font-bold">{{ tr.keahlian || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-700 font-medium">{{ tr.instansi_penyelenggara || '-' }}</td>
                                                <td class="px-4 py-3.5 text-center font-bold text-slate-900 font-mono">{{ tr.tahun || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-600 text-xs">{{ tr.pembiayaan || '-' }}</td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <div v-if="tr.document" class="inline-flex items-center justify-center">
                                                        <button
                                                            type="button"
                                                            @click="openPreview(tr.document)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                            title="Lihat Berkas Sertifikat"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                            <span>Lihat</span>
                                                        </button>
                                                    </div>
                                                    <span v-else class="text-slate-400 text-xs">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">workspace_premium</span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat pelatihan dan nonformal</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Klik tombol Edit Data di atas untuk menambahkan riwayat pelatihan</p>
                                </div>
                            </div>

                            <!-- Edit Mode (Desktop Table) -->
                            <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold text-[11px] uppercase tracking-wider">
                                            <th class="px-3 py-3 text-center w-12">No</th>
                                            <th class="px-3 py-3 text-left w-40">Bidang</th>
                                            <th class="px-3 py-3 text-left">Keahlian</th>
                                            <th class="px-3 py-3 text-left">Instansi Penyelenggara</th>
                                            <th class="px-3 py-3 text-center w-28">Tahun</th>
                                            <th class="px-3 py-3 text-left w-36">Pembiayaan</th>
                                            <th class="px-3 py-3 text-center w-28">Berkas</th>
                                            <th class="px-3 py-3 text-center w-14">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-if="!form.riwayat_pelatihan || form.riwayat_pelatihan.length === 0">
                                            <td colspan="8" class="p-8 text-center text-xs text-slate-500 italic">
                                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">workspace_premium</span>
                                                Belum ada riwayat pelatihan. Klik tombol di bawah untuk menambah riwayat baru.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(tr, idx) in form.riwayat_pelatihan"
                                            :key="tr.id || idx"
                                            class="hover:bg-slate-50/60 transition-colors"
                                        >
                                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono font-bold">{{ idx + 1 }}</td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="tr.bidang"
                                                    type="text"
                                                    placeholder="IT / Pedagogik"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="tr.keahlian"
                                                    type="text"
                                                    placeholder="Desain Kurikulum"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="tr.instansi_penyelenggara"
                                                    type="text"
                                                    placeholder="Balai Diklat / Kemdikbud"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="tr.tahun"
                                                    type="text"
                                                    placeholder="2024"
                                                    class="w-full px-2.5 py-2 text-center bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 font-mono focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="tr.pembiayaan"
                                                    type="text"
                                                    placeholder="Mandiri / Yayasan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <div v-if="tr.document" class="inline-flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        @click="openPreview(tr.document)"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                        title="Lihat Berkas Sertifikat"
                                                    >
                                                        <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                        <span>Lihat</span>
                                                    </button>
                                                </div>
                                                <span v-else class="text-[11px] text-slate-400 italic">Tab Dokumen</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeTrainingRow(idx)"
                                                    class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer active:scale-95"
                                                    title="Hapus baris ini"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="p-3 bg-slate-50 border-t border-slate-200">
                                    <button
                                        type="button"
                                        @click="addTrainingRow"
                                        class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-white hover:bg-emerald-50 text-emerald-900 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                        <span>Tambah Riwayat Pelatihan dan Nonformal</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2.B. MOBILE CARD VIEW: PELATIHAN -->
                        <div class="md:hidden space-y-3">
                            <!-- View Mode -->
                            <div v-if="!isEditing" class="space-y-3">
                                <div
                                    v-for="(tr, idx) in employee.riwayat_pelatihan"
                                    :key="tr.id || idx"
                                    class="bg-slate-50/70 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-200/60">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80 shadow-2xs">
                                            {{ tr.bidang || '-' }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-700 font-mono bg-white px-2 py-0.5 rounded-md border border-slate-200">
                                            Tahun: {{ tr.tahun || '-' }}
                                        </span>
                                    </div>
                                    <div class="space-y-1.5 text-xs">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keahlian</span>
                                            <span class="font-bold text-slate-900 text-sm">{{ tr.keahlian || '-' }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 pt-1">
                                            <div>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Penyelenggara</span>
                                                <span class="text-slate-700 font-medium">{{ tr.instansi_penyelenggara || '-' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pembiayaan</span>
                                                <span class="text-slate-700">{{ tr.pembiayaan || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="tr.document" class="pt-2 border-t border-slate-200/60 flex items-center justify-end">
                                        <button
                                            type="button"
                                            @click="openPreview(tr.document)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>Lihat Sertifikat</span>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="!employee.riwayat_pelatihan || employee.riwayat_pelatihan.length === 0" class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">workspace_premium</span>
                                    <p class="text-xs font-semibold text-slate-600">Belum ada riwayat pelatihan dan nonformal</p>
                                </div>
                            </div>

                            <!-- Mobile Edit Mode (Card Form) -->
                            <div v-else class="space-y-3">
                                <div
                                    v-for="(tr, idx) in form.riwayat_pelatihan"
                                    :key="tr.id || idx"
                                    class="bg-slate-50/60 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                            Pelatihan #{{ idx + 1 }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeTrainingRow(idx)"
                                            class="text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1 p-1 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Bidang</label>
                                                <input
                                                    v-model="tr.bidang"
                                                    type="text"
                                                    placeholder="IT / Bahasa"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun</label>
                                                <input
                                                    v-model="tr.tahun"
                                                    type="text"
                                                    placeholder="2024"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 font-mono focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keahlian / Materi</label>
                                            <input
                                                v-model="tr.keahlian"
                                                type="text"
                                                placeholder="Nama keahlian / materi pelatihan"
                                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 font-semibold focus:outline-none focus:border-emerald-600"
                                            />
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Penyelenggara</label>
                                                <input
                                                    v-model="tr.instansi_penyelenggara"
                                                    type="text"
                                                    placeholder="Instansi penyelenggara"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pembiayaan</label>
                                                <input
                                                    v-model="tr.pembiayaan"
                                                    type="text"
                                                    placeholder="Mandiri / Yayasan"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addTrainingRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 text-emerald-900 font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Pelatihan dan Nonformal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: PERIZINAN -->
                <div v-if="activeTab === 'perizinan'" class="space-y-6 mb-8">
                    <!-- Validation Error Alert in Edit Mode -->
                    <div v-if="isEditing && Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-error/10 border border-error/20 flex items-start gap-3 text-error">
                        <span class="material-symbols-outlined shrink-0 text-xl">error</span>
                        <div class="text-xs sm:text-sm">
                            <span class="font-bold">Terdapat kesalahan pada isian form:</span>
                            <ul class="list-disc list-inside mt-1 space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- KOTAK SECTION UTAMA: RIWAYAT PERIZINAN -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
                        <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                            Riwayat Perizinan &amp; Ketidakhadiran
                                        </h4>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                                        {{ (isEditing ? form.riwayat_perizinan?.length : perizinanList?.length) || 0 }} Perizinan
                                    </span>
                                </div>
                                <p v-if="isEditing" class="text-xs text-slate-500 mt-1">
                                    Kelola atau ajukan perizinan pada baris tabel (desktop) atau formulir kartu (mobile) di bawah ini
                                </p>
                                <p v-else-if="permissions?.permit_supervisor_position" class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[15px] text-emerald-600">account_tree</span>
                                    <span>Approval: <strong class="text-slate-800 font-semibold">{{ permissions.permit_supervisor_position }}</strong> ({{ permissions.permit_supervisor_dept || 'Unit' }}) &amp; Superadmin</span>
                                </p>
                            </div>

                            <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    v-if="!isEditing"
                                    type="button"
                                    @click="startEdit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95"
                                    title="Kelola atau ajukan perizinan"
                                >
                                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add_task</span>
                                    <span>Ajukan / Kelola Izin</span>
                                </button>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Batalkan perubahan"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveChanges"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                                        :disabled="form.processing"
                                        title="Simpan pengajuan / perubahan perizinan"
                                    >
                                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 1. DESKTOP VIEW: TABEL PERIZINAN -->
                        <div class="hidden md:block">
                            <!-- View Mode -->
                            <div v-if="!isEditing">
                                <div v-if="perizinanList && perizinanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                    <table class="w-full text-xs sm:text-sm border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                                <th class="px-4 py-3 text-center w-12">No</th>
                                                <th class="px-4 py-3 text-left">Tanggal</th>
                                                <th class="px-4 py-3 text-left">Jenis</th>
                                                <th class="px-4 py-3 text-left">Detail</th>
                                                <th class="px-4 py-3 text-left">Keterangan</th>
                                                <th class="px-4 py-3 text-center">Lampiran</th>
                                                <th class="px-4 py-3 text-center">Status</th>
                                                <th class="px-4 py-3 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="(item, idx) in perizinanList"
                                                :key="item.id || idx"
                                                class="hover:bg-slate-50/80 transition-colors"
                                            >
                                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                                <td class="px-4 py-3.5 text-slate-900 font-semibold whitespace-nowrap">
                                                    {{ item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '-' }}
                                                </td>
                                                <td class="px-4 py-3.5">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                                        {{ item.jenis || '-' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-slate-800 font-medium">{{ item.detail || '-' }}</td>
                                                <td class="px-4 py-3.5 text-slate-600 max-w-[220px] truncate" :title="item.keterangan || ''">
                                                    {{ item.keterangan || '-' }}
                                                </td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <div v-if="item.preview_url" class="inline-flex items-center justify-center gap-1">
                                                        <button
                                                            type="button"
                                                            @click="openPermitPreview(item)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                            title="Lihat Berkas Lampiran"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                            <span>Lihat</span>
                                                        </button>
                                                        <a
                                                            :href="item.download_url"
                                                            class="inline-flex items-center justify-center p-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                            title="Unduh Berkas Lampiran"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                                        </a>
                                                    </div>
                                                    <span v-else class="text-slate-400 text-xs">-</span>
                                                </td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs',
                                                            item.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                            item.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                            'bg-amber-50 text-amber-700 border-amber-200'
                                                        ]"
                                                    >
                                                        <span
                                                            :class="[
                                                                'w-1.5 h-1.5 rounded-full',
                                                                item.status === 'disetujui' ? 'bg-emerald-500' :
                                                                item.status === 'ditolak' ? 'bg-rose-500' :
                                                                'bg-amber-500'
                                                            ]"
                                                        ></span>
                                                        {{ item.status === 'disetujui' ? 'Disetujui' : item.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3.5 text-center">
                                                    <button
                                                        type="button"
                                                        @click="openPermitNoteModal(item)"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200/80 transition-colors cursor-pointer"
                                                        :title="item.catatan ? 'Lihat Catatan: ' + item.catatan : 'Buka Persetujuan Perizinan'"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px] text-slate-600">sticky_note_2</span>
                                                        <span>Persetujuan</span>
                                                        <span v-if="item.catatan" class="w-1.5 h-1.5 rounded-full bg-emerald-600" title="Ada catatan"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat perizinan untuk pegawai ini</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Klik tombol Ajukan / Kelola Izin di atas untuk mengajukan perizinan</p>
                                </div>
                            </div>

                            <!-- Edit Mode (Desktop Table) -->
                            <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                                <table class="w-full text-xs sm:text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold text-[11px] uppercase tracking-wider">
                                            <th class="px-3 py-3 text-center w-12">No</th>
                                            <th class="px-3 py-3 text-left w-64">Tanggal (Mulai - Selesai)</th>
                                            <th class="px-3 py-3 text-left w-36">Jenis</th>
                                            <th class="px-3 py-3 text-left">Detail</th>
                                            <th class="px-3 py-3 text-left">Keterangan</th>
                                            <th class="px-3 py-3 text-center w-40">Lampiran</th>
                                            <th class="px-3 py-3 text-center w-28">Status</th>
                                            <th class="px-3 py-3 text-center w-14">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-if="!form.riwayat_perizinan || form.riwayat_perizinan.length === 0">
                                            <td colspan="8" class="p-8 text-center text-xs text-slate-500 italic">
                                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">assignment_late</span>
                                                Belum ada riwayat perizinan. Klik tombol di bawah untuk menambah pengajuan perizinan baru.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(row, idx) in form.riwayat_perizinan"
                                            :key="row.id || 'new_' + idx"
                                            class="hover:bg-slate-50/60 transition-colors"
                                        >
                                            <td class="px-3 py-2.5 text-center text-slate-400 font-mono font-bold">{{ idx + 1 }}</td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center gap-1.5">
                                                    <input
                                                        v-model="row.tanggal"
                                                        type="date"
                                                        :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                        class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                        title="Tanggal Mulai"
                                                    />
                                                    <span class="text-slate-400 text-xs font-semibold shrink-0">s/d</span>
                                                    <input
                                                        v-model="row.tanggal_selesai"
                                                        type="date"
                                                        :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                        class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                        title="Tanggal Akhir (Kosongkan jika 1 hari)"
                                                    />
                                                </div>
                                                <span class="text-[10px] text-slate-400 mt-0.5 block">Tgl Selesai kosong = 1 hari</span>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="row.jenis"
                                                    type="text"
                                                    placeholder="Izin, Sakit, Cuti..."
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="row.detail"
                                                    type="text"
                                                    placeholder="Detail perizinan..."
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input
                                                    v-model="row.keterangan"
                                                    type="text"
                                                    placeholder="Keterangan / alasan..."
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                />
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <div class="flex flex-col items-center gap-1.5 justify-center">
                                                    <div v-if="row.preview_url" class="inline-flex items-center gap-1">
                                                        <button
                                                            type="button"
                                                            @click="openPermitPreview(row)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                            title="Lihat Berkas"
                                                        >
                                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                            <span>Lihat</span>
                                                        </button>
                                                    </div>
                                                    <input
                                                        type="file"
                                                        @change="e => onPermitFileChange(row, e)"
                                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                                        :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                        class="w-full max-w-[140px] text-[10px] text-slate-500 file:mr-1 file:py-0.5 file:px-1.5 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-emerald-50 file:text-emerald-800 cursor-pointer border border-slate-200 rounded-lg p-0.5 bg-white disabled:opacity-40 disabled:cursor-not-allowed"
                                                        :title="row.lampiran_nama ? 'Ganti berkas: ' + row.lampiran_nama : 'Pilih berkas lampiran'"
                                                    />
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs',
                                                        row.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                        row.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                        'bg-amber-50 text-amber-700 border-amber-200'
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'w-1.5 h-1.5 rounded-full',
                                                            row.status === 'disetujui' ? 'bg-emerald-500' :
                                                            row.status === 'ditolak' ? 'bg-rose-500' :
                                                            'bg-amber-500'
                                                        ]"
                                                    ></span>
                                                    {{ row.status === 'disetujui' ? 'Disetujui' : row.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeRiwayatPerizinanRow(idx)"
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    :class="[
                                                        'w-8 h-8 rounded-lg flex items-center justify-center mx-auto transition-colors',
                                                        (canApprovePermit || !row.id || (row.status !== 'disetujui' && (!isTeacher || row.status === 'diajukan')))
                                                            ? 'text-rose-500 hover:bg-rose-50 cursor-pointer active:scale-95'
                                                            : 'text-slate-300 cursor-not-allowed opacity-40'
                                                    ]"
                                                    :title="(!canApprovePermit && row.status === 'disetujui') ? 'Perizinan yang sudah disetujui tidak dapat dihapus' : ((!canApprovePermit && isTeacher && row.id && row.status !== 'diajukan') ? 'Hanya dapat dihapus saat status masih Diajukan' : 'Hapus baris perizinan ini')"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="p-3 bg-slate-50 border-t border-slate-200">
                                    <button
                                        type="button"
                                        @click="addRiwayatPerizinanRow"
                                        class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-white hover:bg-emerald-50 text-emerald-900 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                        <span>Tambah Riwayat Perizinan</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. MOBILE CARD VIEW: PERIZINAN -->
                        <div class="md:hidden space-y-3">
                            <!-- View Mode -->
                            <div v-if="!isEditing" class="space-y-3">
                                <div
                                    v-for="(item, idx) in perizinanList"
                                    :key="item.id || idx"
                                    class="bg-slate-50/70 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-200/60">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                            {{ item.jenis || '-' }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shadow-2xs',
                                                item.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                item.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                'bg-amber-50 text-amber-700 border-amber-200'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'w-1.5 h-1.5 rounded-full',
                                                    item.status === 'disetujui' ? 'bg-emerald-500' :
                                                    item.status === 'ditolak' ? 'bg-rose-500' :
                                                    'bg-amber-500'
                                                ]"
                                            ></span>
                                            {{ item.status === 'disetujui' ? 'Disetujui' : item.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                        </span>
                                    </div>

                                    <div class="space-y-1.5 text-xs">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tanggal Izin</span>
                                            <span class="font-bold text-slate-900 text-sm">{{ item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Detail</span>
                                            <span class="text-slate-800 font-semibold">{{ item.detail || '-' }}</span>
                                        </div>
                                        <div v-if="item.keterangan">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Alasan</span>
                                            <span class="text-slate-600">{{ item.keterangan }}</span>
                                        </div>
                                    </div>

                                    <div class="pt-2.5 border-t border-slate-200/60 flex items-center justify-between gap-2 flex-wrap">
                                        <div v-if="item.preview_url" class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openPermitPreview(item)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                <span>Lampiran</span>
                                            </button>
                                        </div>
                                        <div v-else class="text-[11px] text-slate-400 italic">Tanpa lampiran</div>

                                        <button
                                            type="button"
                                            @click="openPermitNoteModal(item)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200/80 transition-colors cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px] text-slate-600">sticky_note_2</span>
                                            <span>Persetujuan</span>
                                            <span v-if="item.catatan" class="w-1.5 h-1.5 rounded-full bg-emerald-600" title="Ada catatan"></span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!perizinanList || perizinanList.length === 0" class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                                    <p class="text-xs font-semibold text-slate-600">Belum ada riwayat perizinan</p>
                                </div>
                            </div>

                            <!-- Mobile Edit Mode (Card Form) -->
                            <div v-else class="space-y-3">
                                <div
                                    v-for="(row, idx) in form.riwayat_perizinan"
                                    :key="row.id || 'new_' + idx"
                                    class="bg-slate-50/60 rounded-2xl border border-slate-200/80 p-4 space-y-3 shadow-2xs"
                                >
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                                Perizinan #{{ idx + 1 }}
                                            </span>
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                                    row.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                    row.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                    'bg-amber-50 text-amber-700 border-amber-200'
                                                ]"
                                            >
                                                {{ row.status === 'disetujui' ? 'Disetujui' : row.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeRiwayatPerizinanRow(idx)"
                                            :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                            :class="[
                                                'text-xs font-bold flex items-center gap-1 p-1',
                                                (canApprovePermit || !row.id || (row.status !== 'disetujui' && (!isTeacher || row.status === 'diajukan')))
                                                    ? 'text-rose-600 hover:text-rose-800 cursor-pointer'
                                                    : 'text-slate-300 cursor-not-allowed opacity-40'
                                            ]"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tgl Mulai</label>
                                                <input
                                                    v-model="row.tanggal"
                                                    type="date"
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="w-full px-2.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tgl Selesai</label>
                                                <input
                                                    v-model="row.tanggal_selesai"
                                                    type="date"
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="w-full px-2.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Perizinan</label>
                                            <input
                                                v-model="row.jenis"
                                                type="text"
                                                placeholder="Contoh: Izin, Sakit, Cuti..."
                                                :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Detail Perizinan</label>
                                            <input
                                                v-model="row.detail"
                                                type="text"
                                                placeholder="Contoh: Acara keluarga di luar kota"
                                                :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Keterangan / Alasan</label>
                                            <input
                                                v-model="row.keterangan"
                                                type="text"
                                                placeholder="Keterangan tambahan..."
                                                :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-emerald-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Berkas Lampiran</label>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="file"
                                                    @change="e => onPermitFileChange(row, e)"
                                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                                    :disabled="!canApprovePermit && row.id && (row.status === 'disetujui' || (isTeacher && row.status !== 'diajukan'))"
                                                    class="flex-1 text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 border border-slate-200 rounded-xl p-1 bg-white disabled:opacity-40"
                                                />
                                                <button
                                                    v-if="row.preview_url"
                                                    type="button"
                                                    @click="openPermitPreview(row)"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0"
                                                >
                                                    Lihat
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addRiwayatPerizinanRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-emerald-400 hover:border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 text-emerald-900 font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Perizinan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 8: DOKUMEN -->
                <div v-if="activeTab === 'dokumen'" class="space-y-8">
                    <!-- 1. TABEL DOKUMEN PRIBADI -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">badge</span>
                                Dokumen Pribadi
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenPribadiList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Unggah Ulang / Ganti Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenPribadiList.length === 0">
                                        <td colspan="4" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen pribadi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. TABEL DOKUMEN RIWAYAT KEPEGAWAIAN -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">work_history</span>
                                Dokumen Riwayat Kepegawaian
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenKepegawaianList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenKepegawaianList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen riwayat kepegawaian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. TABEL DOKUMEN RIWAYAT JABATAN -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                                Dokumen Riwayat Jabatan
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenJabatanList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenJabatanList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada data riwayat jabatan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. TABEL DOKUMEN RIWAYAT PENDIDIKAN FORMAL -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">school</span>
                                Dokumen Riwayat Pendidikan Formal
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenPendidikanList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    v-if="!item.isAdditional"
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenPendidikanList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen riwayat pendidikan formal.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. TABEL DOKUMEN RIWAYAT PELATIHAN DAN PENDIDIKAN NONFORMAL -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">workspace_premium</span>
                                Dokumen Riwayat Pelatihan dan Sertifikat
                            </h4>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
                            <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                                <thead>
                                    <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center w-12">No</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Nama Dokumen</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-left">Keterangan / Rincian</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Status Unggah</th>
                                        <th class="border border-outline-variant/60 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in dokumenPelatihanList"
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono">{{ item.no }}</td>
                                        <td class="border border-outline-variant/60 px-4 py-3 font-semibold text-on-surface">
                                            {{ item.nama }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                            {{ item.keterangan || '-' }}
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <span
                                                v-if="item.document"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Sudah Diunggah
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Belum Diunggah
                                            </span>
                                        </td>
                                        <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                            <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                                    title="Lihat Pratinjau Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <a
                                                    :href="item.document.download_url"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                                    title="Unduh Berkas"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">download</span>
                                                    <span>Unduh</span>
                                                </a>
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                                    title="Ganti Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">sync</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteDocument(item.document)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                    title="Hapus Berkas Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                                                    title="Upload Dokumen"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                    <span>Unggah</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dilihat"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">visibility_off</span>
                                                    <span>Lihat</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60"
                                                    title="Belum ada berkas untuk dihapus"
                                                >
                                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="dokumenPelatihanList.length === 0">
                                        <td colspan="5" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                            Belum ada dokumen riwayat pelatihan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>


        <!-- MODAL UPLOAD DOKUMEN -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showUploadModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="closeUploadModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60 bg-white">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">upload_file</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">Unggah Dokumen Pegawai</h3>
                                <p class="text-xs text-on-surface-variant truncate max-w-[280px] sm:max-w-xs">{{ uploadTitle || 'Pilih berkas untuk diunggah' }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeUploadModal"
                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form @submit.prevent="submitDocument" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Judul Dokumen</label>
                            <input
                                v-model="uploadTitle"
                                type="text"
                                placeholder="Contoh: KTP Pegawai, SK Pengangkatan, Ijazah S1"
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                required
                            />
                        </div>

                        <!-- Dropzone -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Berkas Dokumen</label>
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop"
                                :class="[
                                    'border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer relative',
                                    isDragging ? 'border-primary bg-primary/5' : 'border-outline-variant/60 hover:border-primary/50 bg-white',
                                    uploadFile ? 'border-emerald-500 bg-emerald-50/20' : ''
                                ]"
                                @click="fileInput?.click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf,image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileChange"
                                />

                                <div v-if="uploadFile" class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-4xl text-emerald-600 mb-2">task_alt</span>
                                    <span class="text-xs font-bold text-on-surface max-w-full truncate px-2">{{ uploadFile.name }}</span>
                                    <span class="text-[11px] text-on-surface-variant mt-0.5">{{ formatBytes(uploadFile.size) }}</span>
                                    <span class="mt-2 text-[11px] text-primary font-semibold hover:underline">Klik untuk mengganti berkas</span>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-4xl text-primary/70 mb-2">cloud_upload</span>
                                    <p class="text-xs font-bold text-on-surface">Klik atau seret berkas ke sini</p>
                                    <p class="text-[11px] text-on-surface-variant mt-1">Format didukung: PDF, JPG, PNG, WEBP (Maksimal 10 MB)</p>
                                </div>
                            </div>
                            <p v-if="uploadFileError" class="mt-1.5 text-xs text-error font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                {{ uploadFileError }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/20">
                            <button
                                type="button"
                                @click="closeUploadModal"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-slate-100 border border-outline-variant/60 transition-colors cursor-pointer"
                                :disabled="isUploading"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isUploading || !uploadFile"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs shadow-md transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="isUploading" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">upload</span>
                                <span>{{ isUploading ? 'Mengunggah...' : 'Unggah Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- MODAL PREVIEW DOKUMEN -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPreviewModal && previewDoc"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md"
                @click.self="closePreviewModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-outline-variant/60 bg-white shrink-0">
                        <div class="flex items-center gap-3 truncate mr-2">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">
                                    {{ previewDoc.mime_type?.includes('pdf') ? 'picture_as_pdf' : 'image' }}
                                </span>
                            </div>
                            <div class="truncate">
                                <h3 class="text-xs sm:text-sm font-bold text-on-surface truncate">{{ previewDoc.title || previewDoc.file_name }}</h3>
                                <p class="text-[11px] text-on-surface-variant truncate">{{ previewDoc.file_name }} • {{ previewDoc.formatted_file_size }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a
                                :href="previewDoc.download_url"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                title="Unduh Berkas"
                            >
                                <span class="material-symbols-outlined text-[16px]">download</span>
                                <span class="hidden sm:inline">Unduh</span>
                            </a>
                            <a
                                :href="previewDoc.preview_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary/10 hover:bg-primary/20 text-primary transition-colors cursor-pointer"
                                title="Buka Tab Baru"
                            >
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                <span class="hidden sm:inline">Tab Baru</span>
                            </a>
                            <button
                                type="button"
                                @click="closePreviewModal"
                                class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-slate-100 transition-colors ml-1 cursor-pointer"
                                title="Tutup Preview"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content (PDF or Image viewer) -->
                    <div class="flex-1 bg-white overflow-auto p-4 flex items-center justify-center">
                        <iframe
                            v-if="previewDoc.mime_type?.includes('pdf')"
                            :src="previewDoc.preview_url"
                            class="w-full h-full rounded-xl border border-outline-variant/30 shadow-inner"
                            title="Pratinjau Dokumen PDF"
                        ></iframe>
                        <img
                            v-else
                            :src="previewDoc.preview_url"
                            :alt="previewDoc.title"
                            class="max-w-full max-h-full object-contain rounded-xl shadow-lg border border-outline-variant/20"
                        />
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MODAL PERSETUJUAN PERIZINAN -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPermitNoteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="closePermitNoteModal"
            >
                <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-outline-variant/60 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low">
                        <div class="flex items-center gap-2 text-primary font-bold text-sm sm:text-base">
                            <span class="material-symbols-outlined text-[20px]">sticky_note_2</span>
                            <span>Persetujuan Perizinan</span>
                        </div>
                        <button
                            type="button"
                            @click="closePermitNoteModal"
                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitPermitNote" class="p-6 space-y-4">
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-outline-variant/40 space-y-2">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="text-xs text-on-surface-variant">
                                    <span class="font-bold text-on-surface">Jenis:</span> {{ permitNoteForm.jenis || '-' }}
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="text-[11px] text-outline font-semibold">Status:</span>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                                            permitNoteForm.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            permitNoteForm.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-amber-50 text-amber-700 border-amber-200'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                permitNoteForm.status === 'disetujui' ? 'bg-emerald-500' :
                                                permitNoteForm.status === 'ditolak' ? 'bg-rose-500' :
                                                'bg-amber-500'
                                            ]"
                                        ></span>
                                        {{ permitNoteForm.status === 'disetujui' ? 'Disetujui' : permitNoteForm.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="permitNoteForm.detail" class="text-xs text-on-surface-variant">
                                <span class="font-bold text-on-surface">Detail:</span> {{ permitNoteForm.detail }}
                            </div>
                            <div v-if="permitNoteForm.formatted_tanggal" class="text-xs text-on-surface-variant">
                                <span class="font-bold text-on-surface">Tanggal:</span> {{ permitNoteForm.formatted_tanggal }}
                            </div>
                        </div>

                        <!-- Update Status jika berwenang approval (Atasan Langsung atau Superadmin) -->
                        <div v-if="canApprovePermit" class="space-y-1.5">
                            <label class="block text-xs font-bold text-on-surface">Ubah Status Pengajuan</label>
                            <select
                                v-model="permitNoteForm.status"
                                class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm font-bold text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                            >
                                <option value="diajukan">Diajukan</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        <!-- Catatan / Feedback -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-on-surface">
                                {{ canApprovePermit ? 'Catatan / Alasan Persetujuan / Penolakan' : 'Catatan dari Pihak Sekolah / Atasan Langsung' }}
                            </label>
                            <textarea
                                v-if="canApprovePermit"
                                v-model="permitNoteForm.catatan"
                                rows="4"
                                placeholder="Tuliskan catatan, alasan, atau keterangan tambahan..."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 resize-y"
                            ></textarea>
                            <div v-else class="space-y-2">
                                <div
                                    class="p-3.5 bg-slate-50 rounded-xl border border-outline-variant/40 text-xs sm:text-sm text-on-surface min-h-[80px] whitespace-pre-wrap leading-relaxed"
                                >
                                    {{ permitNoteForm.catatan || 'Belum ada catatan yang diberikan oleh pihak sekolah / atasan untuk perizinan ini.' }}
                                </div>
                                <div v-if="permitNoteForm.updater_name" class="flex items-center gap-1.5 text-[11px] text-on-surface-variant bg-primary/5 px-2.5 py-1.5 rounded-lg border border-primary/10">
                                    <span class="material-symbols-outlined text-[14px] text-primary">verified_user</span>
                                    <span>Diberikan oleh: <strong class="text-on-surface">{{ permitNoteForm.updater_name }}</strong> ({{ permitNoteForm.updater_role || 'Pemberi Approval' }})</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/40">
                            <button
                                type="button"
                                @click="closePermitNoteModal"
                                class="px-4 py-2 rounded-xl border border-outline-variant/60 text-xs sm:text-sm font-semibold text-on-surface hover:bg-slate-50 transition-colors cursor-pointer"
                            >
                                {{ canApprovePermit ? 'Batal' : 'Tutup' }}
                            </button>
                            <button
                                v-if="canApprovePermit"
                                type="submit"
                                :disabled="permitNoteForm.processing"
                                class="px-5 py-2 rounded-xl bg-primary text-white text-xs sm:text-sm font-bold hover:bg-primary/90 transition-all shadow-2xs active:scale-95 disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                            >
                                <span v-if="permitNoteForm.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span>Simpan Persetujuan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>
