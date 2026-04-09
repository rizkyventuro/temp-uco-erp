<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Settings, Shield } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import IconDashboard from '@/components/icons/menu/IconDashboard.vue';
import IconManagementUser from '@/components/icons/menu/iconManagementUser.vue';
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
    { title: 'Role', href: '/roles', icon: Shield, permission: PermissionEnum.VIEW_ROLE },
    { title: 'Manajemen User', href: '/management-user', icon: IconManagementUser, permission: PermissionEnum.VIEW_USER },
    { title: 'Pengaturan', href: '/settings', icon:  Settings},
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
                    <SidebarMenuButton size="lg" class="hover:bg-transparent" as-child>
                        <Link :href="dashboard()">
                            <AppLogo class="h-10"/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

    </Sidebar>
    <slot />




</template>
