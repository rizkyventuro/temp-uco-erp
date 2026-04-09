<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, X, User as UserIcon, MapPin, CreditCard, Phone, Calendar, Briefcase } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';

interface UserProfile {
    phone: string | null;
    birth_date: string | null;
    gender: string | null;
    occupation: string | null;
    address: string | null;
    province: string | null;
    city: string | null;
    district: string | null;
    village: string | null;
    postal_code: string | null;
    id_card_number: string | null;
    id_card_photo_url: string | null;
}

interface User {
    id: number;
    name: string;
    email: string;
    profile: UserProfile | null;
}

const props = defineProps<{
    open: boolean;
    user: User | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
    error: [];
}>();

const action = ref<'approve' | 'reject' | null>(null);
const note = ref('');
const processing = ref(false);
const noteError = ref('');

watch(() => props.open, (val) => {
    if (val) {
        action.value = null;
        note.value = '';
        noteError.value = '';
    }
});

const close = () => emit('update:open', false);

const submit = () => {
    if (!props.user || !action.value) return;

    if (action.value === 'reject' && !note.value.trim()) {
        noteError.value = 'Catatan wajib diisi saat menolak profil.';
        return;
    }
    noteError.value = '';
    processing.value = true;

    const url = action.value === 'approve'
        ? `/management-user/${props.user.id}/verify`
        : `/management-user/${props.user.id}/reject`;

    router.patch(
        url,
        action.value === 'reject' ? { note: note.value } : {},
        {
            preserveScroll: true,
            onSuccess: () => { emit('success'); close(); },
            onError: () => emit('error'),
            onFinish: () => { processing.value = false; },
        }
    );
};

const formatDate = (date: string | null) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatGender = (gender: string | null) => {
    if (gender === 'male') return 'Laki-laki';
    if (gender === 'female') return 'Perempuan';
    return '—';
};

