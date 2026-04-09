<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { watch, ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type BusinessType = 'Restoran' | 'UMKM' | 'Rumah Tangga';

interface POO {
    id: string;
    name: string;
    address: string;
    contact: string;
    type: BusinessType;
}

const props = defineProps<{
    open: boolean;
    editingPoo?: POO | null;
    postUrl: string;
    putUrl?: string;
    suggestions?: POO[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const BUSINESS_TYPES: BusinessType[] = ['Restoran', 'UMKM', 'Rumah Tangga'];

const form = useForm({
    name: '',
    address: '',
    contact: '',
    type: 'Restoran' as BusinessType,
});

// =================== PHONE VALIDATION ===================
const contactError = ref('');

const onContactInput = (e: Event) => {
    const input = e.target as HTMLInputElement;
    // Only allow digits, +, -, spaces, parentheses
    const value = input.value.replace(/[^\d+\-() ]/g, '');
    form.contact = value;
    validateContact(value);
};

const validateContact = (value: string) => {
    if (!value) {
        contactError.value = '';
        return;
    }
    // Strip formatting to check digits only
    const digitsOnly = value.replace(/\D/g, '');
    if (digitsOnly.length < 8) {
        contactError.value = 'Nomor telepon minimal 8 digit.';
    } else if (digitsOnly.length > 15) {
        contactError.value = 'Nomor telepon maksimal 15 digit.';
    } else if (!/^(\+62|62|0)/.test(value.replace(/[\s\-()]/g, ''))) {
        contactError.value = 'Nomor harus diawali 0, 62, atau +62.';
    } else {
        contactError.value = '';
    }
};

// =================== AUTOCOMPLETE ===================
const showSuggestions = ref(false);
const highlightedIndex = ref(-1);

const filteredSuggestions = computed(() => {
    if (!form.name || !props.suggestions) return [];
    const q = form.name.toLowerCase();
    return props.suggestions
        .filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                p.address.toLowerCase().includes(q),
        )
        .slice(0, 5);
});

const selectSuggestion = (poo: POO) => {
    form.name = poo.name;
    form.address = poo.address;
    form.contact = poo.contact ?? '';
    form.type = poo.type;
    showSuggestions.value = false;
    highlightedIndex.value = -1;
};

const onNameInput = () => {
    showSuggestions.value = true;
    highlightedIndex.value = -1;
};

const onKeydown = (e: KeyboardEvent) => {
    if (!showSuggestions.value || filteredSuggestions.value.length === 0)
        return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = Math.min(
            highlightedIndex.value + 1,
            filteredSuggestions.value.length - 1,
        );
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    } else if (e.key === 'Enter' && highlightedIndex.value >= 0) {
        e.preventDefault();
        selectSuggestion(filteredSuggestions.value[highlightedIndex.value]);
    } else if (e.key === 'Escape') {
        showSuggestions.value = false;
    }
};

// =================== FORM ===================
watch(
    () => props.editingPoo,
    (poo) => {
        if (poo) {
            form.name = poo.name;
            form.address = poo.address;
            form.contact = poo.contact ?? '';
            form.type = poo.type;
        } else {
            form.reset();
        }
        form.clearErrors();
        contactError.value = '';
        showSuggestions.value = false;
    },
    { immediate: true },
);

const isEditing = () => !!props.editingPoo;

const handleSubmit = () => {
    validateContact(form.contact);
    if (contactError.value) return;

    if (isEditing() && props.editingPoo) {
        form.put(`${props.putUrl ?? props.postUrl}/${props.editingPoo.id}`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                form.reset();
            },
        });
    } else {
        form.post(props.postUrl, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                form.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent class="overflow-hidden rounded-2xl p-0 sm:max-w-md">
            <div class="p-6 pb-0">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-gray-900">
                        {{ isEditing() ? 'Edit POO' : 'Tambah POO Baru' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <div class="grid gap-5 p-5">
                <!-- Nama + Autocomplete -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Nama <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            v-model="form.name"
                            placeholder="Rumah Padang"
                            :class="{ 'border-red-400': form.errors.name }"
                            @input="onNameInput"
                            @keydown="onKeydown"
                            @blur="
                                setTimeout(() => (showSuggestions = false), 150)
                            "
                            autocomplete="off"
                        />

                        <!-- Dropdown suggestions -->
                        <div
                            v-if="
                                showSuggestions &&
                                filteredSuggestions.length > 0
                            "
                            class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
                        >
                            <button
                                v-for="(poo, index) in filteredSuggestions"
                                :key="poo.id"
                                type="button"
                                @mousedown.prevent="selectSuggestion(poo)"
                                class="flex w-full flex-col gap-0.5 px-4 py-3 text-left text-sm transition hover:bg-gray-50"
                                :class="{
                                    'bg-gray-50': highlightedIndex === index,
                                }"
                            >
                                <span class="font-medium text-gray-900">{{
                                    poo.name
                                }}</span>
                                <span
                                    class="flex items-center gap-1 text-xs text-gray-400"
                                >
                                    <MapPin class="h-3 w-3" />
                                    {{ poo.address }}
                                </span>
                            </button>
                        </div>
                    </div>
                    <span
                        v-if="form.errors.name"
                        class="text-xs text-red-500"
                        >{{ form.errors.name }}</span
                    >
                </div>

                <!-- Alamat -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">
                        Alamat <span class="text-red-500">*</span>
                    </Label>
                    <textarea
                        v-model="form.address"
                        placeholder="Alamat lengkap"
                        rows="3"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        :class="{ 'border-red-400': form.errors.address }"
                    />
                    <span
                        v-if="form.errors.address"
                        class="text-xs text-red-500"
                        >{{ form.errors.address }}</span
                    >
                </div>

                <!-- Kontak -->
                <div class="grid gap-1.5">
                    <Label class="text-sm font-medium">Kontak</Label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                />
                            </svg>
                        </span>
                        <Input
                            v-model="form.contact"
                            placeholder="08123456789"
                            inputmode="tel"
                            class="pl-9"
                            :class="{ 'border-red-400': contactError }"
                            @input="onContactInput"
                            maxlength="20"
                        />
                    </div>
                    <span
                        v-if="contactError"
                        class="text-xs text-red-500"
                        >{{ contactError }}</span
                    >
                    <span
                        v-else
                        class="text-xs text-gray-400"
                        >Format: 08xx, 628xx, atau +628xx</span
                    >
                </div>

                <!-- Jenis Usaha -->
                <div class="grid gap-2">
                    <Label class="text-sm font-medium">Jenis Usaha</Label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="type in BUSINESS_TYPES"
                            :key="type"
                            type="button"
                            @click="form.type = type"
                            class="w-full rounded-lg border py-2 text-sm font-medium transition"
                            :class="
                                form.type === type
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-primary'
                            "
                        >
                            {{ type }}
                        </button>
                    </div>
                </div>
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
                    @click="handleSubmit"
                    :disabled="form.processing"
                    class="w-full rounded bg-primary text-white hover:bg-primary-hover"
                >
                    {{ isEditing() ? 'Simpan Perubahan' : 'Simpan' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
