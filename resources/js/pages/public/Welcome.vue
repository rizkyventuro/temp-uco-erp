<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div
        class="flex min-h-screen items-center justify-center bg-white dark:bg-gray-900"
    >
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                Welcome
            </h1>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Selamat datang di aplikasi kami.
            </p>
            <a
                href="/login"
                class="mt-6 inline-block rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
            >
                Login
            </a>
        </div>
    </div>
</template>
