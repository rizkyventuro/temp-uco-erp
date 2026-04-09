<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import GlobalNetworkError from '@/pages/errors/GlobalNetworkError.vue';

const isOffline = ref(!navigator.onLine);
const hasTimeoutError = ref(false);

const handleOnline = () => {
    isOffline.value = false;
};

const handleOffline = () => {
    isOffline.value = true;
};

const handleTimeout = () => {
    hasTimeoutError.value = true;
};

const handleRetry = () => {
    // If we're retrying a timeout error, we clear the flag
    if (hasTimeoutError.value) {
        hasTimeoutError.value = false;
    }
    // We let the browser or inertia attempt to fetch again
    // For a robust retry, we might want to reload the page or trigger an inertia visit
    if (!navigator.onLine) {
        // still offline, just update the state if it wasn't caught
        isOffline.value = true;
    } else {
        isOffline.value = false;
        window.location.reload();
    }
};

onMounted(() => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Custom event to catch axios timeouts
    window.addEventListener('app:timeout', handleTimeout);
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    window.removeEventListener('app:timeout', handleTimeout);
});

defineProps<{
    App: any;
    props: any;
}>();
</script>

<template>
    <div>
        <!-- Main Application -->
        <component
            :is="App"
            v-bind="props"
        />

        <!-- High-Priority Network Error Overlays -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition opacity-0 duration-300"
                enter-to-class="opacity-100"
                leave-active-class="transition opacity-100 duration-300"
                leave-to-class="opacity-0"
            >
                <GlobalNetworkError
                    v-if="isOffline"
                    type="offline"
                    @retry="handleRetry"
                />
                <GlobalNetworkError
                    v-else-if="hasTimeoutError"
                    type="timeout"
                    @retry="handleRetry"
                />
            </Transition>
        </Teleport>
    </div>
</template>
