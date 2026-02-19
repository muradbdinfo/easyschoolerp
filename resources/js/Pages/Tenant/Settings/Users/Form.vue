<script setup>
import { reactive, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import InputText    from 'primevue/inputtext';
import Password     from 'primevue/password';
import Select       from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button       from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { UserPlus, UserCog } from 'lucide-vue-next';

const props = defineProps({
    user:        Object,   // null = create, object = edit
    branches:    Array,
    departments: Array,
    roles:       Object,
});

const isEdit = computed(() => !!props.user);
const toast  = useToast();

const form = useForm({
    name:          props.user?.name          ?? '',
    email:         props.user?.email         ?? '',
    password:      '',
    password_confirmation: '',
    role:          props.user?.role          ?? 'staff',
    branch_id:     props.user?.branch_id     ?? null,
    department_id: props.user?.department_id ?? null,
    phone:         props.user?.phone         ?? '',
    is_active:     props.user?.is_active     ?? true,
});

const roleOptions = computed(() =>
    Object.entries(props.roles).map(([value, label]) => ({ label, value }))
);

const submit = () => {
    if (isEdit.value) {
        form.put(route('tenant.settings.users.update', props.user.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'User updated.', life: 3000 }),
            onError:   () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix errors below.', life: 4000 }),
        });
    } else {
        form.post(route('tenant.settings.users.store'), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Created', detail: 'User created successfully.', life: 3000 }),
            onError:   () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix errors below.', life: 4000 }),
        });
    }
};

const breadcrumbs = [
    { label: 'Settings' },
    { label: 'Users', route: route('tenant.settings.users.index') },
    { label: isEdit.value ? 'Edit User' : 'Add User' },
];
</script>

<template>
    <TenantLayout :breadcrumbItems="breadcrumbs">
        <div class="max-w-2xl space-y-6">

            <!-- Page header -->
            <div class="flex items-center gap-3">
                <component :is="isEdit ? UserCog : UserPlus" :size="26" class="text-blue-600" />
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isEdit ? 'Edit User' : 'Add New User' }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ isEdit ? `Editing ${user.name}` : 'Create a staff account' }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Basic Info card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <h2 class="font-semibold text-gray-800 text-base">Basic Information</h2>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            v-model="form.name"
                            placeholder="e.g. Rahim Uddin"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            v-model="form.email"
                            type="email"
                            placeholder="e.g. rahim@myschool.com"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Phone -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Phone</label>
                        <InputText
                            v-model="form.phone"
                            placeholder="e.g. 01711-123456"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Password card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <h2 class="font-semibold text-gray-800 text-base">
                        {{ isEdit ? 'Change Password' : 'Password' }}
                        <span v-if="isEdit" class="text-sm font-normal text-gray-400 ml-2">
                            (leave blank to keep current)
                        </span>
                    </h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">
                                Password <span v-if="!isEdit" class="text-red-500">*</span>
                            </label>
                            <Password
                                v-model="form.password"
                                :feedback="true"
                                toggleMask
                                class="w-full"
                                inputClass="w-full"
                                :class="{ 'p-invalid': form.errors.password }"
                                :placeholder="isEdit ? 'New password…' : 'Min 8 characters'"
                            />
                            <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">
                                Confirm Password
                            </label>
                            <Password
                                v-model="form.password_confirmation"
                                :feedback="false"
                                toggleMask
                                class="w-full"
                                inputClass="w-full"
                                placeholder="Repeat password"
                            />
                        </div>
                    </div>
                </div>

                <!-- Role & Assignment card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <h2 class="font-semibold text-gray-800 text-base">Role & Assignment</h2>

                    <!-- Role -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <Select
                            v-model="form.role"
                            :options="roleOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select role"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.role }"
                        />
                        <p v-if="form.errors.role" class="text-xs text-red-500">{{ form.errors.role }}</p>
                        <p class="text-xs text-gray-400">
                            Role controls approval authority in procurement workflows.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Branch -->
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Branch</label>
                            <Select
                                v-model="form.branch_id"
                                :options="[{ id: null, name: 'Not assigned' }, ...branches]"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select branch"
                                class="w-full"
                            />
                        </div>

                        <!-- Department -->
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Department</label>
                            <Select
                                v-model="form.department_id"
                                :options="[{ id: null, name: 'Not assigned' }, ...departments]"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select department"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- Active toggle card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800">Account Status</p>
                            <p class="text-sm text-gray-500">
                                Inactive users cannot log in to the system.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm" :class="form.is_active ? 'text-green-600 font-medium' : 'text-gray-400'">
                                {{ form.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <ToggleSwitch v-model="form.is_active" />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pb-4">
                    <Button
                        type="button"
                        label="Cancel"
                        outlined
                        @click="router.visit(route('tenant.settings.users.index'))"
                    />
                    <Button
                        type="submit"
                        :label="isEdit ? 'Save Changes' : 'Create User'"
                        :loading="form.processing"
                        icon="pi pi-check"
                    />
                </div>

            </form>
        </div>
    </TenantLayout>
</template>