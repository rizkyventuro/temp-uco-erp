<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDown, LogOut, Shield, Settings,
} from 'lucide-vue-next';

// Custom icon components
import IconDashboard from '@/components/icons/menu/IconDashboard.vue';
import IconMasterData from '@/components/icons/menu/iconMasterData.vue';
import IconBarangMasuk from '@/components/icons/menu/iconBarangMasuk.vue';
import IconBarangKeluar from '@/components/icons/menu/iconBarangKeluar.vue';
import IconTransferStok from '@/components/icons/menu/iconTransferStok.vue';
import IconOpname from '@/components/icons/menu/iconOpname.vue';
import IconHutang from '@/components/icons/menu/iconHutang.vue';
import IconPiutang from '@/components/icons/menu/iconPiutang.vue';
import IconKas from '@/components/icons/menu/iconKas.vue';
import IconLaporan from '@/components/icons/menu/iconLaporan.vue';
import IconManagementUser from '@/components/icons/menu/iconManagementUser.vue';

import { computed, ref } from 'vue';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Collapsible, CollapsibleContent, CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
    SidebarMenu, SidebarMenuButton, SidebarMenuItem,
    SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermission } from '@/composables/usePermission';
import { dashboard, logout } from '@/routes';
import AppLogo from './AppLogo.vue';
import AppLogoIcon from './AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar';

const { can } = usePermission();
const { isCurrentUrl } = useCurrentUrl();
const { state } = useSidebar();
const isLogoutOpen = ref(false);

const handleLogout = () => {
    router.flushAll();
    router.post(logout());
};

const masterDataItems = [
    { title: 'Supplier', href: '/master-data/supplier' },
    { title: 'Buyer (Customer)', href: '/master-data/buyer' },
    { title: 'Gudang / Tank', href: '/master-data/warehouse' },
];

const isMasterDataActive = computed(() =>
    masterDataItems.some(item => isCurrentUrl(item.href)),
);

const isMasterDataOpen = ref(isMasterDataActive.value);

const mainMenuItems = [
    { title: 'Barang Masuk', href: '/goods-receipt', icon: IconBarangMasuk },
    { title: 'Barang Keluar', href: '/goods-issue', icon: IconBarangKeluar },
    { title: 'Transfer Stok', href: '/stock-transfer', icon: IconTransferStok },
    { title: 'Stok / Opname', href: '/stock-opname', icon: IconOpname },
    { title: 'Hutang (AP)', href: '/accounts-payable', icon: IconHutang },
    { title: 'Piutang (AR)', href: '/accounts-receivable', icon: IconPiutang },
    { title: 'Kas / Bank', href: '/cash-bank', icon: IconKas },
    { title: 'Laporan', href: '/reports', icon: IconLaporan },
    { title: 'Management User', href: '/management-user', icon: IconManagementUser },
    { title: 'Management Role', href: '/management-role', icon: Shield },
    { title: 'Pengaturan', href: '/pengaturan', icon: Settings },
];

function activeClass(active: boolean) {
    return active
        ? 'bg-[#EBFFFA] text-[#007C95] hover:bg-[#EBFFFA] hover:text-[#007C95] font-semibold'
        : 'text-[#101010] hover:bg-gray-50 hover:text-[#101010]';
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">

        <!-- Logo -->
        <SidebarHeader class="border-b border-gray-100 py-3 ">
            <SidebarMenu>
                <SidebarMenuItem class="flex justify-center">
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent active:bg-transparent">
                        <Link :href="dashboard()" class="flex items-center">
                            <AppLogoIcon v-if="state === 'collapsed'" class="size-7 w-auto" />
                            <AppLogo v-else class="h-10 w-auto" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Navigation -->
        <SidebarContent class="py-2">
            <SidebarMenu class="gap-0.5 px-2">

                <!-- Dashboard -->
                <SidebarMenuItem>
                    <SidebarMenuButton as-child tooltip="Dashboard" :class="activeClass(isCurrentUrl(dashboard()))">
                        <Link :href="dashboard()">
                            <IconDashboard />
                            <span>Dashboard</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Master Data accordion -->
                <SidebarMenuItem>
                    <Collapsible v-model:open="isMasterDataOpen" class="group/collapsible">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip="Master Data" :class="activeClass(isMasterDataActive)">
                                <IconMasterData class="size-4 shrink-0" />
                                <span>Master Data</span>
                                <ChevronDown
                                    class="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-180" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="sub in masterDataItems" :key="sub.href">
                                    <SidebarMenuSubButton as-child
                                        :class="isCurrentUrl(sub.href) ? 'text-[#007C95] font-semibold bg-[#EBFFFA]' : 'text-[#101010]'">
                                        <Link :href="sub.href">{{ sub.title }}</Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>

                <!-- Menu utama -->
                <SidebarMenuItem v-for="item in mainMenuItems" :key="item.href">
                    <SidebarMenuButton as-child :tooltip="item.title" :class="activeClass(isCurrentUrl(item.href))">
                        <Link :href="item.href">
                            <component :is="item.icon" class="size-4 shrink-0" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

            </SidebarMenu>
        </SidebarContent>

    </Sidebar>

    <AlertDialog v-model:open="isLogoutOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Keluar</AlertDialogTitle>
                <AlertDialogDescription>Apakah Anda yakin ingin keluar dari aplikasi?</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Batal</AlertDialogCancel>
                <AlertDialogAction class="bg-red-600 hover:bg-red-700" @click="handleLogout">Ya, Keluar
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
