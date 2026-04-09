<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    type: 'offline' | 'timeout';
}>();

const emit = defineEmits<{
    (e: 'retry'): void;
}>();

const isRetrying = ref(false);

const handleRetry = () => {
    isRetrying.value = true;
    emit('retry');
    setTimeout(() => {
        isRetrying.value = false;
    }, 1000);
};
</script>

<template>
    <div
        class="fixed inset-0 z-[9999] flex min-h-screen items-center justify-center overflow-hidden bg-primary-hover px-4"
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

            <!-- Error Text Number / Label -->
            <div class="relative mb-6">
                <div
                    class="animate-[fadeIn_0.8s_ease-out] text-[120px] leading-none font-black text-white/10 select-none md:text-[160px]"
                >
                    {{ props.type === 'offline' ? '503' : '408' }}
                </div>
            </div>

            <!-- Text Content -->
            <div
                class="mb-10 animate-[fadeInUp_0.6s_ease-out_0.3s_both] space-y-4"
            >
                <h1 class="text-4xl font-bold text-white md:text-5xl">
                    {{
                        props.type === 'offline'
                            ? 'Koneksi Terputus'
                            : 'Koneksi Berakhir'
                    }}
                </h1>
                <p
                    class="mx-auto max-w-xl text-lg leading-relaxed text-white/90 md:text-xl"
                >
                    {{
                        props.type === 'offline'
                            ? 'Mohon periksa sambungan internet Anda. Kami tidak dapat terhubung ke server saat ini.'
                            : 'Permintaan ke server memakan waktu lebih lama dari yang diharapkan (>1 menit). Mohon coba lagi.'
                    }}
                </p>
                <div
                    class="inline-flex items-center gap-2 text-sm text-white/70"
                >
                    <svg
                        v-if="props.type === 'offline'"
                        class="h-4 w-4 animate-pulse"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 5.636a9 9 0 011.085 11.233M15.536 8.464a5 5 0 01.993 5.923M12 20h.01M5.636 5.636a9 9 0 00-1.085 11.233M8.464 8.464a5 5 0 00-.993 5.923M3 3l18 18"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-4 w-4 animate-spin"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    <span>{{
                        props.type === 'offline'
                            ? 'Menunggu koneksi internet...'
                            : 'Menghubungi server...'
                    }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                class="flex animate-[fadeInUp_0.6s_ease-out_0.5s_both] flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <button
                    @click="handleRetry"
                    :disabled="isRetrying"
                    class="group inline-flex items-center gap-3 rounded-xl bg-white px-8 py-4 text-lg font-bold text-primary shadow-xl transition-all hover:scale-105 hover:shadow-2xl active:scale-95 disabled:scale-100 disabled:opacity-75"
                >
                    <svg
                        :class="{
                            'animate-spin': isRetrying,
                            'group-hover:rotate-180': !isRetrying,
                        }"
                        class="h-5 w-5 transition-transform"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ isRetrying ? 'Mencoba lagi...' : 'Coba Lagi' }}
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
                        >Error Code:
                        {{ props.type === 'offline' ? '503' : '408' }}</span
                    >
                    <span class="text-white/40">|</span>
                    <span class="text-sm text-white/60">
                        {{
                            props.type === 'offline'
                                ? 'Service Unavailable'
                                : 'Request Timeout'
                        }}</span
                    >
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
