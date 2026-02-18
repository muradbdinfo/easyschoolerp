<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Breadcrumb from 'primevue/breadcrumb';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    purchaseOrder: Object,
});

const toast = useToast();
const confirm = useConfirm();

const formatCurrency = (val) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(val || 0);

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';
const formatDateTime = (d) => d ? new Date(d).toLocaleString() : '—';

const sendPO = () => {
    confirm.require({
        message: `Send PO ${props.purchaseOrder.po_number} to ${props.purchaseOrder.vendor?.name} (${props.purchaseOrder.vendor?.email})?`,
        header: 'Send Purchase Order',
        icon: 'pi pi-send',
        acceptClass: 'p-button-info',
        accept: () => {
            router.post(route('tenant.purchase-orders.send', props.purchaseOrder.id), {}, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Sent', detail: 'PO sent to vendor.', life: 3000 }),
            });
        },
    });
};

const cancelPO = () => {
    confirm.require({
        message: `Cancel PO ${props.purchaseOrder.po_number}? This cannot be undone.`,
        header: 'Cancel PO',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('tenant.purchase-orders.cancel', props.purchaseOrder.id), {}, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelled', detail: 'PO cancelled.', life: 3000 }),
            });
        },
    });
};

const printPO = () => window.print();
</script>

<template>
    <TenantLayout :title="`PO: ${purchaseOrder.po_number}`">
        <div class="p-6">
            <Breadcrumb
                :home="{ label: 'Dashboard', url: route('dashboard') }"
                :model="[
                    { label: 'Procurement' },
                    { label: 'Purchase Orders', url: route('tenant.purchase-orders.index') },
                    { label: purchaseOrder.po_number }
                ]"
                class="mb-4"
            />

            <!-- Hero Card -->
            <Card class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-0 shadow-sm">
                <template #content>
                    <div class="flex flex-wrap justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                    <i class="pi pi-box text-white text-xl"></i>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ purchaseOrder.po_number }}</h1>
                                    <p class="text-gray-600">{{ purchaseOrder.vendor?.name }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <Tag :value="purchaseOrder.status_badge.label" :severity="purchaseOrder.status_badge.severity" />
                                <Tag :value="purchaseOrder.branch?.name" severity="info" />
                                <Tag v-if="purchaseOrder.requisition" :value="`PR: ${purchaseOrder.requisition.pr_number}`" severity="secondary" />
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 items-start">
                            <Button
                                v-if="purchaseOrder.status === 'draft'"
                                icon="pi pi-pencil" label="Edit"
                                severity="success" outlined
                                @click="router.visit(route('tenant.purchase-orders.edit', purchaseOrder.id))"
                            />
                            <Button
                                v-if="purchaseOrder.status === 'draft'"
                                icon="pi pi-send" label="Send to Vendor"
                                severity="info"
                                @click="sendPO"
                            />
                            <Button
                                icon="pi pi-print" label="Print"
                                severity="secondary" outlined
                                @click="printPO"
                            />
                            <Button
                                v-if="!['received','closed','cancelled'].includes(purchaseOrder.status)"
                                icon="pi pi-times-circle" label="Cancel"
                                severity="danger" outlined
                                @click="cancelPO"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Order Details -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-blue-600"></i>
                                Order Details
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">PO Date</p>
                                    <p class="font-medium">{{ formatDate(purchaseOrder.po_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Expected Delivery</p>
                                    <p class="font-medium">{{ formatDate(purchaseOrder.expected_delivery_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Payment Terms</p>
                                    <p class="font-medium">{{ purchaseOrder.payment_terms_days }} days</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Branch</p>
                                    <p class="font-medium">{{ purchaseOrder.branch?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Department</p>
                                    <p class="font-medium">{{ purchaseOrder.department?.name || '—' }}</p>
                                </div>
                                <div v-if="purchaseOrder.sent_at">
                                    <p class="text-gray-500">Sent At</p>
                                    <p class="font-medium">{{ formatDateTime(purchaseOrder.sent_at) }}</p>
                                </div>
                            </div>

                            <div v-if="purchaseOrder.delivery_address" class="mt-4">
                                <p class="text-gray-500 text-sm">Delivery Address</p>
                                <p class="font-medium whitespace-pre-line">{{ purchaseOrder.delivery_address }}</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Vendor -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-blue-600"></i>
                                Vendor Details
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Name</p>
                                    <p class="font-medium">{{ purchaseOrder.vendor?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Code</p>
                                    <p class="font-mono">{{ purchaseOrder.vendor?.code }}</p>
                                </div>
                                <div v-if="purchaseOrder.vendor?.email">
                                    <p class="text-gray-500">Email</p>
                                    <a :href="`mailto:${purchaseOrder.vendor.email}`" class="text-blue-600 hover:underline">
                                        {{ purchaseOrder.vendor.email }}
                                    </a>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Items -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-list text-blue-600"></i>
                                Order Items ({{ purchaseOrder.items?.length || 0 }})
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="purchaseOrder.items" stripedRows>
                                <Column field="item_name" header="Item" />
                                <Column field="unit" header="Unit" style="width:80px" />
                                <Column field="quantity" header="Ordered" style="width:100px">
                                    <template #body="{ data }">{{ parseFloat(data.quantity) }}</template>
                                </Column>
                                <Column field="received_quantity" header="Received" style="width:100px">
                                    <template #body="{ data }">
                                        <span :class="parseFloat(data.received_quantity) > 0 ? 'text-green-600 font-medium' : 'text-gray-400'">
                                            {{ parseFloat(data.received_quantity) }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="unit_price" header="Unit Price">
                                    <template #body="{ data }">{{ formatCurrency(data.unit_price) }}</template>
                                </Column>
                                <Column header="Total">
                                    <template #body="{ data }">
                                        <span class="font-semibold">{{ formatCurrency(data.total_price) }}</span>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <!-- Terms & Notes -->
                    <Card v-if="purchaseOrder.terms_conditions || purchaseOrder.notes">
                        <template #title>Terms & Notes</template>
                        <template #content>
                            <div class="space-y-4 text-sm">
                                <div v-if="purchaseOrder.terms_conditions">
                                    <p class="font-medium text-gray-700 mb-1">Terms & Conditions</p>
                                    <p class="text-gray-600 whitespace-pre-line bg-gray-50 p-3 rounded">{{ purchaseOrder.terms_conditions }}</p>
                                </div>
                                <div v-if="purchaseOrder.notes">
                                    <p class="font-medium text-gray-700 mb-1">Notes</p>
                                    <p class="text-gray-600 whitespace-pre-line">{{ purchaseOrder.notes }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right: Summary + Activity -->
                <div class="space-y-6">
                    <Card>
                        <template #title>Financial Summary</template>
                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span>{{ formatCurrency(purchaseOrder.subtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">VAT ({{ purchaseOrder.vat_percentage }}%)</span>
                                    <span>{{ formatCurrency(purchaseOrder.vat_amount) }}</span>
                                </div>
                                <div class="flex justify-between" v-if="parseFloat(purchaseOrder.freight_charges) > 0">
                                    <span class="text-gray-600">Freight</span>
                                    <span>{{ formatCurrency(purchaseOrder.freight_charges) }}</span>
                                </div>
                                <div class="flex justify-between text-green-600" v-if="parseFloat(purchaseOrder.discount_amount) > 0">
                                    <span>Discount</span>
                                    <span>- {{ formatCurrency(purchaseOrder.discount_amount) }}</span>
                                </div>
                                <Divider />
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Grand Total</span>
                                    <span class="text-blue-700">{{ formatCurrency(purchaseOrder.total_amount) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Activity</template>
                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created by</span>
                                    <span class="font-medium">{{ purchaseOrder.creator?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created on</span>
                                    <span>{{ formatDateTime(purchaseOrder.created_at) }}</span>
                                </div>
                                <template v-if="purchaseOrder.sent_at">
                                    <Divider />
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Sent by</span>
                                        <span class="font-medium">{{ purchaseOrder.sender?.name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Sent on</span>
                                        <span>{{ formatDateTime(purchaseOrder.sent_at) }}</span>
                                    </div>
                                </template>
                                <template v-if="purchaseOrder.updater">
                                    <Divider />
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Last updated by</span>
                                        <span class="font-medium">{{ purchaseOrder.updater?.name }}</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>

<style>
@media print {
    .p-toast, .p-confirmdialog, button, nav { display: none !important; }
}
</style>