<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Breadcrumb from 'primevue/breadcrumb';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    vendor: Object,
    stats: Object,
});

const toast = useToast();
const confirm = useConfirm();
const activeTab = ref(0);

const editVendor = () => {
    router.visit(route('tenant.vendors.edit', props.vendor.id));
};

const deleteVendor = () => {
    confirm.require({
        message: `Are you sure you want to delete vendor "${props.vendor.name}"? This action cannot be undone.`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.vendors.destroy', props.vendor.id), {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Vendor deleted successfully',
                        life: 3000
                    });
                },
            });
        },
    });
};

const blacklistVendor = () => {
    confirm.require({
        message: `Are you sure you want to blacklist "${props.vendor.name}"?`,
        header: 'Blacklist Vendor',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const reason = prompt('Please provide a reason for blacklisting:');
            if (reason) {
                router.post(route('tenant.vendors.blacklist', props.vendor.id), {
                    reason: reason
                }, {
                    onSuccess: () => {
                        toast.add({
                            severity: 'success',
                            summary: 'Success',
                            detail: 'Vendor blacklisted successfully',
                            life: 3000
                        });
                    },
                });
            }
        },
    });
};

const activateVendor = () => {
    confirm.require({
        message: `Activate vendor "${props.vendor.name}"?`,
        header: 'Activate Vendor',
        icon: 'pi pi-check-circle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('tenant.vendors.activate', props.vendor.id), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Vendor activated successfully',
                        life: 3000
                    });
                },
            });
        },
    });
};

const viewPurchaseOrder = (po) => {
    router.visit(route('tenant.purchase-orders.show', po.id));
};
</script>

