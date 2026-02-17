<template>
    <AdminLayout page-title="Edit Tenant">
        <!-- ADD THIS: Toast Component -->
        <Toast position="top-right" />
        
        <div class="max-w-3xl mx-auto">
            <Card>
                <template #title>
                    <div class="flex items-center gap-3">
                        <Button 
                            icon="pi pi-arrow-left" 
                            text 
                            rounded 
                            @click="goBack"
                        />
                        <span>Edit Tenant</span>
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
                                    Subdomain
                                </label>
                                <div class="flex items-center gap-2">
                                    <InputText 
                                        :value="tenant.subdomain"
                                        disabled
                                        class="flex-1"
                                    />
                                    <span class="text-gray-500">.easyschool.local</span>
                                </div>
                                <small class="text-gray-500 block mt-1">
                                    Subdomain cannot be changed after creation
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
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <Dropdown 
                                        v-model="form.status"
                                        :options="statusOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select Status"
                                        class="w-full"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Monthly Recurring Revenue (MRR) <span class="text-red-500">*</span>
                                </label>
                                <InputNumber 
                                    v-model="form.mrr"
                                    mode="currency"
                                    currency="USD"
                                    locale="en-US"
                                    :min="0"
                                    class="w-full"
                                />
                                <small class="text-gray-500 block mt-1">
                                    Monthly subscription amount
                                </small>
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
                                <small class="text-red-500" v-if="form.errors.email">
                                    {{ form.errors.email }}
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

                        <!-- Additional Information -->
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Database Name:</span>
                                <span class="font-medium">{{ tenant.database_name }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium">{{ formatDate(tenant.created_at) }}</span>
                            </div>
                            <div class="flex justify-between text-sm" v-if="tenant.trial_ends_at">
                                <span class="text-gray-600">Trial Ends:</span>
                                <span class="font-medium">{{ formatDate(tenant.trial_ends_at) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3">
                            <Button 
                                label="Cancel" 
                                severity="secondary" 
                                outlined
                                @click="goBack"
                            />
                            <Button 
                                type="submit"
                                label="Update Tenant" 
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
import { useForm, router } from '@inertiajs/vue3';
// ADD THIS: Import useToast
import { useToast } from 'primevue/usetoast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Divider from 'primevue/divider';
// ADD THIS: Import Toast component
import Toast from 'primevue/toast';

const props = defineProps({
    tenant: Object,
});

// ADD THIS: Initialize toast
const toast = useToast();

const planOptions = [
    { label: 'Basic - $50/month', value: 'basic' },
    { label: 'Professional - $100/month', value: 'professional' },
    { label: 'Enterprise - $200/month', value: 'enterprise' },
];

const statusOptions = [
    { label: 'Trial', value: 'trial' },
    { label: 'Active', value: 'active' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Cancelled', value: 'cancelled' },
];

const form = useForm({
    name: props.tenant.name,
    plan: props.tenant.plan,
    status: props.tenant.status,
    contact_name: props.tenant.contact_name,
    contact_email: props.tenant.contact_email,
    contact_phone: props.tenant.contact_phone,
    mrr: props.tenant.mrr || 0,
});

const goBack = () => {
    router.visit('/admin/tenants');
};

const submit = () => {
    form.put(`/admin/tenants/${props.tenant.id}`, {
        onSuccess: () => {
            // ADD THIS: Show success toast
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Tenant updated successfully!',
                life: 3000
            });
        },
        onError: () => {
            // ADD THIS: Show error toast
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update tenant. Please check the form.',
                life: 5000
            });
        },
    });
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>