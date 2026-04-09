<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { watch, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const showPassword = ref(false);

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    profile_photo_url?: string | null;
    roles: Role[];
}

const props = defineProps<{
    open: boolean;
    editingUser?: User | null;
    postUrl: string;
    putUrl?: string;
    roles: Role[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    profile_photo: null as File | null,
    is_active: true,
    role: '',
});

const fileInputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const selectedFileName = ref<string>('');

watch(
    () => props.editingUser,
    (user) => {
        if (user) {
            form.name = user.name;
            form.email = user.email;
            form.password = '';
            form.profile_photo = null;
            form.is_active = user.is_active;
            form.role = user.roles[0]?.name ?? '';
            previewUrl.value = user.profile_photo_url ?? null;
            selectedFileName.value = '';
        } else {
            form.reset();
            previewUrl.value = null;
            selectedFileName.value = '';
        }
        form.clearErrors();
    },
    { immediate: true },
);

const isEditing = () => !!props.editingUser;

function triggerFileInput() {
    fileInputRef.value?.click();
}

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    form.profile_photo = file;
    selectedFileName.value = file.name;

    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function removePhoto() {
    previewUrl.value = null;
    selectedFileName.value = '';
    form.profile_photo = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

const handleSubmit = () => {
    if (isEditing() && props.editingUser) {
        form.put(`${props.putUrl ?? props.postUrl}/${props.editingUser.id}`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                form.reset();
                previewUrl.value = null;
                selectedFileName.value = '';
            },
        });
    } else {
        form.post(props.postUrl, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                form.reset();
                previewUrl.value = null;
                selectedFileName.value = '';
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="overflow-hidden rounded-2xl p-0 sm:max-w-md">
            <div class="p-6 pb-0">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-gray-900">
                        {{ isEditing() ? 'Edit User' : 'Tambah User Baru' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <div class="grid gap-5 p-5">
                <!-- Nama -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Nama <span class="text-red-500">*</span>
                    </Label>
                    <Input v-model="form.name" placeholder="Nama Lengkap"
                        :class="{ 'border-red-400': form.errors.name }" autocomplete="name" />
                    <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                </div>

                <!-- Email -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Email <span class="text-red-500">*</span>
                    </Label>
                    <Input v-model="form.email" type="email" placeholder="email@contoh.com"
                        :class="{ 'border-red-400': form.errors.email }" autocomplete="email" />
                    <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                </div>

                <!-- Role -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Role <span class="text-red-500">*</span>
                    </Label>
                    <select v-model="form.role" :class="[
                        'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none',
                        form.errors.role ? 'border-red-400' : 'border-gray-200'
                    ]">
                        <option value="" disabled>Pilih role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.name">
                            {{ role.name }}
                        </option>
                    </select>
                    <span v-if="form.errors.role" class="text-xs text-red-500">{{ form.errors.role }}</span>
                </div>

                <!-- Password -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Password
                        <span v-if="!isEditing()" class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                            :class="{ 'border-red-400': form.errors.password }" autocomplete="new-password"
                            class="pr-10" />
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600">
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <span v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</span>
                    <span v-if="isEditing()" class="text-xs text-gray-400">
                        Biarkan kosong jika tidak ingin mengubah password.
                    </span>
                    <div v-if="form.password" class="mt-1 flex flex-wrap gap-2 text-xs">
                        <span class="flex items-center gap-1"
                            :class="form.password.length >= 8 ? 'text-green-600' : 'text-gray-400'">
                            <svg v-if="form.password.length >= 8" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Min 8 Karakter
                        </span>
                        <span class="flex items-center gap-1"
                            :class="/[A-Z]/.test(form.password) ? 'text-green-600' : 'text-gray-400'">
                            <svg v-if="/[A-Z]/.test(form.password)" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Huruf Besar
                        </span>
                        <span class="flex items-center gap-1"
                            :class="/[0-9]/.test(form.password) ? 'text-green-600' : 'text-gray-400'">
                            <svg v-if="/[0-9]/.test(form.password)" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Angka
                        </span>
                        <span class="flex items-center gap-1"
                            :class="/[^A-Za-z0-9]/.test(form.password) ? 'text-green-600' : 'text-gray-400'">
                            <svg v-if="/[^A-Za-z0-9]/.test(form.password)" class="h-3 w-3" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Simbol (!@#$...)
                        </span>
                    </div>
                </div>

                <!-- Foto Profil -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">Foto Profil</Label>
                    <div class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-input p-5 transition-colors hover:bg-muted/40"
                        @click="triggerFileInput">
                        <div v-if="previewUrl" class="relative">
                            <img :src="previewUrl" alt="Profile preview"
                                class="h-20 w-20 rounded-full border border-border object-cover" />
                            <button type="button"
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-border bg-background text-xs text-muted-foreground hover:text-foreground"
                                @click.stop="removePhoto">✕</button>
                        </div>
                        <div v-else
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-muted">
                            <svg class="h-5 w-5 text-muted-foreground" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-foreground">
                                {{ previewUrl ? 'Klik untuk ganti foto' : 'Klik untuk upload foto' }}
                            </p>
                            <p v-if="selectedFileName"
                                class="mt-0.5 max-w-[200px] truncate text-xs text-muted-foreground">{{ selectedFileName
                                }}</p>
                            <p v-else class="text-xs text-muted-foreground">PNG, JPG, WebP maks. 2MB</p>
                        </div>
                        <input ref="fileInputRef" type="file" accept="image/*" class="hidden"
                            @change="handleFileChange" />
                    </div>
                    <span v-if="form.errors.profile_photo" class="text-xs text-red-500">{{ form.errors.profile_photo
                    }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5">
                <Button variant="outline" class="w-full rounded" @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button @click="handleSubmit" :disabled="form.processing"
                    class="w-full rounded bg-primary text-white hover:bg-primary-hover">
                    {{ isEditing() ? 'Simpan Perubahan' : 'Simpan' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>