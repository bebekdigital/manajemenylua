import sys

students_path = 'resources/js/Pages/Students.vue'
with open(students_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add link to dropdown
search_str = r'''                    <div v-show="showEditDropdown" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden py-1">
                        <Link
                            href="/students/inline-edit"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-on-surface hover:bg-surface-container-low transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">edit_square</span>
                            Edit Massal (Excel)
                        </Link>
                    </div>'''

replace_str = r'''                    <div v-show="showEditDropdown" class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden py-1">
                        <Link
                            href="/students/inline-edit"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-on-surface hover:bg-surface-container-low transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">edit_square</span>
                            Edit Massal (Excel)
                        </Link>
                        <Link
                            href="/students/mapping"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-on-surface hover:bg-surface-container-low transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">route</span>
                            Petakan Riwayat (Bulking)
                        </Link>
                    </div>'''

if "Petakan Riwayat" not in content:
    content = content.replace(search_str, replace_str)
    with open(students_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Updated Students.vue")
