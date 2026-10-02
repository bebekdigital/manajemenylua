import sys
import os
import re

routes_path = 'routes/web.php'
with open(routes_path, 'r', encoding='utf-8') as f:
    routes = f.read()

# Replace old generate-classes route with two new ones
search_str = "Route::post('/portal/academic-years/{academicYear}/generate-classes', [AcademicYearClassController::class, 'generate'])->name('academic-years.generate-classes');"
insert_str = """Route::get('/portal/academic-years/{academicYear}/active-classes', [AcademicYearClassController::class, 'getActiveClasses'])->name('academic-years.get-active-classes');
    Route::post('/portal/academic-years/{academicYear}/sync-classes', [AcademicYearClassController::class, 'syncActiveClasses'])->name('academic-years.sync-classes');"""
    
if search_str in routes:
    routes = routes.replace(search_str, insert_str)
    with open(routes_path, 'w', encoding='utf-8') as f:
        f.write(routes)
else:
    # already done maybe? Just in case, try regex replacement if it was slightly different
    pass

portal_path = 'resources/js/Pages/Portal.vue'
with open(portal_path, 'r', encoding='utf-8') as f:
    portal = f.read()

# 1. Update the button to open a modal instead of submitting
old_button = r'''                                    <button
                                        type="button"
                                        @click="generateClasses(year)"
                                        :disabled="generatingId === year.id"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                        title="Generate/Sinkronisasi Kelas dari Master Data"
                                    >
                                        <span v-if="generatingId === year.id" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[18px]">auto_awesome</span>
                                    </button>'''

new_button = r'''                                    <button
                                        type="button"
                                        @click="openSyncClassesModal(year)"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                        title="Pilih Kelas Aktif di TA ini"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">checklist</span>
                                    </button>'''

if "openSyncClassesModal(year)" not in portal:
    portal = portal.replace(old_button, new_button)

# 2. Add Vue state and functions for the modal
script_search = r'''const settingActiveId = ref(null);
const deletingId = ref(null);
const generatingId = ref(null);'''

script_replace = r'''const settingActiveId = ref(null);
const deletingId = ref(null);

const showSyncClassesModal = ref(false);
const syncTargetYear = ref(null);
const syncMasterUnits = ref([]);
const selectedClasses = ref([]);
const isSyncingClasses = ref(false);

async function openSyncClassesModal(year) {
    syncTargetYear.value = year;
    showSyncClassesModal.value = true;
    isSyncingClasses.value = true;
    
    try {
        const res = await axios.get(`/portal/academic-years/${year.id}/active-classes`);
        syncMasterUnits.value = res.data.units;
        selectedClasses.value = res.data.active_classrooms;
    } catch (e) {
        alert('Gagal mengambil data kelas');
    } finally {
        isSyncingClasses.value = false;
    }
}

function closeSyncClassesModal() {
    showSyncClassesModal.value = false;
    syncTargetYear.value = null;
    syncMasterUnits.value = [];
    selectedClasses.value = [];
}

function submitSyncClasses() {
    router.post(`/portal/academic-years/${syncTargetYear.value.id}/sync-classes`, {
        selected_classes: selectedClasses.value
    }, {
        preserveScroll: true,
        onSuccess: () => closeSyncClassesModal()
    });
}
'''

if "showSyncClassesModal = ref(false)" not in portal:
    portal = portal.replace(script_search, script_replace)
    
# Remove old generateClasses function
if "function generateClasses(year) {" in portal:
    import re
    # use regex to remove the function
    portal = re.sub(r'function generateClasses\(year\) \{[\s\S]*?function setActive\(year\)', 'function setActive(year)', portal)


# 3. Add the Modal HTML at the end of the template (before </template>)
modal_html = r'''
        <!-- Sync Classes Modal -->
        <div v-if="showSyncClassesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl animate-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-outline-variant/30 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Pilih Kelas Aktif</h3>
                        <p class="text-xs text-on-surface-variant">Tahun Ajaran: {{ syncTargetYear?.name }} ({{ syncTargetYear?.semester }})</p>
                    </div>
                    <button @click="closeSyncClassesModal" class="p-2 rounded-full hover:bg-surface-container-low text-on-surface-variant transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    <div v-if="isSyncingClasses" class="py-12 flex justify-center text-primary">
                        <span class="material-symbols-outlined animate-spin text-[32px]">progress_activity</span>
                    </div>
                    <div v-else class="space-y-6">
                        <div v-for="unit in syncMasterUnits" :key="unit.id" class="border border-outline-variant/30 rounded-xl overflow-hidden">
                            <div class="bg-surface-container-low px-4 py-2 font-bold text-sm border-b border-outline-variant/30 text-on-surface flex justify-between items-center">
                                <span>Unit: {{ unit.name }}</span>
                                <span class="text-xs font-normal text-on-surface-variant">{{ unit.classes.length }} Kelas Master</span>
                            </div>
                            <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <label v-for="cls in unit.classes" :key="cls.id" class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-surface-container-low transition-colors border border-transparent hover:border-outline-variant/30">
                                    <input type="checkbox" :value="unit.name + '|' + cls.name" v-model="selectedClasses" class="rounded text-primary focus:ring-primary/30 border-outline-variant">
                                    <span class="text-sm font-medium text-on-surface">Kelas {{ cls.name }}</span>
                                </label>
                                <div v-if="!unit.classes.length" class="text-xs text-on-surface-variant italic col-span-full">Belum ada master kelas di unit ini.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-outline-variant/30 flex justify-end gap-3 shrink-0 bg-surface-container-lowest rounded-b-2xl">
                    <button @click="closeSyncClassesModal" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-on-surface-variant hover:bg-surface-container-low transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button @click="submitSyncClasses" :disabled="isSyncingClasses" class="px-5 py-2.5 rounded-xl font-semibold text-sm bg-primary text-white hover:bg-primary-container disabled:opacity-50 transition-colors shadow-sm cursor-pointer">
                        Simpan Kelas Aktif ({{ selectedClasses.length }})
                    </button>
                </div>
            </div>
        </div>
'''

if "<!-- Sync Classes Modal -->" not in portal:
    portal = portal.replace("</template>", modal_html + "\n</template>")
    
with open(portal_path, 'w', encoding='utf-8') as f:
    f.write(portal)

print("Updated routes and Portal.vue with Modal Checkbox UI")
