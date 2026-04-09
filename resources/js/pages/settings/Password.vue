<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/user-password';
import { type BreadcrumbItem } from '@/types';

defineProps<{
    requireOldPassword: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: edit().url,
    },
];

const passwordInput = ref('');
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Password settings" />

        <h1 class="sr-only">Password Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <!-- Current Password -->
                    <div v-if="requireOldPassword" class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <div class="relative">
                            <Input
                                id="current_password"
                                name="current_password"
                                :type="
                                    showCurrentPassword ? 'text' : 'password'
                                "
                                class="mt-1 block w-full pr-10"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <button
                                type="button"
                                @click="
                                    showCurrentPassword = !showCurrentPassword
                                "
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                            >
                                <EyeOff
                                    v-if="showCurrentPassword"
                                    class="h-4 w-4"
                                />
                                <Eye
                                    v-else
                                    class="h-4 w-4"
                                />
                            </button>
                        </div>
                        <InputError :message="errors.current_password" />
                    </div>

                    <!-- New Password -->
                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                name="password"
                                :type="showNewPassword ? 'text' : 'password'"
                                v-model="passwordInput"
                                class="mt-1 block w-full pr-10"
                                autocomplete="new-password"
                                placeholder="New password"
                            />
                            <button
                                type="button"
                                @click="showNewPassword = !showNewPassword"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                            >
                                <EyeOff
                                    v-if="showNewPassword"
                                    class="h-4 w-4"
                                />
                                <Eye
                                    v-else
                                    class="h-4 w-4"
                                />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                        <div
                            v-if="passwordInput"
                            class="mt-1 flex flex-wrap gap-2 text-xs"
                        >
                            <span
                                class="flex items-center gap-1"
                                :class="
                                    passwordInput.length >= 8
                                        ? 'text-green-600'
                                        : 'text-gray-400'
                                "
                            >
                                <svg
                                    v-if="passwordInput.length >= 8"
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <line
                                        x1="18"
                                        y1="6"
                                        x2="6"
                                        y2="18"
                                    />
                                    <line
                                        x1="6"
                                        y1="6"
                                        x2="18"
                                        y2="18"
                                    />
                                </svg>
                                Min 8 characters
                            </span>
                            <span
                                class="flex items-center gap-1"
                                :class="
                                    /[A-Z]/.test(passwordInput)
                                        ? 'text-green-600'
                                        : 'text-gray-400'
                                "
                            >
                                <svg
                                    v-if="/[A-Z]/.test(passwordInput)"
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <line
                                        x1="18"
                                        y1="6"
                                        x2="6"
                                        y2="18"
                                    />
                                    <line
                                        x1="6"
                                        y1="6"
                                        x2="18"
                                        y2="18"
                                    />
                                </svg>
                                Uppercase
                            </span>
                            <span
                                class="flex items-center gap-1"
                                :class="
                                    /[0-9]/.test(passwordInput)
                                        ? 'text-green-600'
                                        : 'text-gray-400'
                                "
                            >
                                <svg
                                    v-if="/[0-9]/.test(passwordInput)"
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <line
                                        x1="18"
                                        y1="6"
                                        x2="6"
                                        y2="18"
                                    />
                                    <line
                                        x1="6"
                                        y1="6"
                                        x2="18"
                                        y2="18"
                                    />
                                </svg>
                                Number
                            </span>
                            <span
                                class="flex items-center gap-1"
                                :class="
                                    /[^A-Za-z0-9]/.test(passwordInput)
                                        ? 'text-green-600'
                                        : 'text-gray-400'
                                "
                            >
                                <svg
                                    v-if="/[^A-Za-z0-9]/.test(passwordInput)"
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3 w-3"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <line
                                        x1="18"
                                        y1="6"
                                        x2="6"
                                        y2="18"
                                    />
                                    <line
                                        x1="6"
                                        y1="6"
                                        x2="18"
                                        y2="18"
                                    />
                                </svg>
                                Symbol (!@#$...)
                            </span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                class="mt-1 block w-full pr-10"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                            <button
                                type="button"
                                @click="
                                    showConfirmPassword = !showConfirmPassword
                                "
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                            >
                                <EyeOff
                                    v-if="showConfirmPassword"
                                    class="h-4 w-4"
                                />
                                <Eye
                                    v-else
                                    class="h-4 w-4"
                                />
                            </button>
                        </div>
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            Save password
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