const fullAddress = (profile: UserProfile | null) => {
    if (!profile) return '—';
    const parts = [
        profile.address,
        profile.village ? `Kel. ${profile.village}` : null,
        profile.district ? `Kec. ${profile.district}` : null,
        profile.city,
        profile.province,
        profile.postal_code,
    ].filter(Boolean);
    return parts.join(', ') || '—';
};
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

                <!-- Modal — scrollable -->
                <Transition enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2">
                    <div v-if="open"
                        class="relative z-10 w-full max-w-lg max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl">

                        <!-- Header (sticky) -->
                        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Verifikasi Profil User</h2>
                                <p class="mt-0.5 text-sm text-gray-500">
                                    Tinjau data diri
                                    <span class="font-medium text-gray-700">{{ user?.name }}</span>
                                </p>
                            </div>
                            <button @click="close"
                                class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Body (scrollable) -->
                        <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                            <!-- Data Diri -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UserIcon class="h-4 w-4 text-[#007C95]" />
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#007C95]">Data Diri
                                    </h3>
                                </div>
                                <div class="rounded-xl border border-gray-100 bg-gray-50 divide-y divide-gray-100">
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400">Nama Lengkap</span>
                                        <span class="text-xs font-medium text-gray-700">{{ user?.name || '—' }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400">Email</span>
                                        <span class="text-xs font-medium text-gray-700 break-all">{{ user?.email || '—'
                                        }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400 flex items-center gap-1">
                                            <Phone class="h-3 w-3" /> Telepon
                                        </span>
                                        <span class="text-xs font-medium text-gray-700">{{ user?.profile?.phone || '—'
                                        }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400 flex items-center gap-1">
                                            <Calendar class="h-3 w-3" /> Tgl. Lahir
                                        </span>
                                        <span class="text-xs font-medium text-gray-700">{{
                                            formatDate(user?.profile?.birth_date ?? null) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400">Jenis Kelamin</span>
                                        <span class="text-xs font-medium text-gray-700">{{
                                            formatGender(user?.profile?.gender ?? null) }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400 flex items-center gap-1">
                                            <Briefcase class="h-3 w-3" /> Pekerjaan
                                        </span>
                                        <span class="text-xs font-medium text-gray-700">{{ user?.profile?.occupation ||
                                            '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <MapPin class="h-4 w-4 text-[#007C95]" />
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#007C95]">Alamat</h3>
                                </div>
                                <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-medium text-gray-700 leading-relaxed">
                                        {{ fullAddress(user?.profile ?? null) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Identitas KTP -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <CreditCard class="h-4 w-4 text-[#007C95]" />
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#007C95]">Identitas
                                        KTP</h3>
                                </div>
                                <div class="rounded-xl border border-gray-100 bg-gray-50 divide-y divide-gray-100">
                                    <div class="grid grid-cols-2 px-4 py-2.5">
                                        <span class="text-xs text-gray-400">NIK</span>
                                        <span class="text-xs font-medium text-gray-700 font-mono tracking-wider">
                                            {{ user?.profile?.id_card_number || '—' }}
                                        </span>
                                    </div>
                                    <div class="px-4 py-3">
                                        <span class="text-xs text-gray-400 block mb-2">Foto KTP</span>
                                        <div v-if="user?.profile?.id_card_photo_url">
                                            <a :href="user.profile.id_card_photo_url" target="_blank"
                                                class="block overflow-hidden rounded-lg border border-gray-200 hover:opacity-90 transition">
                                                <img :src="user.profile.id_card_photo_url" alt="Foto KTP"
                                                    class="w-full max-h-40 object-cover" />
                                            </a>
                                            <p class="mt-1 text-[10px] text-gray-400">Klik gambar untuk membuka ukuran
                                                penuh</p>
                                        </div>
                                        <div v-else
                                            class="flex items-center justify-center h-20 rounded-lg border border-dashed border-gray-200 bg-white">
                                            <span class="text-xs text-gray-400">Foto tidak tersedia</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <hr class="border-gray-100" />

                            <!-- Pilihan aksi -->
                            <div>
                                <p class="text-xs font-semibold text-gray-600 mb-3">Keputusan Verifikasi</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <button @click="action = 'approve'" :class="[
                                        'flex items-center gap-3 rounded-xl border-2 px-4 py-3 transition-all text-left',
                                        action === 'approve'
                                            ? 'border-green-500 bg-green-50'
                                            : 'border-gray-200 bg-white hover:border-green-300 hover:bg-green-50/50'
                                    ]">
                                        <div :class="[
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors',
                                            action === 'approve' ? 'bg-green-100' : 'bg-gray-100'
                                        ]">
                                            <CheckCircle2
                                                :class="['h-4 w-4', action === 'approve' ? 'text-green-600' : 'text-gray-400']" />
                                        </div>
                                        <div>
                                            <p
                                                :class="['text-sm font-medium', action === 'approve' ? 'text-green-700' : 'text-gray-600']">
                                                Setujui
                                            </p>
                                            <p class="text-[10px] text-gray-400 leading-tight">Profil diterima</p>
                                        </div>
                                    </button>

                                    <button @click="action = 'reject'" :class="[
                                        'flex items-center gap-3 rounded-xl border-2 px-4 py-3 transition-all text-left',
                                        action === 'reject'
                                            ? 'border-red-500 bg-red-50'
                                            : 'border-gray-200 bg-white hover:border-red-300 hover:bg-red-50/50'
                                    ]">
                                        <div :class="[
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors',
                                            action === 'reject' ? 'bg-red-100' : 'bg-gray-100'
                                        ]">
                                            <XCircle
                                                :class="['h-4 w-4', action === 'reject' ? 'text-red-500' : 'text-gray-400']" />
                                        </div>
                                        <div>
                                            <p
                                                :class="['text-sm font-medium', action === 'reject' ? 'text-red-600' : 'text-gray-600']">
                                                Tolak
                                            </p>
                                            <p class="text-[10px] text-gray-400 leading-tight">Kembalikan untuk
                                                diperbaiki</p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Form catatan -->
                            <div v-if="action">
                                <Label for="verify-note" class="mb-1.5 block text-sm">
                                    Catatan
                                    <span v-if="action === 'reject'" class="text-red-500">*</span>
                                    <span v-else class="ml-1 text-xs font-normal text-gray-400">(opsional)</span>
                                </Label>
                                <Textarea id="verify-note" v-model="note" :placeholder="action === 'reject'
                                    ? 'Jelaskan alasan penolakan agar user dapat memperbaiki datanya...'
                                    : 'Tambahkan catatan untuk user (opsional)...'" rows="3"
                                    class="focus-visible:ring-[#007C95]" />
                                <p v-if="noteError" class="mt-1 text-xs text-red-500">{{ noteError }}</p>
                            </div>
                        </div>

                        <!-- Footer (sticky) -->
                        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                            <Button variant="outline" @click="close" :disabled="processing">
                                Batal
                            </Button>
                            <Button v-if="action" @click="submit" :disabled="processing" :class="[
                                'min-w-[120px]',
                                action === 'approve'
                                    ? 'bg-green-600 hover:bg-green-700 text-white'
                                    : 'bg-red-500 hover:bg-red-600 text-white'
                            ]">
                                {{ processing ?
                                    'Menyimpan...' : action === 'approve' ? 'Setujui Profil' : 'Tolak Profil'
                                }}
                            </Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>