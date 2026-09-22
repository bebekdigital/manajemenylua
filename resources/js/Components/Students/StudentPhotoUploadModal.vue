<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'success']);

const files = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);

const fileCount = computed(() => files.value.length);

function onDragOver(e) {
    e.preventDefault();
    isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function onDrop(e) {
    e.preventDefault();
    isDragging.value = false;
    addFiles(e.dataTransfer.files);
}

function onFileSelect(e) {
    addFiles(e.target.files);
    e.target.value = '';
}

function addFiles(fileList) {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    for (const file of fileList) {
        if (allowedTypes.includes(file.type)) {
            const alreadyAdded = files.value.some(f => f.name === file.name && f.size === file.size);
            if (!alreadyAdded) {
                files.value.push(file);
            }
        }
    }
}

function removeFile(index) {
    files.value.splice(index, 1);
}

function clearFiles() {
    files.value = [];
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function extractNisnFromName(filename) {
    const name = filename.replace(/\.[^/.]+$/, '');
    const match = name.match(/\d{10}/);
    return match ? match[0] : null;
}

function uploadPhotos() {
    if (files.value.length === 0 || isUploading.value) return;

    isUploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    files.value.forEach((file) => {
        formData.append('photos[]', file);
    });

    router.post('/students/upload-photos', formData, {
        forceFormData: true,
        onProgress: (progress) => {
            uploadProgress.value = progress.percentage ?? 0;
        },
        onSuccess: () => {
            isUploading.value = false;
            files.value = [];
            emit('success');
            emit('close');
        },
        onError: () => {
            isUploading.value = false;
        },
    });
}

function close() {
    if (!isUploading.value) {
        files.value = [];
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="close"
            >
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-2xl bg-surface rounded-3xl shadow-2xl overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 bg-surface-container-low">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 bg-primary-container text-on-primary-container rounded-xl flex items-center justify-center">
                                    <span class="material-symbols-outlined text-lg">add_a_photo</span>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-on-surface">Upload Foto Siswa</h3>
                                    <p class="text-xs text-on-surface-variant">Nama file harus mengandung NISN siswa</p>
                                </div>
                            </div>
                            <button
                                @click="close"
                                :disabled="isUploading"
                                class="p-1.5 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                            <!-- Info banner -->
                            <div class="flex items-start gap-3 p-3 bg-primary/5 border border-primary/15 rounded-xl">
                                <span class="material-symbols-outlined text-primary text-lg mt-0.5 shrink-0">info</span>
                                <div class="text-xs text-on-surface-variant leading-relaxed">
                                    <p class="font-semibold text-on-surface mb-0.5">Format nama file:</p>
                                    <p>Nama file harus <strong>mengandung NISN</strong> (10 digit). Contoh:
                                        <code class="bg-surface-container px-1.5 py-0.5 rounded text-primary font-mono text-[11px]">0051234001.jpg</code>,
                                        <code class="bg-surface-container px-1.5 py-0.5 rounded text-primary font-mono text-[11px]">0051234001_Ahmad.png</code>
                                    </p>
                                </div>
                            </div>

                            <!-- Drop Zone -->
                            <div
                                @dragover="onDragOver"
                                @dragleave="onDragLeave"
                                @drop="onDrop"
                                :class="[
                                    'relative border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer',
                                    isDragging
                                        ? 'border-primary bg-primary/5 scale-[1.01]'
                                        : 'border-outline-variant/50 hover:border-primary/40 hover:bg-primary/[0.02]',
                                ]"
                                @click="$refs.fileInput.click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="onFileSelect"
                                />
                                <span class="material-symbols-outlined text-4xl mb-2" :class="isDragging ? 'text-primary' : 'text-on-surface-variant/50'">
                                    cloud_upload
                                </span>
                                <p class="text-sm font-semibold text-on-surface">
                                    Drag & drop foto siswa ke sini
                                </p>
                                <p class="text-xs text-on-surface-variant mt-1">
                                    atau <span class="text-primary font-semibold underline cursor-pointer">klik untuk pilih file</span>
                                </p>
                                <p class="text-[11px] text-on-surface-variant/60 mt-2">
                                    Format: JPG, PNG, WEBP — Maks 5MB per file
                                </p>
                            </div>

                            <!-- File List -->
                            <div v-if="fileCount > 0" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-on-surface">
                                        {{ fileCount }} file dipilih
                                    </p>
                                    <button
                                        @click="clearFiles"
                                        class="text-xs text-error font-medium hover:underline cursor-pointer"
                                    >
                                        Hapus Semua
                                    </button>
                                </div>

                                <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                    <div
                                        v-for="(file, index) in files"
                                        :key="index"
                                        class="flex items-center gap-3 p-2.5 bg-surface-container-low rounded-xl border border-outline-variant/20 group"
                                    >
                                        <span class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-primary text-base">image</span>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-on-surface truncate">{{ file.name }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] text-on-surface-variant">{{ formatSize(file.size) }}</span>
                                                <span
                                                    v-if="extractNisnFromName(file.name)"
                                                    class="text-[10px] font-mono px-1.5 py-0.5 bg-primary/10 text-primary rounded-md"
                                                >
                                                    NISN: {{ extractNisnFromName(file.name) }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-[10px] px-1.5 py-0.5 bg-error/10 text-error rounded-md font-medium"
                                                >
                                                    NISN tidak terdeteksi
                                                </span>
                                            </div>
                                        </div>
                                        <button
                                            @click.stop="removeFile(index)"
                                            class="p-1 rounded-lg text-on-surface-variant/50 hover:text-error hover:bg-error/10 transition-colors cursor-pointer opacity-0 group-hover:opacity-100"
                                        >
                                            <span class="material-symbols-outlined text-base">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Progress -->
                            <div v-if="isUploading" class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-on-surface-variant font-medium">Mengupload...</span>
                                    <span class="text-primary font-bold">{{ Math.round(uploadProgress) }}%</span>
                                </div>
                                <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-primary rounded-full transition-all duration-300"
                                        :style="{ width: uploadProgress + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low">
                            <button
                                @click="close"
                                :disabled="isUploading"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                @click="uploadPhotos"
                                :disabled="fileCount === 0 || isUploading"
                                class="flex items-center gap-2 px-5 py-2 bg-primary hover:bg-primary/90 text-on-primary rounded-xl text-sm font-bold transition-all active:scale-95 cursor-pointer shadow-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                            >
                                <span class="material-symbols-outlined text-base">upload</span>
                                <span v-if="!isUploading">Upload {{ fileCount }} Foto</span>
                                <span v-else>Mengupload...</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
