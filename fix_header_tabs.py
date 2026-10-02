import sys

files = [
    'resources/js/Pages/Students.vue',
    'resources/js/Pages/Statistics/StudentStatistics.vue'
]

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'Students.vue' in file:
        old_header = r'''        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12 border-b border-outline-variant/30 pb-0">
            <div class="pb-4 sm:pb-3 shrink-0">
                <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                    Database Siswa
                </h2>
                <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                    Kelola dan tinjau seluruh data peserta didik sesuai Tahun Ajaran aktif
                </p>
            </div>
            
            <!-- Unit Tabs (Tab Kotak2 Style) -->
            <div class="flex overflow-x-auto -mb-px">'''
        new_header = r'''        <!-- Page Header -->
        <div class="mb-6 border-b border-outline-variant/30">
            <div class="flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12">
                <div class="pb-3 shrink-0">
                    <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                        Database Siswa
                    </h2>
                    <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                        Kelola dan tinjau seluruh data peserta didik sesuai Tahun Ajaran aktif
                    </p>
                </div>
                
                <!-- Unit Tabs (Tab Kotak2 Style) -->
                <div class="flex overflow-x-auto -mb-px">'''
        content = content.replace(old_header, new_header)
        
        # In case the old header didn't match (because maybe I didn't get the exact whitespace)
        # Let's do a more robust replace
        content = content.replace(
            '<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12 border-b border-outline-variant/30 pb-0">',
            '<div class="mb-6 border-b border-outline-variant/30">\n            <div class="flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12">'
        )
        content = content.replace('            <!-- Unit Tabs (Tab Kotak2 Style) -->\n            <div class="flex overflow-x-auto -mb-px">', '            <!-- Unit Tabs (Tab Kotak2 Style) -->\n                <div class="flex overflow-x-auto -mb-px">')
        content = content.replace('                </button>\n            </div>\n        </div>', '                </button>\n                </div>\n            </div>\n        </div>')

    else:
        old_header = r'''            <!-- Page Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12 border-b border-outline-variant/30 pb-0">
                <div class="pb-4 sm:pb-3 shrink-0">
                    <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                        Rincian Statistik
                    </h2>
                    <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                        Ringkasan data statistik
                        <span v-if="selectedAcademicYear" class="font-semibold text-primary">
                            — TA {{ selectedAcademicYear.name }} ({{ selectedAcademicYear.semester }})
                        </span>
                    </p>
                </div>
                
                <!-- Category Tabs (Tab Kotak2 Style) -->
                <div class="flex overflow-x-auto -mb-px">'''
        new_header = r'''            <!-- Page Header -->
            <div class="mb-6 border-b border-outline-variant/30">
                <div class="flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12">
                    <div class="pb-3 shrink-0">
                        <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                            Rincian Statistik
                        </h2>
                        <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                            Ringkasan data statistik
                            <span v-if="selectedAcademicYear" class="font-semibold text-primary">
                                — TA {{ selectedAcademicYear.name }} ({{ selectedAcademicYear.semester }})
                            </span>
                        </p>
                    </div>
                    
                    <!-- Category Tabs (Tab Kotak2 Style) -->
                    <div class="flex overflow-x-auto -mb-px">'''
        content = content.replace(old_header, new_header)

        content = content.replace(
            '<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12 border-b border-outline-variant/30 pb-0">',
            '<div class="mb-6 border-b border-outline-variant/30">\n                <div class="flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12">'
        )
        content = content.replace('                    </button>\n                </div>\n            </div>', '                    </button>\n                    </div>\n                </div>\n            </div>')


    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
