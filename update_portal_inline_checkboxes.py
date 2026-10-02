import sys
import re

file_path = 'resources/js/Pages/Portal.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the checklist button from the table
remove_button = r'''                                    <button
                                        type="button"
                                        @click="openSyncClassesModal(year)"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                        title="Pilih Kelas Aktif di TA ini"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">checklist</span>
                                    </button>'''
content = content.replace(remove_button, '')

# Update the form definition
form_search = r'''const formYear = useForm({
    name: '',
    semester: 'Ganjil',
    start_date: '',
    end_date: '',
});'''
form_replace = r'''const formYear = useForm({
    name: '',
    semester: 'Ganjil',
    start_date: '',
    end_date: '',
    selected_classes: [],
});'''
content = content.replace(form_search, form_replace)

# Modify openCreateModal and openEditModal
create_search = r'''function openCreateModal() {
    editingYear.value = null;
    showFormModal.value = true;
}'''
create_replace = r'''async function openCreateModal() {
    editingYear.value = null;
    formYear.reset();
    showFormModal.value = true;
    
    // Fetch master units
    try {
        const res = await axios.get(`/portal/academic-years/0/active-classes`);
        syncMasterUnits.value = res.data.units;
    } catch (e) {}
}'''
content = content.replace(create_search, create_replace)

edit_search = r'''function openEditModal(year) {
    editingYear.value = { ...year };
    showFormModal.value = true;
}'''
edit_replace = r'''async function openEditModal(year) {
    editingYear.value = { ...year };
    
    showFormModal.value = true;
    isSyncingClasses.value = true;
    
    try {
        const res = await axios.get(`/portal/academic-years/${year.id}/active-classes`);
        syncMasterUnits.value = res.data.units;
        formYear.selected_classes = res.data.active_classrooms;
    } catch (e) {} finally {
        isSyncingClasses.value = false;
    }
}'''
content = content.replace(edit_search, edit_replace)

# Modify submitFormYear
submit_search = r'''function submitFormYear() {
    if (editingYear.value) {
        formYear.put(`/portal/academic-years/${editingYear.value.id}`, {
            onSuccess: onFormSuccess,
        });
    } else {
        formYear.post('/portal/academic-years', {
            onSuccess: onFormSuccess,
        });
    }
}'''
submit_replace = submit_search + r'''
// Variables for class sync inside form
const syncMasterUnits = ref([]);
const isSyncingClasses = ref(false);
'''
if "const syncMasterUnits = ref([]);" not in content:
    content = content.replace(submit_search, submit_replace)


# Remove the separate Sync Classes Modal HTML at the end of the file
sync_modal_regex = r'<!-- Sync Classes Modal -->[\s\S]*?(?=</template>)'
content = re.sub(sync_modal_regex, '', content)

# Remove the old ref and functions for separate modal
old_script_regex = r'const showSyncClassesModal = ref\(false\);[\s\S]*?function submitSyncClasses\(\) \{[\s\S]*?\}\n'
content = re.sub(old_script_regex, '', content)


# Insert the checklist inside the main Form Modal
form_html_search = r'''                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-on-surface">Mulai (Opsional)</label>
                            <input
                                type="date"
                                v-model="formYear.start_date"
                                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/50 bg-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm"
                            />
                            <div v-if="formYear.errors.start_date" class="text-error text-xs">{{ formYear.errors.start_date }}</div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-on-surface">Selesai (Opsional)</label>
                            <input
                                type="date"
                                v-model="formYear.end_date"
                                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/50 bg-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm"
                            />
                            <div v-if="formYear.errors.end_date" class="text-error text-xs">{{ formYear.errors.end_date }}</div>
                        </div>
                    </div>'''

form_html_replace = form_html_search + r'''
                    
                    <div class="pt-4 border-t border-outline-variant/30">
                        <label class="block text-sm font-semibold text-on-surface mb-3">Pilih Kelas Aktif (Checklist)</label>
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
                                        <input type="checkbox" :value="unit.name + '|' + cls.name" v-model="formYear.selected_classes" class="rounded text-primary focus:ring-primary/30 border-outline-variant">
                                        <span class="text-xs font-medium text-on-surface">Kelas {{ cls.name }}</span>
                                    </label>
                                    <div v-if="!unit.classes.length" class="text-xs text-on-surface-variant italic col-span-full">Belum ada master kelas di unit ini.</div>
                                </div>
                            </div>
                        </div>
                    </div>'''

if "Pilih Kelas Aktif (Checklist)" not in content:
    content = content.replace(form_html_search, form_html_replace)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated Portal.vue with integrated checkboxes in pencil form")
