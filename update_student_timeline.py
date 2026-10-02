import sys

file_path = 'resources/js/Pages/StudentDetail.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add computed to imports and computed logic
if "import { ref } from 'vue';" in content:
    content = content.replace("import { ref } from 'vue';", "import { ref, computed } from 'vue';")

computed_logic = r'''
const timelineHeaders = computed(() => {
    if (!props.student.riwayat_keaktifan) return [];
    
    const headersMap = new Map();
    props.student.riwayat_keaktifan.forEach(r => {
        const key = `${r.academic_year} ${r.semester}`;
        if (!headersMap.has(key)) {
            headersMap.set(key, { ta: r.academic_year, semester: r.semester, key });
        }
    });
    return Array.from(headersMap.values());
});

const timelineRows = computed(() => {
    if (!props.student.riwayat_keaktifan) return [];
    
    const unitsMap = new Map();
    props.student.riwayat_keaktifan.forEach(r => {
        const u = r.unit || '-';
        if (!unitsMap.has(u)) {
            unitsMap.set(u, {});
        }
        const key = `${r.academic_year} ${r.semester}`;
        unitsMap.get(u)[key] = r;
    });
    
    return Array.from(unitsMap.entries()).map(([unit, data]) => ({
        unit,
        data
    }));
});
'''

if "const timelineHeaders = computed" not in content:
    content = content.replace("const activeTab = ref('identitas');", f"const activeTab = ref('identitas');\n{computed_logic}")

# 2. Update the HTML layout for the table
old_html = r'''                                    <div class="overflow-x-auto rounded-xl border border-outline-variant/30">
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
                                    </div>'''

new_html = r'''                                    <div class="overflow-x-auto rounded-xl border border-outline-variant/30 relative">
                                        <table class="w-full text-left text-sm border-collapse whitespace-nowrap">
                                            <thead>
                                                <tr class="border-b border-outline-variant/30">
                                                    <th class="p-3 font-semibold text-on-surface min-w-[160px] bg-surface-container-low sticky left-0 z-10 border-r border-outline-variant/30 shadow-[4px_0_12px_rgba(0,0,0,0.03)]">Unit</th>
                                                    <th v-for="header in timelineHeaders" :key="header.key" class="p-3 font-semibold text-on-surface text-center bg-surface-container-low/50">
                                                        {{ header.ta }} <br/>
                                                        <span class="text-xs text-on-surface-variant font-medium">{{ header.semester }}</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-outline-variant/30">
                                                <tr v-for="row in timelineRows" :key="row.unit">
                                                    <td class="p-3 text-on-surface-variant font-bold bg-white sticky left-0 z-10 border-r border-outline-variant/30 shadow-[4px_0_12px_rgba(0,0,0,0.03)]">
                                                        {{ row.unit }}
                                                    </td>
                                                    <td v-for="header in timelineHeaders" :key="header.key" class="p-3 text-center min-w-[160px] hover:bg-surface-container-low/30 transition-colors">
                                                        <template v-if="row.data[header.key]">
                                                            <span v-if="row.data[header.key].status === 'aktif'" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_4px_rgba(16,185,129,0.5)]"></span>
                                                                Kelas {{ row.data[header.key].kelas || 'Aktif' }}
                                                            </span>
                                                            <div v-else class="flex flex-col items-center justify-center">
                                                                <span class="text-error font-semibold capitalize text-xs bg-error/10 px-2 py-1 rounded">
                                                                    {{ row.data[header.key].status.replace('_', ' ') }}
                                                                </span>
                                                                <span v-if="row.data[header.key].keterangan" class="text-[10px] text-error/80 mt-1 max-w-[140px] truncate" :title="row.data[header.key].keterangan">
                                                                    ({{ row.data[header.key].keterangan }})
                                                                </span>
                                                            </div>
                                                        </template>
                                                        <template v-else>
                                                            <span class="text-outline-variant/50">-</span>
                                                        </template>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>'''

content = content.replace(old_html, new_html)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated timeline layout in StudentDetail.vue")