<template>
    <TenantLayout :title="`Vendor: ${vendor.name}`">
        <div class="p-6">
            <Breadcrumb :home="{label: 'Dashboard', url: '/'}" :model="[
                {label: 'Procurement', url: '/procurement'},
                {label: 'Vendors', url: route('tenant.vendors.index')},
                {label: vendor.name}
            ]" class="mb-4" />

            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-0 shadow-sm mb-6">
                <template #content>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="pi pi-users text-3xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">{{ vendor.name }}</h1>
                                <div class="flex items-center gap-3 mt-2">
                                    <Tag :value="vendor.code" severity="info" class="font-mono" />
                                    <Tag :value="vendor.status_badge.label" :severity="vendor.status_badge.severity" />
                                    <Tag :value="vendor.type_label" />
                                </div>
                                <div class="mt-3">
                                    <Rating :modelValue="vendor.rating" :readonly="true" :cancel="false" />
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" label="Edit" severity="success" @click="editVendor" outlined />
                            <Button v-if="vendor.status === 'blacklisted'" icon="pi pi-check" label="Activate" severity="success" @click="activateVendor" />
                            <Button v-else-if="vendor.status === 'active'" icon="pi pi-ban" label="Blacklist" severity="warning" @click="blacklistVendor" outlined />
                            <Button icon="pi pi-trash" label="Delete" severity="danger" @click="deleteVendor" outlined />
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Orders</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_orders }}</p>
                            </div>
                            <i class="pi pi-shopping-cart text-4xl text-blue-500"></i>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Amount</p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ stats.total_amount?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) || '৳0' }}
                                </p>
                            </div>
                            <i class="pi pi-dollar text-4xl text-green-500"></i>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Pending Orders</p>
                                <p class="text-2xl font-bold text-orange-600">{{ stats.pending_orders }}</p>
                            </div>
                            <i class="pi pi-clock text-4xl text-orange-500"></i>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Vendor Rating</p>
                                <div class="flex items-center gap-2">
                                    <p class="text-2xl font-bold text-yellow-600">{{ vendor.rating }}</p>
                                    <span class="text-sm text-gray-500">/ 5.0</span>
                                </div>
                            </div>
                            <i class="pi pi-star text-4xl text-yellow-500"></i>
                        </div>
                    </template>
                </Card>
            </div>

            <TabView v-model:activeIndex="activeTab">
                <TabPanel header="Details">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-phone text-blue-600"></i>
                                    Contact Information
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <div v-if="vendor.contact_person">
                                        <label class="text-sm font-medium text-gray-600">Contact Person</label>
                                        <p class="text-gray-900 mt-1">{{ vendor.contact_person }}</p>
                                    </div>
                                    <div v-if="vendor.phone" class="flex items-center gap-2">
                                        <i class="pi pi-phone text-gray-500"></i>
                                        <a :href="`tel:${vendor.phone}`" class="text-blue-600 hover:underline">{{ vendor.phone }}</a>
                                    </div>
                                    <div v-if="vendor.email" class="flex items-center gap-2">
                                        <i class="pi pi-envelope text-gray-500"></i>
                                        <a :href="`mailto:${vendor.email}`" class="text-blue-600 hover:underline">{{ vendor.email }}</a>
                                    </div>
                                    <div v-if="vendor.address">
                                        <div class="flex items-start gap-2">
                                            <i class="pi pi-map-marker text-gray-500 mt-1"></i>
                                            <div>
                                                <p class="text-gray-900">{{ vendor.address }}</p>
                                                <p class="text-gray-600 text-sm" v-if="vendor.city">
                                                    {{ vendor.city }}<span v-if="vendor.postal_code">, {{ vendor.postal_code }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-building text-blue-600"></i>
                                    Business Details
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <div v-if="vendor.tax_id">
                                        <label class="text-sm font-medium text-gray-600">Tax ID / TIN</label>
                                        <p class="text-gray-900 mt-1 font-mono">{{ vendor.tax_id }}</p>
                                    </div>
                                    <div v-if="vendor.business_registration">
                                        <label class="text-sm font-medium text-gray-600">Business Registration</label>
                                        <p class="text-gray-900 mt-1 font-mono">{{ vendor.business_registration }}</p>
                                    </div>
                                    <div v-if="vendor.bank_details">
                                        <label class="text-sm font-medium text-gray-600">Bank Details</label>
                                        <p class="text-gray-900 mt-1 whitespace-pre-line">{{ vendor.bank_details }}</p>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-credit-card text-blue-600"></i>
                                    Payment Terms
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Payment Due</label>
                                        <p class="text-gray-900 mt-1 text-2xl font-semibold">
                                            {{ vendor.payment_terms_days }} <span class="text-sm font-normal">days</span>
                                        </p>
                                    </div>
                                    <Divider />
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Credit Limit</label>
                                        <p class="text-gray-900 mt-1 text-2xl font-semibold">
                                            {{ vendor.credit_limit?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card v-if="vendor.status === 'blacklisted'" class="border-2 border-red-200">
                            <template #title>
                                <div class="flex items-center gap-2 text-red-600">
                                    <i class="pi pi-ban"></i>
                                    Blacklist Information
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Blacklisted On</label>
                                        <p class="text-gray-900 mt-1">{{ new Date(vendor.blacklisted_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Reason</label>
                                        <p class="text-red-700 mt-1 bg-red-50 p-3 rounded">{{ vendor.blacklist_reason }}</p>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card v-if="vendor.notes" class="lg:col-span-2">
                            <template #title>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-file-edit text-blue-600"></i>
                                    Additional Notes
                                </div>
                            </template>
                            <template #content>
                                <p class="text-gray-700 whitespace-pre-line">{{ vendor.notes }}</p>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <TabPanel header="Purchase Orders">
                    <Card>
                        <template #content>
                            <DataTable :value="vendor.purchase_orders || []" stripedRows :paginator="vendor.purchase_orders?.length > 10" :rows="10">
                                <template #empty>
                                    <div class="text-center py-8">
                                        <i class="pi pi-shopping-cart text-6xl text-gray-400 mb-4"></i>
                                        <p class="text-gray-500 text-lg">No purchase orders yet</p>
                                        <p class="text-gray-400 text-sm mt-2">Purchase orders from this vendor will appear here</p>
                                    </div>
                                </template>

                                <Column field="po_number" header="PO Number" :sortable="true">
                                    <template #body="{ data }">
                                        <span class="font-mono">{{ data.po_number }}</span>
                                    </template>
                                </Column>
                                <Column field="date" header="Date" :sortable="true">
                                    <template #body="{ data }">
                                        {{ new Date(data.date).toLocaleDateString() }}
                                    </template>
                                </Column>
                                <Column field="branch" header="Branch" :sortable="true" />
                                <Column field="total_amount" header="Amount" :sortable="true">
                                    <template #body="{ data }">
                                        {{ data.total_amount?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}
                                    </template>
                                </Column>
                                <Column field="status" header="Status" :sortable="true">
                                    <template #body="{ data }">
                                        <Tag :value="data.status" :severity="data.status === 'completed' ? 'success' : 'info'" />
                                    </template>
                                </Column>
                                <Column header="Actions">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-eye" severity="info" text rounded @click="viewPurchaseOrder(data)" v-tooltip.top="'View Details'" />
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel header="Activity">
                    <Card>
                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created by:</span>
                                    <span class="font-medium">{{ vendor.creator?.name || 'System' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created on:</span>
                                    <span class="font-medium">{{ new Date(vendor.created_at).toLocaleString() }}</span>
                                </div>
                                <Divider />
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last updated by:</span>
                                    <span class="font-medium">{{ vendor.updater?.name || vendor.creator?.name || 'System' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last updated:</span>
                                    <span class="font-medium">{{ new Date(vendor.updated_at).toLocaleString() }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </TabPanel>
            </TabView>
        </div>

        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>