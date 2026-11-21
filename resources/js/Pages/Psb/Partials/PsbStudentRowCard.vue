<script setup>
import { Link } from '@inertiajs/vue3';
import {
    getGenderBadgeClass,
    getGenderLabel,
    getProgramBadgeClass,
    getRegistrationTypeBadgeClass,
    getStatusKeaktifanBadgeClass,
    getStatusKeaktifanDotClass,
} from '@/Utils/studentBadges';

defineProps({
    student: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    mode: {
        type: String,
        default: 'school', // 'school' | 'welfare'
    },
    fallbackSchoolName: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Link
        :href="`/students/${student.nisn || student.id}`"
        class="p-3 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/40 transition-all flex items-center justify-between gap-3 group"
    >
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold flex items-center justify-center shrink-0">
                {{ index + 1 }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-slate-900 group-hover:text-emerald-950 break-words leading-snug">
                        {{ student.nama }}
                    </span>
                    <span :class="['px-1.5 py-0.5 rounded border text-[10px] font-bold', getGenderBadgeClass(student.jk)]">
                        {{ getGenderLabel(student.jk) }}
                    </span>
                    <span
                        v-if="student.status_registrasi"
                        :class="['px-1.5 py-0.5 rounded border text-[10px] font-bold', getRegistrationTypeBadgeClass(student.status_registrasi)]"
                    >
                        {{ student.status_registrasi }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 break-words leading-snug">
                    {{ student.kelas && student.kelas !== '-' ? student.kelas : `Angk. ${student.angkatan || '-'}` }} • Masuk {{ student.ta_masuk || '-' }}
                </p>
                <p class="text-[11px] font-semibold text-slate-700 mt-0.5 break-words leading-snug">
                    <template v-if="mode === 'welfare'">
                        Dusun: {{ student.dusun || '-' }}
                    </template>
                    <template v-else>
                        Asal: {{ student.sekolah_asal || fallbackSchoolName || '-' }}
                    </template>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
            <span v-if="student.program" :class="['px-2 py-0.5 rounded border text-[10px] font-bold', getProgramBadgeClass(student.program)]">
                {{ student.program }}
            </span>
            <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[10px] font-bold', getStatusKeaktifanBadgeClass(student)]">
                <span :class="['w-1.5 h-1.5 rounded-full', getStatusKeaktifanDotClass(student)]"></span>
                <span>{{ student.status_keaktifan || 'Aktif' }}</span>
            </span>
        </div>
    </Link>
</template>
