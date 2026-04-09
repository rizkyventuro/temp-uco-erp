<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, ArrowUpRight, ArrowDownLeft, Droplets, Info, AlertTriangle, CheckCircle, XCircle, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface NotifItem {
    id: string;
    type: number;
    type_label: string;
    title: string;
    message: string;
    icon: string | null;
    url: string | null;
    sender_name: string | null;
    read: boolean;
    created_at: string;
    time_label: string;
}

const props = defineProps<{
    notifications: NotifItem[];
    unreadCount: number;
    filter: string;
    hasMore: boolean;
    nextPageUrl: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifikasi', href: '/notifications' }];

const localNotifs = ref<NotifItem[]>([...props.notifications]);
const nextPageUrl = ref<string | null>(props.nextPageUrl);
const isLoading = ref(false);
const unreadCount = ref(props.unreadCount);

const FILTERS = [
    { key: 'all', label: 'Semua' },
    { key: 'unread', label: 'Belum dibaca' },
    { key: '1', label: 'Info' },
    { key: '2', label: 'Sukses' },
    { key: '3', label: 'Peringatan' },
    { key: '4', label: 'Error' },
];

function setFilter(key: string) {
    router.get('/notifications', { filter: key }, { preserveState: false, replace: true });
}

async function markRead(notif: NotifItem) {
    if (notif.read) {
        if (notif.url) window.location.href = notif.url;
        return;
    }
    await axios.patch(`/notifications/${notif.id}/read`);
    notif.read = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
    if (notif.url) window.location.href = notif.url;
}

async function markAllRead() {
    await axios.patch('/notifications/mark-all-read');
    localNotifs.value.forEach(n => (n.read = true));
    unreadCount.value = 0;
}

async function loadMore() {
    if (!nextPageUrl.value || isLoading.value) return;
    isLoading.value = true;
    try {
        const res = await axios.get(nextPageUrl.value, {
            headers: { 'X-Inertia': true, 'X-Inertia-Partial-Data': 'notifications,nextPageUrl,hasMore', 'X-Inertia-Partial-Component': 'admin/notification/Notifikasi' },
        });
        const data = res.data?.props ?? res.data;
        if (data?.notifications) localNotifs.value.push(...data.notifications);
        nextPageUrl.value = data?.nextPageUrl ?? null;
    } finally {
        isLoading.value = false;
    }
}

// Group by date label
const grouped = computed(() => {
    const map: Record<string, NotifItem[]> = {};
    localNotifs.value.forEach(n => {
        const d = new Date(n.created_at);
        const now = new Date();
        const diffDays = Math.floor((now.getTime() - d.getTime()) / 86400000);
        const label = diffDays === 0 ? 'Hari ini' : diffDays === 1 ? 'Kemarin' : 'Lebih lama';
        (map[label] ??= []).push(n);
    });
    return Object.entries(map).map(([label, items]) => ({ label, items }));
});

function typeIcon(type: number) {
    return { 1: Info, 2: CheckCircle, 3: AlertTriangle, 4: XCircle }[type] ?? Bell;
}
function typeBg(type: number) {
    return { 1: 'bg-blue-50 text-blue-600', 2: 'bg-green-50 text-green-600', 3: 'bg-amber-50 text-amber-600', 4: 'bg-red-50 text-red-600' }[type] ?? 'bg-gray-100 text-gray-500';
}
function typeAccent(type: number) {
    return { 1: 'border-blue-400', 2: 'border-green-400', 3: 'border-amber-400', 4: 'border-red-400' }[type] ?? '';
}
</script>

<template>

    <Head title="Notifikasi" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center">
            <div class="flex h-full w-full max-w-4xl flex-col">

                <!-- Header -->
                <div class="shrink-0 bg-[#007C95] px-5 pt-5 pb-0">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Bell class="h-5 w-5 text-white/80" />
                            <span class="text-base font-semibold text-white">Notifikasi</span>
                            <span v-if="unreadCount > 0"
                                class="rounded-full bg-yellow-400 px-2 py-0.5 text-xs font-bold text-yellow-900">
                                {{ unreadCount }}
                            </span>
                        </div>
                        <button v-if="unreadCount > 0" @click="markAllRead"
                            class="rounded-full bg-white/20 px-3 py-1.5 text-xs font-medium text-white hover:bg-white/30 transition">
                            Tandai semua dibaca
                        </button>
                    </div>

                    <!-- Filter tabs -->
                    <div class="flex gap-2 overflow-x-auto pb-0 scrollbar-none">
                        <button v-for="f in FILTERS" :key="f.key" @click="setFilter(f.key)" :class="['whitespace-nowrap rounded-t-xl px-4 py-2 text-xs font-medium transition',
                            filter === f.key
                                ? 'bg-white text-[#007C95]'
                                : 'text-white/70 hover:text-white']">
                            {{ f.label }}
                        </button>
                    </div>
                </div>

                <!-- List -->
                <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50 px-4 pt-4 pb-8">

                    <div v-if="localNotifs.length === 0" class="flex flex-col items-center justify-center py-24">
                        <Bell class="mb-3 h-12 w-12 text-gray-300" />
                        <p class="text-sm font-medium text-gray-500">Tidak ada notifikasi</p>
                        <p class="mt-1 text-xs text-gray-400">Notifikasi akan muncul di sini</p>
                    </div>

                    <div v-for="group in grouped" :key="group.label" class="mb-4">
                        <p class="mb-2 px-1 text-[11px] font-semibold tracking-wider text-gray-400 uppercase">
                            {{ group.label }}
                        </p>
                        <div class="flex flex-col gap-2">
                            <div v-for="n in group.items" :key="n.id" @click="markRead(n)" :class="['flex gap-3 rounded-2xl bg-white p-4 shadow-sm transition cursor-pointer hover:bg-gray-50',
                                !n.read ? 'border-l-4 ' + typeAccent(n.type) : 'border border-gray-100']">
                                <div
                                    :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-full', typeBg(n.type)]">
                                    <component :is="typeIcon(n.type)" class="h-4.5 w-4.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-gray-800 leading-snug">{{ n.title }}</p>
                                        <div v-if="!n.read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#007C95]" />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 leading-relaxed">{{ n.message }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <p class="text-[10px] text-gray-400">{{ n.time_label }}</p>
                                        <span v-if="n.sender_name" class="text-[10px] text-gray-400">· dari {{
                                            n.sender_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Load more -->
                    <div class="flex justify-center py-4">
                        <div v-if="isLoading" class="flex items-center gap-2 text-xs text-gray-400">
                            <Loader2 class="h-4 w-4 animate-spin" /> Memuat...
                        </div>
                        <button v-else-if="nextPageUrl" @click="loadMore"
                            class="rounded-full border border-gray-200 bg-white px-5 py-2 text-xs text-gray-500 hover:bg-gray-50 transition">
                            Muat lebih banyak
                        </button>
                        <p v-else-if="localNotifs.length > 0" class="text-xs text-gray-400">
                            Semua notifikasi sudah ditampilkan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>