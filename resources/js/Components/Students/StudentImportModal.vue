<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'success']);

const fileInputRef = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);

const form = useForm({
    file: null,
});

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function triggerFileInput() {
    fileInputRef.value?.click();
}

function handleFileChange(event) {
    const files = event.target.files;
    if (files && files.length > 0) {
        setFile(files[0]);
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
            setFile(file);
        } else {
            form.setError('file', 'File harus berformat Excel (.xlsx atau .xls)');
        }
    }
}

function setFile(file) {
    selectedFile.value = file;
    form.file = file;
    form.clearErrors('file');
}

function removeFile() {
    selectedFile.value = null;
    form.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function submitImport() {
    if (!form.file) return;

    form.post('/students/import', {
        preserveScroll: true,
        onSuccess: () => {
            removeFile();
            emit('success');
            emit('close');
        },
    });
}

function closeModal() {
    if (form.processing) return;
    removeFile();
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity"
            @click="closeModal"
        />

        <!-- Modal Content -->
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-[0px_20px_50px_rgba(0,0,0,0.15)] border border-outline-variant/40 w-full max-w-xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="px-6 pt-6 pb-4 border-b border-outline-variant/30 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Import Data Siswa</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">
                            Unggah file Excel multi-sheet untuk memperbarui atau menambahkan data peserta didik.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    :disabled="form.processing"
                    class="text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-variant transition-colors disabled:opacity-50"
                >
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- Info Box -->
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-primary/15 flex items-start gap-3 text-xs text-on-surface-variant">
                    <span class="material-symbols-outlined text-primary text-[20px] shrink-0 mt-0.5">info</span>
                    <div class="space-y-1">
                        <p>
                            Gunakan format file resmi dengan 5 sheet: <strong>Data Siswa, Alamat, Keluarga, Saudara,</strong> dan <strong>Data Akademik</strong>. Kunci relasi antar sheet adalah <strong>NISN</strong>.
                        </p>
                        <div class="pt-1">
                            <a
                                href="/students/template"
                                download
                                class="inline-flex items-center gap-1 font-semibold text-primary hover:underline"
                            >
                                <span class="material-symbols-outlined text-[15px]">download</span>
                                Unduh Template XLSX Resmi
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Error alert -->
                <div
                    v-if="form.errors.file"
                    class="p-3 bg-error/10 border border-error/20 rounded-xl flex items-center gap-2 text-xs text-error font-medium"
                >
                    <span class="material-symbols-outlined text-base shrink-0">error</span>
                    <span>{{ form.errors.file }}</span>
                </div>

                <!-- File Input (Hidden) -->
                <input
                    ref="fileInputRef"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="handleFileChange"
                />

                <!-- Dropzone (if no file chosen) -->
                <div
                    v-if="!selectedFile"
                    @click="triggerFileInput"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    :class="[
                        'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200',
                        isDragging
                            ? 'border-primary bg-primary/5 ring-4 ring-primary/10'
                            : 'border-outline-variant/70 hover:border-primary hover:bg-surface-container-low'
                    ]"
                >
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center text-outline">
                            <span class="material-symbols-outlined text-3xl">cloud_upload</span>
                        </div>
                        <div class="mt-1">
                            <p class="text-sm font-semibold text-on-surface">
                                Tarik & letakkan file template Excel di sini
                            </p>
                            <p class="text-xs text-on-surface-variant mt-0.5">
                                atau <span class="text-primary font-bold hover:underline">Pilih dari perangkat Anda</span>
                            </p>
                        </div>
                        <span class="text-[11px] text-outline mt-1">Hanya mendukung format .xlsx atau .xls (Maks. 10 MB)</span>
                    </div>
                </div>

                <!-- Selected File Preview -->
                <div
                    v-else
                    class="p-4 bg-surface-container-low border border-primary/20 rounded-xl flex items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-primary/15 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">table_view</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-on-surface truncate">{{ selectedFile.name }}</p>
                            <p class="text-xs text-on-surface-variant">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            v-if="!form.processing"
                            type="button"
                            @click="triggerFileInput"
                            class="text-xs font-semibold text-primary hover:bg-primary/10 px-2.5 py-1.5 rounded-lg transition-colors"
                        >
                            Ganti
                        </button>
                        <button
                            v-if="!form.processing"
                            type="button"
                            @click="removeFile"
                            class="text-xs font-semibold text-error hover:bg-error/10 p-1.5 rounded-lg transition-colors"
                            title="Hapus file"
                        >
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div v-if="form.processing" class="space-y-2 pt-2">
                    <div class="flex justify-between text-xs font-semibold text-primary">
                        <span>Sedang mengimpor dan memproses data...</span>
                        <span v-if="form.progress">{{ form.progress.percentage }}%</span>
                    </div>
                    <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                        <div
                            class="h-full bg-primary rounded-full transition-all duration-300"
                            :style="{ width: `${form.progress ? form.progress.percentage : 100}%` }"
                            :class="{ 'animate-pulse': !form.progress }"
                        />
                    </div>
                    <p class="text-[11px] text-outline text-center">Jangan menutup halaman saat proses impor sedang berlangsung.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/30 flex items-center justify-end gap-3">
                <button
                    type="button"
                    @click="closeModal"
                    :disabled="form.processing"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-on-surface hover:bg-surface-variant border border-outline-variant transition-colors disabled:opacity-50 cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="submitImport"
                    :disabled="!selectedFile || form.processing"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-semibold text-on-primary bg-primary hover:bg-primary-container disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-xs cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">
                        {{ form.processing ? 'sync' : 'upload' }}
                    </span>
                    <span>{{ form.processing ? 'Mengimpor...' : 'Mulai Import Data' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
