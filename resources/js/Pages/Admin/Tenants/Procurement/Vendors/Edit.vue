<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Rating from 'primevue/rating';
import Toast from 'primevue/toast';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    vendor: Object,
});

const toast = useToast();

const form = useForm({
    name: props.vendor.name,
    type: props.vendor.type,
    contact_person: props.vendor.contact_person || '',
    phone: props.vendor.phone || '',
    email: props.vendor.email || '',
    address: props.vendor.address || '',
    city: props.vendor.city || '',
    postal_code: props.vendor.postal_code || '',
    tax_id: props.vendor.tax_id || '',
    business_registration: props.vendor.business_registration || '',
    bank_details: props.vendor.bank_details || '',
    rating: props.vendor.rating || 0,
    status: props.vendor.status,
    blacklist_reason: props.vendor.blacklist_reason || '',
    payment_terms_days: props.vendor.payment_terms_days || 30,
    credit_limit: props.vendor.credit_limit || 0,
    notes: props.vendor.notes || '',
});

const typeOptions = [
    { label: 'Supplier', value: 'supplier' },
    { label: 'Contractor', value: 'contractor' },
    { label: 'Service Provider', value: 'service_provider' },
];

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Blacklisted', value: 'blacklisted' },
];

const submit = () => {
    form.put(route('tenant.vendors.update', props.vendor.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Vendor updated successfully',
                life: 3000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Please check the form for errors',
                life: 3000
            });
        },
    });
};

const cancel = () => {
    if (confirm('Are you sure? Any unsaved changes will be lost.')) {
        window.history.back();
    }
};

const requiresBlacklistReason = computed(() => form.status === 'blacklisted');
const formChanged = computed(() => form.isDirty);
</script>

<template>
    <TenantLayout title="Edit Vendor">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Edit Vendor</h1>
                        <p class="text-gray-600 mt-1">Update vendor information for {{ vendor.name }}</p>
                        <div class="mt-2">
                            <Tag :value="vendor.code" severity="info" class="font-mono" />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Cancel" icon="pi pi-times" severity="secondary" @click="cancel" outlined />
                        <Button label="Save Changes" icon="pi pi-check" @click="submit" :loading="form.processing" :disabled="!formChanged" />
                    </div>
                </div>

                <Message v-if="formChanged" severity="warn" :closable="false">
                    You have unsaved changes. Click "Save Changes" to update the vendor.
                </Message>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <template #title>Basic Information</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium mb-2">Vendor Name <span class="text-red-500">*</span></label>
                                            <InputText v-model="form.name" placeholder="Enter vendor name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
                                            <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">Vendor Type <span class="text-red-500">*</span></label>
                                            <Dropdown v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Select Type" class="w-full" :class="{ 'p-invalid': form.errors.type }" />
                                            <small class="p-error" v-if="form.errors.type">{{ form.errors.type }}</small>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">Status <span class="text-red-500">*</span></label>
                                            <Dropdown v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Select Status" class="w-full" :class="{ 'p-invalid': form.errors.status }" />
                                            <small class="p-error" v-if="form.errors.status">{{ form.errors.status }}</small>
                                        </div>
                                        <div class="md:col-span-2" v-if="requiresBlacklistReason">
                                            <label class="block text-sm font-medium mb-2">Blacklist Reason <span class="text-red-500">*</span></label>
                                            <Textarea v-model="form.blacklist_reason" rows="3" placeholder="Enter reason for blacklisting" class="w-full" :class="{ 'p-invalid': form.errors.blacklist_reason }" />
                                            <small class="p-error" v-if="form.errors.blacklist_reason">{{ form.errors.blacklist_reason }}</small>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium mb-2">Vendor Rating</label>
                                            <Rating v-model="form.rating" :cancel="false" />
                                            <small class="text-gray-500">Rate the vendor's performance</small>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Contact Information</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div><label class="block text-sm font-medium mb-2">Contact Person</label><InputText v-model="form.contact_person" placeholder="Enter contact person name" class="w-full" /></div>
                                        <div><label class="block text-sm font-medium mb-2">Phone Number</label><InputText v-model="form.phone" placeholder="01711-123456" class="w-full" :class="{ 'p-invalid': form.errors.phone }" /><small class="p-error" v-if="form.errors.phone">{{ form.errors.phone }}</small></div>
                                        <div><label class="block text-sm font-medium mb-2">Email Address</label><InputText v-model="form.email" type="email" placeholder="vendor@example.com" class="w-full" :class="{ 'p-invalid': form.errors.email }" /><small class="p-error" v-if="form.errors.email">{{ form.errors.email }}</small></div>
                                        <div><label class="block text-sm font-medium mb-2">City</label><InputText v-model="form.city" placeholder="Enter city" class="w-full" /></div>
                                        <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Address</label><Textarea v-model="form.address" rows="3" placeholder="Enter complete address" class="w-full" /></div>
                                        <div><label class="block text-sm font-medium mb-2">Postal Code</label><InputText v-model="form.postal_code" placeholder="Enter postal code" class="w-full" /></div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Business Details</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div><label class="block text-sm font-medium mb-2">Tax ID / TIN</label><InputText v-model="form.tax_id" placeholder="Enter Tax ID or TIN" class="w-full" /><small class="text-gray-500">Tax Identification Number</small></div>
                                        <div><label class="block text-sm font-medium mb-2">Business Registration No.</label><InputText v-model="form.business_registration" placeholder="Enter registration number" class="w-full" /></div>
                                        <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Bank Details</label><Textarea v-model="form.bank_details" rows="3" placeholder="Bank name, account number, branch, etc." class="w-full" /></div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Additional Information</template>
                            <template #content>
                                <div><label class="block text-sm font-medium mb-2">Notes</label><Textarea v-model="form.notes" rows="5" placeholder="Any additional notes or remarks about this vendor" class="w-full" /></div>
                            </template>
                        </Card>
                    </div>

                    <div class="space-y-6">
                        <Card>
                            <template #title>Payment Terms</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div><label class="block text-sm font-medium mb-2">Payment Due (Days)</label><InputNumber v-model="form.payment_terms_days" placeholder="30" class="w-full" :min="0" :max="365" suffix=" days" /><small class="text-gray-500">Days until payment is due</small></div>
                                    <div><label class="block text-sm font-medium mb-2">Credit Limit</label><InputNumber v-model="form.credit_limit" mode="currency" currency="BDT" locale="en-BD" placeholder="0.00" class="w-full" :min="0" /><small class="text-gray-500">Maximum credit allowed</small></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Vendor Information</template>
                            <template #content>
                                <div class="space-y-3 text-sm">
                                    <div><span class="text-gray-600">Vendor Code:</span><span class="font-mono font-medium ml-2">{{ vendor.code }}</span></div>
                                    <Divider />
                                    <div v-if="vendor.creator"><span class="text-gray-600">Created by:</span><span class="ml-2">{{ vendor.creator.name }}</span></div>
                                    <div><span class="text-gray-600">Created on:</span><span class="ml-2">{{ new Date(vendor.created_at).toLocaleDateString() }}</span></div>
                                    <Divider />
                                    <div v-if="vendor.updater"><span class="text-gray-600">Last updated by:</span><span class="ml-2">{{ vendor.updater.name }}</span></div>
                                    <div><span class="text-gray-600">Last updated:</span><span class="ml-2">{{ new Date(vendor.updated_at).toLocaleDateString() }}</span></div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </form>
        </div>

        <Toast />
    </TenantLayout>
</template>