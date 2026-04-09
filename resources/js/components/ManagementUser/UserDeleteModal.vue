<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface User {
    id: number;
    name: string;
}

const props = defineProps<{
    open: boolean;
    user?: User | null;
    deleteUrl: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const handleDelete = () => {
    if (!props.user) return;

    router.delete(`${props.deleteUrl}/${props.user.id}`, {
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
        },
    });
};
</script>

<template>
    <Dialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent class="overflow-hidden rounded-2xl p-0 sm:max-w-sm">
            <div class="p-6">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-gray-900"
                        >Hapus User</DialogTitle
                    >
                </DialogHeader>
                <p class="mt-3 text-sm text-gray-500">
                    Apakah Anda yakin ingin menghapus
                    <span class="font-semibold text-gray-700">{{
                        user?.name
                    }}</span
                    >? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5">
                <Button
                    variant="outline"
                    class="w-full rounded"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    @click="handleDelete"
                    class="w-full rounded bg-red-500 text-white hover:bg-red-600"
                >
                    Hapus
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
