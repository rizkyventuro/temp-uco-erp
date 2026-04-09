<script setup lang="ts">
import { computed } from 'vue';

interface Permission {
    name: string;
}

interface PermissionGroup {
    id: number;
    name: string;
    key: string;
    permissions: Permission[];
}

const props = defineProps<{
    modelValue: string[];          // permission yang dicentang (role + extra)
    disabledPermissions: string[]; // permission bawaan role → disabled
    groups: PermissionGroup[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const checked = computed(() => new Set(props.modelValue));
const disabled = computed(() => new Set(props.disabledPermissions));

function toggle(permName: string) {
    if (disabled.value.has(permName)) return;
    const next = new Set(checked.value);
    if (next.has(permName)) {
        next.delete(permName);
    } else {
        next.add(permName);
    }
    emit('update:modelValue', Array.from(next));
}

// Untuk satu group: apakah SEMUA permission sudah tercentang?
function isGroupAllChecked(group: PermissionGroup): boolean {
    return group.permissions.every(p => checked.value.has(p.name));
}

// Toggle group — hanya operasi pada yang tidak disabled
function toggleGroup(group: PermissionGroup) {
    const allChecked = isGroupAllChecked(group);
    const next = new Set(checked.value);
    for (const p of group.permissions) {
        if (disabled.value.has(p.name)) continue;
        if (allChecked) {
            next.delete(p.name);
        } else {
            next.add(p.name);
        }
    }
    emit('update:modelValue', Array.from(next));
}

// Global: semua permission semua group
const allPermissions = computed(() => props.groups.flatMap(g => g.permissions.map(p => p.name)));
const isAllChecked = computed(() => allPermissions.value.every(n => checked.value.has(n)));

function toggleAll() {
    const next = new Set(checked.value);
    if (isAllChecked.value) {
        // Hapus semua yang tidak disabled
        for (const n of allPermissions.value) {
            if (!disabled.value.has(n)) next.delete(n);
        }
    } else {
        for (const n of allPermissions.value) {
            next.add(n);
        }
    }
    emit('update:modelValue', Array.from(next));
}

function labelDisplay(name: string): string {
    // "view user" → "View User"
    return name.replace(/\b\w/g, c => c.toUpperCase());
}
</script>

<template>
    <div class="space-y-4">
        <!-- Tombol global -->
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-[#101010]">Permission</span>
            <button
                type="button"
                class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                :class="isAllChecked
                    ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
                    : 'border-[#007C95] bg-[#EBFFFA] text-[#007C95] hover:bg-[#007C95]/10'"
                @click="toggleAll"
            >
                {{ isAllChecked ? 'Hapus Semua' : 'Centang Semua' }}
            </button>
        </div>

        <!-- Per-group -->
        <div
            v-for="group in groups"
            :key="group.key"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
        >
            <!-- Group header -->
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/60 px-4 py-2.5">
                <span class="text-xs font-semibold text-gray-700">{{ group.name }}</span>
                <button
                    type="button"
                    class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
                    :class="isGroupAllChecked(group)
                        ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
                        : 'border-[#007C95] bg-[#EBFFFA] text-[#007C95] hover:bg-[#007C95]/10'"
                    @click="toggleGroup(group)"
                >
                    {{ isGroupAllChecked(group) ? 'Hapus Semua' : 'Centang Semua' }}
                </button>
            </div>

            <!-- Permission items -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 p-3 sm:grid-cols-3">
                <label
                    v-for="perm in group.permissions"
                    :key="perm.name"
                    class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-2 text-sm transition-colors"
                    :class="[
                        disabled.has(perm.name) ? 'cursor-default opacity-70' : 'hover:bg-gray-50',
                        checked.has(perm.name) ? 'text-[#007C95] font-medium' : 'text-[#101010]',
                    ]"
                >
                    <input
                        type="checkbox"
                        :checked="checked.has(perm.name)"
                        :disabled="disabled.has(perm.name)"
                        class="size-4 rounded border-gray-300 accent-[#007C95] disabled:opacity-50"
                        @change="toggle(perm.name)"
                    />
                    {{ labelDisplay(perm.name) }}
                    <span
                        v-if="disabled.has(perm.name)"
                        class="ml-auto rounded bg-[#EBFFFA] px-1.5 py-0.5 text-[10px] font-semibold text-[#007C95]"
                    >Role</span>
                </label>
            </div>
        </div>
    </div>
</template>
