import sys

with open('resources/js/Components/Students/StudentToolbar.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the Reset Filter Button
reset_block = r'''                <!-- Reset Filter Button (Icon only on desktop) -->
                <div class="w-full lg:w-auto mt-1 lg:mt-0 flex justify-end shrink-0" v-if="hasActiveFilters">
                    <button
                        type="button"
                        @click="resetAll"
                        class="inline-flex items-center justify-center gap-1 w-full lg:w-9 lg:h-9 lg:p-0 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-semibold text-error hover:bg-error-container/30 border border-error/30 transition-colors cursor-pointer"
                        title="Hapus seluruh filter dan pencarian"
                    >
                        <span class="material-symbols-outlined text-[15px] sm:text-[18px]">restart_alt</span>
                        <span class="lg:hidden">Reset Filter</span>
                    </button>
                </div>'''
content = content.replace(reset_block, '')

# Remove the Filter Summary Block
summary_block = r'''        <!-- Result Counter / Filter Summary -->
        <div v-if="hasActiveFilters" class="pt-2 sm:pt-3 border-t border-outline-variant/30 text-[10px] sm:text-xs text-on-surface-variant font-medium flex justify-start sm:justify-end shrink-0">
            <span class="inline-flex items-center gap-1">
                Menampilkan <strong class="text-primary font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} siswa
            </span>
        </div>'''
content = content.replace(summary_block, '')

with open('resources/js/Components/Students/StudentToolbar.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print('Removed the requested elements.')
