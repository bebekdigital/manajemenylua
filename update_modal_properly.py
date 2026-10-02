import sys

file_path = 'resources/js/Components/Portal/AcademicYearFormModal.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

script_search = r'''const form = ref({
    name: '',
    semester: 'Ganjil',
    start_date: '',
    end_date: '',
});

const isSubmitting = ref(false);'''

script_replace = r'''const form = ref({
    name: '',
    semester: 'Ganjil',
    start_date: '',
    end_date: '',
    selected_classes: [],
});

const isSubmitting = ref(false);
const syncMasterUnits = ref([]);
const isSyncingClasses = ref(false);
'''
content = content.replace(script_search, script_replace)

watch_search = r'''        if (props.editing) {
            form.value = {
                name: props.editing.name || '',
                semester: props.editing.semester || 'Ganjil',
                start_date: props.editing.start_date || '',
                end_date: props.editing.end_date || '',
            };
        } else {
            form.value = {
                name: '',
                semester: 'Ganjil',
                start_date: '',
                end_date: '',
            };
        }'''

watch_replace = r'''        isSyncingClasses.value = true;
        if (props.editing) {
            form.value = {
                name: props.editing.name || '',
                semester: props.editing.semester || 'Ganjil',
                start_date: props.editing.start_date || '',
                end_date: props.editing.end_date || '',
                selected_classes: [],
            };
            axios.get(`/portal/academic-years/${props.editing.id}/active-classes`).then(res => {
                syncMasterUnits.value = res.data.units;
                form.value.selected_classes = res.data.active_classrooms;
                isSyncingClasses.value = false;
            }).catch(e => isSyncingClasses.value = false);
        } else {
            form.value = {
                name: '',
                semester: 'Ganjil',
                start_date: '',
                end_date: '',
                selected_classes: [],
            };
            axios.get(`/portal/academic-years/0/active-classes`).then(res => {
                syncMasterUnits.value = res.data.units;
                isSyncingClasses.value = false;
            }).catch(e => isSyncingClasses.value = false);
        }'''
content = content.replace(watch_search, watch_replace)

# Add axios import if missing
if "import axios from 'axios';" not in content:
    content = content.replace("import { router } from '@inertiajs/vue3';", "import { router } from '@inertiajs/vue3';\nimport axios from 'axios';")

html_search = r'''                                    <p v-if="errors.end_date" class="text-xs text-error mt-1">{{ errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Actions -->'''

html_replace = r'''                                    <p v-if="errors.end_date" class="text-xs text-error mt-1">{{ errors.end_date }}</p>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-outline-variant/30">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">
                                    Pilih Kelas Aktif (Checklist)
                                </label>
                                <div v-if="isSyncingClasses" class="py-6 flex justify-center text-primary">
                                    <span class="material-symbols-outlined animate-spin text-[24px]">progress_activity</span>
                                </div>
                                <div v-else class="space-y-4 max-h-[30vh] overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="unit in syncMasterUnits" :key="unit.id" class="border border-outline-variant/30 rounded-xl overflow-hidden">
                                        <div class="bg-surface-container-low px-4 py-2 font-bold text-sm border-b border-outline-variant/30 text-on-surface flex justify-between items-center">
                                            <span>Unit: {{ unit.name }}</span>
                                        </div>
                                        <div class="p-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            <label v-for="cls in unit.classes" :key="cls.id" class="flex items-center gap-2 cursor-pointer p-1.5 rounded-lg hover:bg-surface-container-low transition-colors border border-transparent hover:border-outline-variant/30">
                                                <input type="checkbox" :value="unit.name + '|' + cls.name" v-model="form.selected_classes" class="rounded text-primary focus:ring-primary/30 border-outline-variant">
                                                <span class="text-xs font-medium text-on-surface">Kelas {{ cls.name }}</span>
                                            </label>
                                            <div v-if="!unit.classes.length" class="text-xs text-on-surface-variant italic col-span-full">Belum ada master kelas di unit ini.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->'''
content = content.replace(html_search, html_replace)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated AcademicYearFormModal.vue")
