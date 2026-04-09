import { usePage } from '@inertiajs/vue3';
import { type AppPageProps } from '@/types';
import { PermissionEnum } from '@/enums/PermissionEnum'; // tambah import

export function usePermission() {
    const page = usePage<AppPageProps>();

    // Sekarang terima PermissionEnum atau string biasa
    const can = (permission: PermissionEnum | string): boolean =>
        page.props.auth.permissions.includes(permission);

    return { can };
}