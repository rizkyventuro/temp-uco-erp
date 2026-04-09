<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Bell, LogOut, Shield } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import IconDashboard from '@/components/icons/menu/IconDashboard.vue';
import IconManagementUser from '@/components/icons/menu/iconManagementUser.vue';
import IconManajemen from '@/components/icons/menu/iconManajemen.vue';
import IconPengambilan from '@/components/icons/menu/iconPengambilan.vue';
import IconPenjualan from '@/components/icons/menu/iconPenjualan.vue';
import IconProfil from '@/components/icons/menu/iconProfile.vue';
import IconRiwayat from '@/components/icons/menu/iconRiwayatTransaksi.vue';
import IconTransfer from '@/components/icons/menu/iconTransfer.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermission } from '@/composables/usePermission';
import { dashboard, logout } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
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

const { can } = usePermission();


const isMoreOpen = ref(false);

const isLogoutOpen = ref(false);

const handleLogout = () => {

    router.flushAll();
    // // kalau perlu POST ke logout route:
    router.post(logout());
};

const allNavItems: (NavItem & { permission?: PermissionEnum })[] = [
    { title: 'Dashboard', href: dashboard(), icon: IconDashboard },
    { title: 'Pengambilan dari POO', href: '/collections', icon: IconPengambilan, permission: PermissionEnum.VIEW_PENGAMBILAN_POO },
    { title: 'Transfer UCO', href: '/transfers', icon: IconTransfer, permission: PermissionEnum.VIEW_TRANSFER },
    { title: 'Penjualan / Export', href: '/exports', icon: IconPenjualan, permission: PermissionEnum.VIEW_PENJUALAN },
    { title: 'Riwayat Transaksi', href: '/history', icon: IconRiwayat, permission: PermissionEnum.VIEW_RIWAYAT },
    { title: 'Manajemen POO', href: '/management-poo', icon: IconManajemen, permission: PermissionEnum.VIEW_MASTER_POO },
    { title: 'Role', href: '/roles', icon: Shield, permission: PermissionEnum.VIEW_ROLE },
    { title: 'Manajemen User', href: '/management-user', icon: IconManagementUser, permission: PermissionEnum.VIEW_USER },
    { title: 'Profil Akun', href: '/settings/profile', icon: IconProfil },
    { title: 'Notifications', href: '/notifications', icon: Bell, permission: PermissionEnum.VIEW_NOTIFICATION },
];

const mainNavItems = computed<NavItem[]>(() =>
    allNavItems.filter(item => !item.permission || can(item.permission))
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <button type="button"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-red-600 transition-colors hover:bg-red-50"
                            @click="isMoreOpen = false; isLogoutOpen = true">
                            <LogOut class="size-5 shrink-0" />
                            <span class="text-sm font-medium">Keluar</span>
                        </button>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />



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
