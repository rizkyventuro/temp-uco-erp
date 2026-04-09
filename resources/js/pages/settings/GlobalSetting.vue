<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

type Props = {
    verificationMode: 'manual' | 'auto';
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Global Settings',
        href: '/settings/global',
    },
];

// Local state agar toggle responsif secara visual (optimistic UI)
const isAuto = ref(props.verificationMode === 'auto');
const isSaving = ref(false);

// Sync jika prop berubah dari server (misal setelah Inertia refresh)
watch(
    () => props.verificationMode,
    (val) => {
        isAuto.value = val === 'auto';
    }
);

function onVerificationToggle(checked: boolean) {

    isAuto.value = checked; // update lokal dulu (optimistic)
    isSaving.value = true;

    router.put(
        '/settings/global',
        {
            verification_mode: checked ? 'auto' : 'manual',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
            },
            onError: () => {
                // Rollback jika request gagal
                isAuto.value = !checked;
                isSaving.value = false;
            },
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Global Settings" />

        <h1 class="sr-only">Global Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading variant="small" title="Global Settings" description="Kelola pengaturan global aplikasi" />

                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">Verifikasi Otomatis</p>
                            <p class="text-sm text-muted-foreground">
                                Jika aktif, verifikasi user dilakukan secara otomatis
                                (<span class="font-medium">auto</span>).
                                Jika tidak aktif, verifikasi dilakukan secara manual
                                (<span class="font-medium">manual</span>).
                            </p>
                        </div>
                        <Switch v-model="isAuto" :disabled="isSaving" @update:modelValue="onVerificationToggle" />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>