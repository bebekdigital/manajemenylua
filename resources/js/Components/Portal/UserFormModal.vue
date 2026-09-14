<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editing: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'guru',
});

// Watch for changes when modal opens/closes or when editing state changes
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            if (props.editing) {
                // Edit mode
                form.name = props.editing.name;
                form.username = props.editing.username;
                form.email = props.editing.email || '';
                form.role = props.editing.role;
                form.password = ''; // Don't show password
            } else {
                // Create mode
                form.reset();
            }
            form.clearErrors();
        }
    }
);

function submit() {
    if (props.editing) {
        form.put(`/portal/users/${props.editing.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
            },
        });
    } else {
        form.post('/portal/users', {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
            },
        });
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <!-- Backdrop -->
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
                @click="emit('close')"
            ></div>

            <!-- Modal Panel -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div class="bg-surface-container-lowest rounded-3xl shadow-xl border border-outline-variant/30 overflow-hidden w-full max-w-md relative z-10 transform transition-all">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-outline-variant/30 flex items-center justify-between bg-surface-container-low/30">
                        <h3 class="text-lg font-bold text-on-surface">
                            {{ editing ? 'Edit Akun Pengguna' : 'Buat Akun Baru' }}
                        </h3>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <!-- Nama Lengkap -->
                        <div>
                            <label for="name" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Nama Lengkap
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full px-3.5 py-2.5 bg-surface-container hover:bg-surface-container-high focus:bg-white text-sm font-medium rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-on-surface placeholder:text-on-surface-variant/50"
                                placeholder="Contoh: Fathudin Mahmud"
                            >
                            <div v-if="form.errors.name" class="text-error text-xs mt-1.5 ml-1 font-medium">{{ form.errors.name }}</div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Username / NIP
                            </label>
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="w-full px-3.5 py-2.5 bg-surface-container hover:bg-surface-container-high focus:bg-white text-sm font-medium rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-on-surface placeholder:text-on-surface-variant/50"
                                placeholder="Contoh: 19850312001"
                            >
                            <div v-if="form.errors.username" class="text-error text-xs mt-1.5 ml-1 font-medium">{{ form.errors.username }}</div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Email (Opsional)
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full px-3.5 py-2.5 bg-surface-container hover:bg-surface-container-high focus:bg-white text-sm font-medium rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-on-surface placeholder:text-on-surface-variant/50"
                                placeholder="Contoh: guru@sekolah.com"
                            >
                            <div v-if="form.errors.email" class="text-error text-xs mt-1.5 ml-1 font-medium">{{ form.errors.email }}</div>
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Role / Kewenangan
                            </label>
                            <select
                                id="role"
                                v-model="form.role"
                                class="w-full px-3.5 py-2.5 bg-surface-container hover:bg-surface-container-high focus:bg-white text-sm font-medium rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-on-surface appearance-none"
                            >
                                <option value="guru">Guru</option>
                                <option value="superadmin">Superadmin</option>
                            </select>
                            <div v-if="form.errors.role" class="text-error text-xs mt-1.5 ml-1 font-medium">{{ form.errors.role }}</div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Kata Sandi {{ editing ? '(Opsional)' : '' }}
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="w-full px-3.5 py-2.5 bg-surface-container hover:bg-surface-container-high focus:bg-white text-sm font-medium rounded-xl border border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-on-surface placeholder:text-on-surface-variant/50"
                                placeholder="••••••••••••"
                            >
                            <p v-if="editing" class="text-[10px] text-on-surface-variant/70 mt-1 ml-1">
                                Kosongkan jika tidak ingin mengubah kata sandi.
                            </p>
                            <div v-if="form.errors.password" class="text-error text-xs mt-1.5 ml-1 font-medium">{{ form.errors.password }}</div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-2 flex items-center justify-end gap-3 border-t border-outline-variant/30 mt-6 pt-5">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="px-5 py-2.5 rounded-xl font-semibold text-sm text-on-surface-variant hover:bg-surface-variant hover:text-on-surface transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm bg-primary text-on-primary hover:bg-primary-container hover:text-primary transition-colors cursor-pointer shadow-xs shadow-primary/20 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[18px]">save</span>
                                <span>Simpan Data</span>
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
