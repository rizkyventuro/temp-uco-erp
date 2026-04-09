<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';

const showPassword = ref(false);
const showConfirm = ref(false);
const passwordInput = ref('');
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            method="post"
            action="/register"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <div class="relative">
                        <Input
                            id="password"
                            v-model="passwordInput"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Password"
                            class="pr-10"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                        >
                            <EyeOff
                                v-if="showPassword"
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
                    <Label for="password_confirmation">Confirm password</Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            class="pr-10"
                        />
                        <button
                            type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                        >
                            <EyeOff
                                v-if="showConfirm"
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

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Log in</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
