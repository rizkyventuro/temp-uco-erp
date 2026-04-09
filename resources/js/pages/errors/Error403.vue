<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const goBack = () => window.history.back();
</script>

<template>
    <Head title="403 - Akses Ditolak" />

    <div
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-primary-hover px-4"
    >
        <!-- Animated Background -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Flight path lines -->
            <div class="absolute inset-0">
                <svg
                    class="h-full w-full opacity-10"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <defs>
                        <pattern
                            id="grid"
                            width="40"
                            height="40"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M 40 0 L 0 0 0 40"
                                fill="none"
                                stroke="white"
                                stroke-width="0.5"
                            />
                        </pattern>
                    </defs>
                    <rect
                        width="100%"
                        height="100%"
                        fill="url(#grid)"
                    />
                </svg>
            </div>
            <!-- Floating particles -->
            <div
                v-for="i in 15"
                :key="i"
                class="absolute animate-pulse rounded-full bg-white/5 blur-sm"
                :style="{
                    left: `${Math.random() * 100}%`,
                    top: `${Math.random() * 100}%`,
                    width: `${20 + Math.random() * 40}px`,
                    height: `${20 + Math.random() * 40}px`,
                    animationDelay: `${Math.random() * 3}s`,
                    animationDuration: `${3 + Math.random() * 2}s`,
                }"
            />
        </div>

        <div class="relative z-10 w-full max-w-3xl text-center">
            <!-- Logo/Brand Section -->
            <div class="mb-8 animate-[fadeIn_0.6s_ease-out]">
                <div
                    class="inline-flex items-center gap-3 rounded-full border border-white/20 bg-white/10 px-6 py-3 backdrop-blur-md"
                >
                    <img
                        src="/assets/images/logo.png"
                        class="h-5"
                        alt="Logo"
                    />
                    <span class="text-xl font-bold tracking-wide text-white"
                        >UCO Tracer</span
                    >
                </div>
            </div>

            <!-- 404 Number -->
            <div class="relative mb-6">
                <div
                    class="animate-[fadeIn_0.8s_ease-out] text-[160px] leading-none font-black text-white/10 select-none md:text-[200px]"
                >
                    403
                </div>
            </div>

            <!-- Text Content -->
            <div
                class="mb-10 animate-[fadeInUp_0.6s_ease-out_0.3s_both] space-y-4"
            >
                <h1 class="text-4xl font-bold text-white md:text-5xl">
                    Akses Ditolak
                </h1>
                <p
                    class="mx-auto max-w-xl text-lg leading-relaxed text-white/90 md:text-xl"
                >
                    Anda tidak memiliki izin untuk mengakses area terbatas ini.
                    Silakan hubungi administrator jika Anda memerlukan akses.
                </p>
            </div>

            <!-- Action Buttons -->
            <div
                class="flex animate-[fadeInUp_0.6s_ease-out_0.5s_both] flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link href="/">
                    <button
                        class="group inline-flex items-center gap-3 rounded-xl bg-white px-8 py-4 text-lg font-bold text-primary shadow-xl transition-all hover:scale-105 hover:shadow-2xl active:scale-95"
                    >
                        <svg
                            class="h-5 w-5 transition-transform group-hover:rotate-12"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        Kembali ke Beranda
                    </button>
                </Link>

                <button
                    @click="goBack"
                    class="group inline-flex items-center gap-3 rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-lg font-semibold text-white backdrop-blur-md transition-all hover:scale-105 hover:border-white/50 hover:bg-white/20 active:scale-95"
                >
                    <svg
                        class="h-5 w-5 transition-transform group-hover:-rotate-12"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Halaman Sebelumnya
                </button>
            </div>

            <!-- Additional Info -->
            <div
                class="mt-12 animate-[fadeIn_0.8s_ease-out_0.8s_both] space-y-2"
            >
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 backdrop-blur-sm"
                >
                    <span class="font-mono text-sm text-white/60"
                        >Error Code: 403</span
                    >
                    <span class="text-white/40">|</span>
                    <span class="text-sm text-white/60">Forbidden Access</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes wiggle {
    0%,
    100% {
        transform: translateX(0) rotate(0deg);
    }

    25% {
        transform: translateX(10px) rotate(2deg);
    }

    50% {
        transform: translateX(0) rotate(0deg);
    }

    75% {
        transform: translateX(-10px) rotate(-2deg);
    }
}
</style>
