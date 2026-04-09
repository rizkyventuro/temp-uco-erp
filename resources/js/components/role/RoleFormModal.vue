<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { X, Check } from 'lucide-vue-next';

interface PermissionItem { id: number; name: string }
interface PermissionGroup { group: string; items: PermissionItem[] }
interface RoleItem {
    id: number;
    name: string;
    permissions_count: number;
    permissions: string[];
}

const props = defineProps<{
    show: boolean;
    permissions: PermissionGroup[];
    editTarget: RoleItem | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({ name: '', permissions: [] as string[] });

// Sync form saat editTarget berubah
watch(() => props.editTarget, (val) => {
    if (val) {
        form.name = val.name;
        form.permissions = [...val.permissions];
    } else {
        form.reset();
    }
}, { immediate: true });

// Reset saat modal ditutup
watch(() => props.show, (val) => {
    if (!val) form.reset();
});

function close() {
    emit('close');
}

function togglePermission(name: string) {
    const idx = form.permissions.indexOf(name);
    idx === -1 ? form.permissions.push(name) : form.permissions.splice(idx, 1);
}

function toggleGroup(group: PermissionGroup) {
    const allChecked = group.items.every(p => form.permissions.includes(p.name));
    if (allChecked) {
        form.permissions = form.permissions.filter(p => !group.items.find(i => i.name === p));
    } else {
        group.items.forEach(p => {
            if (!form.permissions.includes(p.name)) form.permissions.push(p.name);
        });
    }
}

function isGroupChecked(group: PermissionGroup) {
    return group.items.every(p => form.permissions.includes(p.name));
}

function isGroupIndeterminate(group: PermissionGroup) {
    const checked = group.items.filter(p => form.permissions.includes(p.name)).length;
    return checked > 0 && checked < group.items.length;
}

function submit() {
    if (props.editTarget) {
        form.patch(`/roles/${props.editTarget.id}`, { onSuccess: close });
    } else {
        form.post('/roles', { onSuccess: close });
    }
}

function selectAll() {
    form.permissions = props.permissions.flatMap(g => g.items.map(p => p.name));
}

function clearAll() {
    form.permissions = [];
}

function formatLabel(name: string) {
    const map: Record<string, string> = {
        view: 'Lihat', create: 'Tambah', edit: 'Ubah', delete: 'Hapus',
    };
    const [action] = name.split(' ');
    return map[action] ?? action;
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                @click.self="close">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl flex flex-col"
                    style="height: 90vh; max-height: 90vh;"> <!-- ✅ pakai style eksplisit -->

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                        <h3 class="text-base font-bold text-gray-800">
                            {{ editTarget ? 'Edit Role' : 'Tambah Role' }}
                        </h3>
                        <button @click="close"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body ✅ overflow-y-auto + min-h-0 agar flex child bisa scroll -->
                    <div class="flex-1 min-h-0 overflow-y-auto px-6 py-4">

                        <!-- Nama role -->
                        <div class="mb-5">
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Nama Role
                            </label>
                            <input v-model="form.name" type="text" disabled placeholder="Masukkan Nama Role"
                                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Permission groups -->
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Permission
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAll"
                                        class="rounded-lg border border-[#007C95] px-3 py-1 text-xs font-medium text-[#007C95] hover:bg-[#007C95]/5 transition">
                                        Pilih Semua
                                    </button>
                                    <button type="button" @click="clearAll"
                                        class="rounded-lg border border-gray-200 px-3 py-1 text-xs font-medium text-gray-500 hover:bg-gray-50 transition">
                                        Hapus Semua
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <div v-for="group in permissions" :key="group.group"
                                    class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                    <!-- Group toggle -->
                                    <label class="mb-3 flex cursor-pointer items-center gap-2">
                                        <input type="checkbox" :checked="isGroupChecked(group)"
                                            :indeterminate="isGroupIndeterminate(group)" @change="toggleGroup(group)"
                                            class="h-4 w-4 rounded accent-[#007C95] cursor-pointer" />
                                        <span class="text-sm font-semibold capitalize text-gray-700">
                                            {{ group.group }}
                                        </span>
                                        <span class="ml-auto text-xs text-gray-400">
                                            {{group.items.filter(p => form.permissions.includes(p.name)).length}}
                                            / {{ group.items.length }}
                                        </span>
                                    </label>

                                    <!-- Permission chips -->
                                    <div class="flex flex-wrap gap-2 pl-6">
                                        <label v-for="perm in group.items" :key="perm.id" :class="[
                                            'flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                                            form.permissions.includes(perm.name)
                                                ? 'border-[#007C95] bg-[#007C95]/5 text-[#007C95]'
                                                : 'border-gray-200 bg-white text-gray-500 hover:border-[#007C95]/40',
                                        ]">
                                            <input type="checkbox" :checked="form.permissions.includes(perm.name)"
                                                @change="togglePermission(perm.name)" class="hidden" />
                                            <Check v-if="form.permissions.includes(perm.name)"
                                                class="h-3 w-3 shrink-0" />
                                            {{ formatLabel(perm.name) }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex gap-3 border-t border-gray-100 px-6 py-4 shrink-0">
                        <button @click="close"
                            class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button @click="submit" :disabled="form.processing"
                            class="flex-1 rounded-xl bg-[#007C95] py-2.5 text-sm font-semibold text-white hover:bg-[#006880] disabled:opacity-60 transition">
                            {{ editTarget ? 'Simpan Perubahan' : 'Buat Role' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>