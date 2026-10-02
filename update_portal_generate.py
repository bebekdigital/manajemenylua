import sys
import os

routes_path = 'routes/web.php'
with open(routes_path, 'r', encoding='utf-8') as f:
    routes = f.read()

if "use App\\Http\\Controllers\\AcademicYearClassController;" not in routes:
    routes = routes.replace("use App\\Http\\Controllers\\AcademicYearController;", "use App\\Http\\Controllers\\AcademicYearController;\nuse App\\Http\\Controllers\\AcademicYearClassController;")
    
if "academic-years/{academicYear}/generate-classes" not in routes:
    search_str = "Route::post('/portal/academic-years/{academicYear}/set-active', [AcademicYearController::class, 'setActive'])->name('academic-years.set-active');"
    insert_str = search_str + "\n    Route::post('/portal/academic-years/{academicYear}/generate-classes', [AcademicYearClassController::class, 'generate'])->name('academic-years.generate-classes');"
    
    routes = routes.replace(search_str, insert_str)
    with open(routes_path, 'w', encoding='utf-8') as f:
        f.write(routes)

portal_path = 'resources/js/Pages/Portal.vue'
with open(portal_path, 'r', encoding='utf-8') as f:
    portal = f.read()

search_button = r'''                                        @click="openEditModal(year)"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                                        title="Edit Tahun Ajaran"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>'''

replace_button = search_button + r'''
                                    <button
                                        type="button"
                                        @click="generateClasses(year)"
                                        :disabled="generatingId === year.id"
                                        class="p-2 rounded-lg text-on-surface-variant hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                        title="Generate/Sinkronisasi Kelas dari Master Data"
                                    >
                                        <span v-if="generatingId === year.id" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[18px]">auto_awesome</span>
                                    </button>'''

if "generateClasses(year)" not in portal:
    portal = portal.replace(search_button, replace_button)
    
    search_script = "const deletingId = ref(null);"
    replace_script = search_script + "\nconst generatingId = ref(null);"
    portal = portal.replace(search_script, replace_script)
    
    search_script2 = r'''function setActive(year) {'''
    replace_script2 = r'''function generateClasses(year) {
    if (!confirm(`Generate seluruh kelas dari Master Data untuk TA "${year.name} ${year.semester}"?\n(Kelas yang sudah ada tidak akan dihapus)`)) {
        return;
    }
    generatingId.value = year.id;
    dismissedFlash.value = false;
    router.post(`/portal/academic-years/${year.id}/generate-classes`, {}, {
        preserveScroll: true,
        onFinish: () => {
            generatingId.value = null;
        }
    });
}

function setActive(year) {'''
    portal = portal.replace(search_script2, replace_script2)
    
    with open(portal_path, 'w', encoding='utf-8') as f:
        f.write(portal)

print("Updated routes and Portal.vue")
