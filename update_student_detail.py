import sys

file_path = 'resources/js/Pages/StudentDetail.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

registrasi_tab_end = r'''                                    <div class="md:col-span-2">
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Sumber Informasi PSB</label>
                                        <p class="mt-0.5">
                                            <span
                                                v-if="student.info_psb"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-primary/10 text-primary"
                                            >
                                                <span class="material-symbols-outlined text-[13px]">campaign</span>
                                                {{ student.info_psb }}
                                            </span>
                                            <span v-else class="text-xs sm:text-sm font-medium text-on-surface">-</span>
                                        </p>
                                    </div>
                                </div>'''

riwayat_html = r'''                                    <div class="md:col-span-2">
                                        <label class="text-[10px] sm:text-xs text-on-surface-variant uppercase tracking-wider">Sumber Informasi PSB</label>
                                        <p class="mt-0.5">
                                            <span
                                                v-if="student.info_psb"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-primary/10 text-primary"
                                            >
                                                <span class="material-symbols-outlined text-[13px]">campaign</span>
                                                {{ student.info_psb }}
                                            </span>
                                            <span v-else class="text-xs sm:text-sm font-medium text-on-surface">-</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mt-6" v-if="student.riwayat_keaktifan && student.riwayat_keaktifan.length > 0">
                                    <h4 class="text-xs sm:text-sm font-bold text-primary mb-3 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">history</span>
                                        Riwayat Keaktifan Siswa
                                    </h4>
                                    <div class="overflow-x-auto rounded-xl border border-outline-variant/30">
                                        <table class="w-full text-left text-sm border-collapse">
                                            <thead>
                                                <tr class="bg-surface-container-low border-b border-outline-variant/30">
                                                    <th class="p-3 font-semibold text-on-surface w-24">Unit</th>
                                                    <th class="p-3 font-semibold text-on-surface">TA / Semester</th>
                                                    <th class="p-3 font-semibold text-on-surface">Kelas / Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-outline-variant/30">
                                                <tr v-for="riwayat in student.riwayat_keaktifan" :key="riwayat.id">
                                                    <td class="p-3 text-on-surface-variant font-medium">
                                                        {{ riwayat.unit }}
                                                    </td>
                                                    <td class="p-3 text-on-surface-variant">
                                                        {{ riwayat.academic_year }} ({{ riwayat.semester }})
                                                    </td>
                                                    <td class="p-3">
                                                        <span v-if="riwayat.status === 'aktif'" class="text-emerald-600 font-bold">
                                                            {{ riwayat.kelas || 'Aktif' }}
                                                        </span>
                                                        <span v-else class="text-error font-medium capitalize">
                                                            {{ riwayat.status.replace('_', ' ') }} 
                                                            {{ riwayat.keterangan ? `- ${riwayat.keterangan}` : '' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>'''

content = content.replace(registrasi_tab_end, riwayat_html)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated StudentDetail.vue")
