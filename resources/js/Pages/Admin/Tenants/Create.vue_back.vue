<template>
    <AdminLayout page-title="Create Tenant">
        <div class="max-w-3xl mx-auto">
            <Card>
                <template #title>
                    <div class="flex items-center gap-3">
                        <Button 
                            icon="pi pi-arrow-left" 
                            text 
                            rounded 
                            @click="$inertia.visit(route('admin.tenants.index'))"
                        />
                        <span>Create New Tenant</span>
                    </div>
                </template>
                <template #content>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- School Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">School Information</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    School Name <span class="text-red-500">*</span>
                                </label>
                                <InputText 
                                    v-model="form.name"
                                    placeholder="e.g., Green Valley International School"
                                    class="w-full"
                                    :invalid="!!form.errors.name"
                                />
                                <small class="text-red-500" v-if="form.errors.name">
                                    {{ form.errors.name }}
                                </small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Subdomain <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <InputText 
                                        v-model="form.subdomain"
                                        placeholder="greenvalley"
                                        class="flex-1"
                                        :invalid="!!form.errors.subdomain"
                                    />
                                    <span class="text-gray-500">.easyschool.local</span>
                                </div>
                                <small class="text-gray-500 block mt-1">
                                    Only lowercase letters, numbers, and hyphens
                                </small>
                                <small class="text-red-500" v-if="form.errors.subdomain">
                                    {{ form.errors.subdomain }}
                                </small>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Plan <span class="text-red-500">*</span>
                                    </label>
                                    <Dropdown 
                                        v-model="form.plan"
                                        :options="planOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select Plan"
                                        class="w-full"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Trial Period (Days) <span class="text-red-500">*</span>
                                    </label>
                                    <InputNumber 
                                        v-model="form.trial_days"
                                        :min="0"
                                        :max="90"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Contact Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Contact Information</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Name <span class="text-red-500">*</span>
                                </label>
                                <InputText 
                                    v-model="form.contact_name"
                                    placeholder="Principal or Administrator Name"
                                    class="w-full"
                                    :invalid="!!form.errors.contact_name"
                                />
                                <small class="text-red-500" v-if="form.errors.contact_name">
                                    {{ form.errors.contact_name }}
                                </small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <InputText 
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="admin@greenvalley.edu"
                                    class="w-full"
                                    :invalid="!!form.errors.contact_email"
                                />
                                <small class="text-red-500" v-if="form.errors.contact_email">
                                    {{ form.errors.contact_email }}
                                </small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number
                                </label>
                                <InputText 
                                    v-model="form.contact_phone"
                                    placeholder="+880 1XXX-XXXXXX"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <Divider />

                        <!-- Actions -->
                        <div class="flex justify-end gap-3">
                            <Button 
                                label="Cancel" 
                                severity="secondary" 
                                outlined
                                @click="$inertia.visit(route('admin.tenants.index'))"
                            />
                            <Button 
                                type="submit"
                                label="Create Tenant" 
                                :loading="form.processing"
                            />
                        </div>
                    </form>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Divider from 'primevue/divider';

const planOptions = [
    { label: 'Basic - $50/month', value: 'basic' },
    { label: 'Professional - $100/month', value: 'professional' },
    { label: 'Enterprise - $200/month', value: 'enterprise' },
];

const form = useForm({
    name: '',
    subdomain: '',
    plan: 'basic',
    trial_days: 30,
    contact_name: '',
    contact_email: '',
    contact_phone: '',
});

const submit = () => {
    form.post(route('admin.tenants.store'), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>