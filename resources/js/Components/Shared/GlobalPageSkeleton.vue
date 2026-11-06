<script setup>
defineProps({
    type: {
        type: String,
        default: 'general', // 'detail' | 'table' | 'general'
    },
});
</script>

<template>
    <div class="w-full animate-pulse transition-opacity duration-200">
        <!-- DETAIL PAGE SKELETON (Untuk /staff/{id} dan /students/{id}) -->
        <div v-if="type === 'detail'" class="space-y-5 max-w-[1440px] mx-auto">
            <!-- Back Button Skeleton -->
            <div class="flex items-center gap-2">
                <div class="h-8 w-36 bg-slate-200 rounded-xl"></div>
            </div>

            <!-- Profile Header Card Skeleton -->
            <div class="bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-100/60 rounded-3xl border border-slate-200/90 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4 sm:gap-5 min-w-0 flex-1">
                    <!-- Avatar circle/rounded-2xl -->
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-200 shrink-0"></div>
                    <div class="space-y-2.5 min-w-0 flex-1">
                        <div class="h-6 sm:h-7 bg-slate-300 rounded-xl w-48 sm:w-72"></div>
                        <div class="h-4 bg-slate-200 rounded-lg w-32 sm:w-44"></div>
                        <div class="flex items-center gap-2 pt-1">
                            <div class="h-5 w-20 bg-emerald-100 rounded-full"></div>
                            <div class="h-5 w-24 bg-slate-200 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segmented Capsule Tabs Skeleton -->
            <div class="bg-slate-100 p-1.5 rounded-full inline-flex items-center gap-2 border border-slate-200/90 max-w-full overflow-hidden">
                <div class="h-8 w-24 bg-slate-200 rounded-full"></div>
                <div class="h-8 w-20 bg-slate-200/70 rounded-full hidden sm:block"></div>
                <div class="h-8 w-24 bg-slate-200/70 rounded-full hidden sm:block"></div>
                <div class="h-8 w-28 bg-slate-200/70 rounded-full hidden sm:block"></div>
            </div>

            <!-- Content Card Skeleton -->
            <div class="bg-white rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs space-y-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="h-5 w-44 bg-slate-200 rounded-lg"></div>
                    <div class="h-7 w-20 bg-slate-200 rounded-xl"></div>
                </div>

                <!-- 2-Col Grid Field Placeholders -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
                    <div v-for="i in 8" :key="i" class="space-y-2">
                        <div class="h-3 w-28 bg-slate-200 rounded"></div>
                        <div class="h-5 w-48 bg-slate-100 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE / LIST PAGE SKELETON (Untuk /staff dan /students) -->
        <div v-else-if="type === 'table'" class="space-y-5 max-w-[1440px] mx-auto">
            <!-- Header Title Skeleton -->
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="h-7 w-48 sm:w-64 bg-slate-300 rounded-xl"></div>
                    <div class="h-4 w-36 sm:w-52 bg-slate-200 rounded-lg"></div>
                </div>
                <div class="h-9 w-32 bg-slate-200 rounded-xl hidden sm:block"></div>
            </div>

            <!-- Unit Segment Tabs Skeleton -->
            <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-2 border border-slate-200">
                <div class="h-7 w-20 bg-slate-200 rounded-full"></div>
                <div class="h-7 w-20 bg-slate-200/70 rounded-full"></div>
                <div class="h-7 w-20 bg-slate-200/70 rounded-full"></div>
            </div>

            <!-- Search Bar & Filter Skeleton -->
            <div class="flex items-center gap-2.5">
                <div class="h-11 bg-slate-100 rounded-xl border border-slate-200 flex-1"></div>
                <div class="h-11 w-24 bg-slate-100 rounded-xl border border-slate-200 shrink-0"></div>
            </div>

            <!-- Table Outer Container Skeleton (rounded-xl selaras) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <!-- Header Table Placeholder -->
                <div class="h-11 bg-emerald-900/10 border-b border-slate-200 flex items-center px-4 gap-4">
                    <div class="w-8 h-4 bg-emerald-900/20 rounded"></div>
                    <div class="w-48 h-4 bg-emerald-900/20 rounded"></div>
                    <div class="w-32 h-4 bg-emerald-900/20 rounded hidden md:block"></div>
                    <div class="w-24 h-4 bg-emerald-900/20 rounded hidden md:block"></div>
                    <div class="w-32 h-4 bg-emerald-900/20 rounded hidden md:block ml-auto"></div>
                </div>

                <!-- Table Rows Skeleton -->
                <div class="divide-y divide-slate-100">
                    <div v-for="i in 7" :key="i" class="p-4 flex items-center gap-4">
                        <div class="w-6 h-4 bg-slate-200 rounded"></div>
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="h-4 bg-slate-200 rounded w-44"></div>
                            <div class="h-3 bg-slate-100 rounded w-28"></div>
                        </div>
                        <div class="w-28 h-4 bg-slate-100 rounded hidden md:block"></div>
                        <div class="w-20 h-5 bg-slate-100 rounded-full hidden md:block"></div>
                        <div class="w-28 h-4 bg-slate-100 rounded hidden md:block ml-auto"></div>
                    </div>
                </div>

                <!-- Pagination Footer Skeleton -->
                <div class="p-4 border-t border-slate-200 flex items-center justify-between bg-slate-50/50">
                    <div class="h-4 w-36 bg-slate-200 rounded"></div>
                    <div class="h-8 w-48 bg-slate-200 rounded-xl"></div>
                </div>
            </div>
        </div>

        <!-- GENERAL PAGE SKELETON (Untuk halaman lainnya) -->
        <div v-else class="space-y-6 max-w-7xl mx-auto">
            <div class="h-8 w-56 bg-slate-300 rounded-xl"></div>
            <div class="h-36 sm:h-48 bg-gradient-to-r from-emerald-900/10 to-teal-900/10 rounded-3xl border border-slate-200"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div v-for="i in 6" :key="i" class="h-28 bg-slate-100 rounded-2xl border border-slate-200"></div>
            </div>
        </div>
    </div>
</template>
