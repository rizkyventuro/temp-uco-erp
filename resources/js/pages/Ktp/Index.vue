<template>
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Verifikasi KTP</h1>

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Upload Foto KTP
            </label>

            <!-- Hidden file input -->
            <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="onFileChange" />

            <!-- Upload trigger button -->
            <button @click="fileInputRef.click()" class="w-full border-2 border-dashed border-gray-300 rounded-lg py-8 flex flex-col items-center gap-2
                       hover:border-blue-400 hover:bg-blue-50 transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 16.5V19a1 1 0 001 1h16a1 1 0 001-1v-2.5M12 3v13m0-13L8.5 6.5M12 3l3.5 3.5" />
                </svg>
                <span class="text-sm text-gray-500">Klik untuk pilih foto KTP</span>
            </button>

            <!-- Preview hasil crop -->
            <div v-if="croppedPreview" class="mt-4 flex items-start gap-4">
                <img :src="croppedPreview" class="h-28 rounded border object-cover" />
                <div class="flex flex-col gap-2">
                    <p class="text-xs text-green-600 font-medium">✅ Foto sudah diposisikan</p>
                    <button @click="fileInputRef.click()" class="text-xs text-blue-600 underline hover:text-blue-800">
                        Ganti foto
                    </button>
                </div>
            </div>

            <button @click="submitFoto" :disabled="!croppedFile || loading" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded
                       hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ loading ? 'Memproses...' : 'Ekstrak Data KTP' }}
            </button>
        </div>

        <!-- Result -->
        <div v-if="result" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Hasil Ekstraksi</h2>
            <table class="w-full text-sm">
                <tbody>
                    <tr v-for="(value, key) in result" :key="key" class="border-b">
                        <td class="py-2 font-medium text-gray-600 capitalize w-40">
                            {{ formatKey(key) }}
                        </td>
                        <td class="py-2 text-gray-900">
                            {{ value ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded p-4 text-red-700">
            {{ error }}
        </div>

        <!-- KTP Positioner Modal -->
        <KtpCropper ref="KtpCropperRef" @cropped="onCropped" @cancel="onCancelCrop" />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import KtpCropper from '@/components/KtpCropper.vue';

const fileInputRef = ref(null)
const KtpCropperRef = ref(null)

const croppedFile = ref(null)
const croppedPreview = ref(null)
const loading = ref(false)
const result = ref(null)
const error = ref(null)

// User pilih file → langsung buka modal positioner
function onFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    // Reset state sebelumnya
    result.value = null
    error.value = null
    croppedFile.value = null
    croppedPreview.value = null

    // Reset input supaya bisa pilih file sama lagi
    e.target.value = ''

    // Buka modal positioner
    KtpCropperRef.value?.openModal(file)
}

// Callback setelah user konfirmasi posisi KTP
function onCropped(file) {
    croppedFile.value = file
    croppedPreview.value = URL.createObjectURL(file)
}

// Callback jika user cancel
function onCancelCrop() {
    // Preview lama tetap tampil jika sudah ada sebelumnya
}

async function submitFoto() {
    if (!croppedFile.value) return

    loading.value = true
    error.value = null
    result.value = null

    const formData = new FormData()
    formData.append('foto', croppedFile.value)

    try {
        const response = await axios.post('/ktp/extract', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            timeout: 120000, // 120 detik (dalam milidetik)
        })
        result.value = response.data.data
    } catch (err) {
        error.value = err.response?.data?.error ?? err.response?.data?.message ?? 'Terjadi kesalahan'
    } finally {
        loading.value = false
    }
}

function formatKey(key) {
    return key.replace(/_/g, ' ')
}
</script>