<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Bell, EllipsisIcon, LogOut, Shield } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import IconDashboard from '@/components/icons/menu/IconDashboard.vue';
import IconManagementUser from '@/components/icons/menu/iconManagementUser.vue';
import IconManajemen from '@/components/icons/menu/iconManajemen.vue';
import IconPengambilan from '@/components/icons/menu/iconPengambilan.vue';
import IconPenjualan from '@/components/icons/menu/iconPenjualan.vue';
import IconProfil from '@/components/icons/menu/iconProfile.vue';
import IconRiwayat from '@/components/icons/menu/iconRiwayatTransaksi.vue';
import IconTransfer from '@/components/icons/menu/iconTransfer.vue';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermission } from '@/composables/usePermission';
import { dashboard, logout } from '@/routes';
import { type NavItem } from '@/types';
import { PermissionEnum } from '@/enums/PermissionEnum';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const { isCurrentUrl } = useCurrentUrl();
const { can } = usePermission();

const allMainMenuItems: (NavItem & { permission?: PermissionEnum })[] = [
    { title: 'Dashboard', href: dashboard(), icon: IconDashboard },
    { title: 'Pengambilan', href: '/collections', icon: IconPengambilan, permission: PermissionEnum.VIEW_PENGAMBILAN_POO },
    { title: 'Transfer', href: '/transfers', icon: IconTransfer, permission: PermissionEnum.VIEW_TRANSFER },
    { title: 'Penjualan', href: '/exports', icon: IconPenjualan, permission: PermissionEnum.VIEW_PENJUALAN },
];

const allMoreMenuItems: (NavItem & { permission?: PermissionEnum })[] = [
    { title: 'Riwayat Transaksi', href: '/history', icon: IconRiwayat, permission: PermissionEnum.VIEW_RIWAYAT },
    { title: 'Manajemen POO', href: '/management-poo', icon: IconManajemen, permission: PermissionEnum.VIEW_MASTER_POO },
    { title: 'Role', href: '/roles', icon: Shield, permission: PermissionEnum.VIEW_ROLE },
    { title: 'Manajemen User', href: '/management-user', icon: IconManagementUser, permission: PermissionEnum.VIEW_USER },
    { title: 'Profil Akun', href: '/settings/profile', icon: IconProfil },
    { title: 'Notifications', href: '/notifications', icon: Bell, permission: PermissionEnum.VIEW_NOTIFICATION },
];

const mainMenuItems = computed<NavItem[]>(() =>
    allMainMenuItems.filter(item => !item.permission || can(item.permission))
);

const moreMenuItems = computed<NavItem[]>(() =>
    allMoreMenuItems.filter(item => !item.permission || can(item.permission))
);

const isMoreOpen = ref(false);

const isMoreActive = computed(() =>
    moreMenuItems.value.some((item) => isCurrentUrl(item.href))
);

const isLogoutOpen = ref(false);

const handleLogout = () => {
    router.flushAll();
    // kalau perlu POST ke logout route:
    router.post(logout());
};
</script>

<template>
    <nav class="fixed right-0 bottom-0 left-0 z-50 border-t border-gray-200 bg-white">
        <div class="flex items-center justify-around px-1 py-1.5">
            <!-- 4 Menu Utama -->
            <Link v-for="item in mainMenuItems" :key="item.title" :href="item.href"
                class="flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-1.5 text-center transition-colors"
                :class="isCurrentUrl(item.href)
                    ? 'text-[#007C95]'
                    : 'text-gray-400 hover:text-gray-600'
                    ">
                <component :is="item.icon" class="size-5 shrink-0" />
                <span class="truncate text-[10px] leading-tight font-medium">{{
                    item.title
                    }}</span>
            </Link>

            <!-- Tombol Lainnya -->
            <button type="button"
                class="flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-1.5 text-center transition-colors"
                :class="isMoreActive || isMoreOpen
                    ? 'text-[#007C95]'
                    : 'text-gray-400 hover:text-gray-600'
                    " @click="isMoreOpen = true">
                <EllipsisIcon class="size-5 shrink-0" />
                <span class="truncate text-[10px] leading-tight font-medium">Lainnya</span>
            </button>
        </div>
        <!-- Safe area for devices with home indicator -->
        <div class="h-[env(safe-area-inset-bottom)]" />
    </nav>

    <!-- Sheet Menu Lainnya -->
    <Sheet v-model:open="isMoreOpen">
        <SheetContent side="bottom" class="rounded-t-2xl px-4 pb-8">
            <SheetHeader class="pb-2">
                <SheetTitle class="text-base">Menu Lainnya</SheetTitle>
            </SheetHeader>
            <div class="flex flex-col gap-1">
                <Link v-for="item in moreMenuItems" :key="item.title" :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3 py-3 transition-colors" :class="isCurrentUrl(item.href)
                        ? 'bg-[#EBF7FA] text-[#007C95]'
                        : 'text-gray-700 hover:bg-gray-100'
                        " @click="isMoreOpen = false">
                    <component :is="item.icon" class="size-5 shrink-0" />
                    <span class="text-sm font-medium">{{ item.title }}</span>
                </Link>

                <div class="my-1 border-t border-gray-200" />

                <button type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-red-600 transition-colors hover:bg-red-50"
                    @click="isMoreOpen = false; isLogoutOpen = true">
                    <LogOut class="size-5 shrink-0" />
                    <span class="text-sm font-medium">Keluar</span>
                </button>
            </div>
        </SheetContent>
    </Sheet>

    <!-- Logout Confirmation Dialog -->
    <AlertDialog v-model:open="isLogoutOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Keluar</AlertDialogTitle>
                <AlertDialogDescription>
                    Apakah Anda yakin ingin keluar dari aplikasi?
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Batal</AlertDialogCancel>
                <AlertDialogAction class="bg-red-600 hover:bg-red-700" @click="handleLogout">
                    Ya, Keluar
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
