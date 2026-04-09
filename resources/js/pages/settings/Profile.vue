<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    profilePhotoUrl?: string | null;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

const fileInputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.profilePhotoUrl ?? null);
const selectedFileName = ref<string>('');

function triggerFileInput() {
    fileInputRef.value?.click();
}

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    selectedFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function removePhoto() {
    previewUrl.value = null;
    selectedFileName.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError
                            class="mt-2"
                            :message="errors.name"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError
                            class="mt-2"
                            :message="errors.email"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="profile_photo">Profile Photo</Label>

                        <div
                            class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-input p-6 transition-colors hover:bg-muted/40"
                            @click="triggerFileInput"
                        >
                            <!-- Preview -->
                            <div
                                v-if="previewUrl"
                                class="relative"
                            >
                                <img
                                    :src="previewUrl"
                                    alt="Profile preview"
                                    class="h-24 w-24 rounded-full border border-border object-cover"
                                />
                                <button
                                    type="button"
                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-border bg-background text-xs text-muted-foreground hover:text-foreground"
                                    @click.stop="removePhoto"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Placeholder -->
                            <div
                                v-else
                                class="flex h-14 w-14 items-center justify-center rounded-full border border-border bg-muted"
                            >
                                <svg
                                    class="h-6 w-6 text-muted-foreground"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                    />
                                    <circle
                                        cx="12"
                                        cy="7"
                                        r="4"
                                    />
                                </svg>
                            </div>

                            <div class="text-center">
                                <p class="text-sm text-foreground">
                                    {{
                                        previewUrl
                                            ? 'Click to change photo'
                                            : 'Click to upload photo'
                                    }}
                                </p>
                                <p
                                    v-if="selectedFileName"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ selectedFileName }}
                                </p>
                                <p
                                    v-else-if="!previewUrl"
                                    class="text-xs text-muted-foreground"
                                >
                                    PNG, JPG, GIF up to 2MB
                                </p>
                            </div>

                            <input
                                id="profile_photo"
                                ref="fileInputRef"
                                type="file"
                                name="profile_photo"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileChange"
                            />
                        </div>

                        <InputError
                            class="mt-2"
                            :message="errors.profile_photo"
                        />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
