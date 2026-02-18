<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Panel from 'primevue/panel';
import Paginator from 'primevue/paginator';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    purchaseOrders: Object,
    filters: Object,
    stats: Object,
    vendors: Array,
    branches: Array,
});

const toast = useToast();
const confirm = useConfirm();
const showFilters = ref(false);

const localFilters = ref({
    search:    props.filters?.search    || '',
    status:    props.filters?.status    || null,
    vendor_id: props.filters?.vendor_id || null,
    branch_id: props.filters?.branch_id || null,
    date_from: props.filters?.date_from || '',
    date_to:   props.filters?.date_to   || '',
});

let debounce = null;
watch(() => localFilters.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(applyFilters, 500);
});

const statusOptions = [
    { label: 'All Status',    value: null },
    { label: 'Draft',         value: 'draft' },
    { label: 'Sent',          value: 'sent' },
    { label: 'Acknowledged',  value: 'acknowledged' },
    { label: 'Partial',       value: 'partial' },
    { label: 'Received',      value: 'received' },
    { label: 'Closed',        value: 'closed' },
    { label: 'Cancelled',     value: 'cancelled' },
];

const applyFilters = () => {
    router.get(route('tenant.purchase-orders.index'), localFilters.value, {
        preserveState: true, preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = { search: '', status: null, vendor_id: null, branch_id: null, date_from: '', date_to: '' };
    applyFilters();
};

const createPO = () => router.visit(route('tenant.purchase-orders.create'));
const viewPO   = (po) => router.visit(route('tenant.purchase-orders.show', po.id));
const editPO   = (po) => router.visit(route('tenant.purchase-orders.edit', po.id));

const sendPO = (po) => {
    confirm.require({
        message: `Send PO ${po.po_number} to ${po.vendor?.name}?`,
        header: 'Send Purchase Order',
        icon: 'pi pi-send',
        acceptClass: 'p-button-info',
        accept: () => {
            router.post(route('tenant.purchase-orders.send', po.id), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Sent', detail: 'PO sent to vendor.', life: 3000 }),
            });
        },
    });
};

const cancelPO = (po) => {
    confirm.require({
        message: `Cancel PO ${po.po_number}? This cannot be undone.`,
        header: 'Cancel Purchase Order',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('tenant.purchase-orders.cancel', po.id), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelled', detail: 'PO cancelled.', life: 3000 }),
            });
        },
    });
};

const formatCurrency = (val) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(val || 0);
</script>

<template>
    <TenantLayout title="Purchase Orders">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Purchase Orders</h1>
                    <p class="text-gray-600 mt-1">Manage purchase orders sent to vendors</p>
                </div>
                <Button icon="pi pi-plus" label="New PO" @click="createPO" />
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <Card v-for="(val, key) in stats" :key="key">
                    <template #content>
                        <p class="text-sm text-gray-500 capitalize">{{ key }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ val }}</p>
                    </template>
                </Card>
            </div>

            <!-- Table Card -->
            <Card>
                <template #content>
                    <!-- Search + Filter Toggle -->
                    <div class="mb-4 flex gap-2 justify-between flex-wrap">
                        <span class="p-input-icon-left flex-1 max-w-sm">
                            <i class="pi pi-search" />
                            <InputText v-model="localFilters.search" placeholder="Search PO#, vendor…" class="w-full" />
                        </span>
                        <Button
                            :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'"
                            :label="showFilters ? 'Hide Filters' : 'Filters'"
                            severity="secondary" outlined
                            @click="showFilters = !showFilters"
                        />
                    </div>

                    <!-- Filters Panel -->
                    <Panel v-if="showFilters" header="Filters" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Status</label>
                                <Dropdown v-model="localFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Status" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Vendor</label>
                                <Dropdown v-model="localFilters.vendor_id" :options="[{id:null,name:'All Vendors'},...vendors]" optionLabel="name" optionValue="id" placeholder="All Vendors" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Branch</label>
                                <Dropdown v-model="localFilters.branch_id" :options="[{id:null,name:'All Branches'},...branches]" optionLabel="name" optionValue="id" placeholder="All Branches" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Date From</label>
                                <InputText v-model="localFilters.date_from" type="date" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Date To</label>
                                <InputText v-model="localFilters.date_to" type="date" class="w-full" @change="applyFilters" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <Button label="Reset" severity="secondary" outlined size="small" @click="resetFilters" />
                        </div>
                    </Panel>

                    <!-- DataTable -->
                    <DataTable :value="purchaseOrders.data" stripedRows responsiveLayout="scroll" dataKey="id">
                        <template #empty>
                            <div class="text-center py-10">
                                <i class="pi pi-box text-6xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No purchase orders found</p>
                                <Button label="Create First PO" icon="pi pi-plus" @click="createPO" class="mt-3" size="small" />
                            </div>
                        </template>

                        <Column field="po_number" header="PO Number" :sortable="true">
                            <template #body="{ data }">
                                <span class="font-mono font-medium text-blue-700 cursor-pointer hover:underline" @click="viewPO(data)">
                                    {{ data.po_number }}
                                </span>
                            </template>
                        </Column>

                        <Column field="po_date" header="Date" :sortable="true">
                            <template #body="{ data }">
                                {{ new Date(data.po_date).toLocaleDateString() }}
                            </template>
                        </Column>

                        <Column field="vendor.name" header="Vendor">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">{{ data.vendor?.name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ data.vendor?.code }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="branch.name" header="Branch" />

                        <Column field="items" header="Items">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">{{ data.items?.length || 0 }} item(s)</span>
                            </template>
                        </Column>

                        <Column field="total_amount" header="Total" :sortable="true">
                            <template #body="{ data }">
                                <span class="font-semibold">{{ formatCurrency(data.total_amount) }}</span>
                            </template>
                        </Column>

                        <Column field="expected_delivery_date" header="Expected Delivery">
                            <template #body="{ data }">
                                <span v-if="data.expected_delivery_date">
                                    {{ new Date(data.expected_delivery_date).toLocaleDateString() }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>

                        <Column field="status" header="Status" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.status_badge.label" :severity="data.status_badge.severity" />
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 10rem">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="viewPO(data)" v-tooltip.top="'View'" />
                                    <Button v-if="data.status === 'draft'" icon="pi pi-pencil" severity="success" text rounded size="small" @click="editPO(data)" v-tooltip.top="'Edit'" />
                                    <Button v-if="data.status === 'draft'" icon="pi pi-send" severity="info" text rounded size="small" @click="sendPO(data)" v-tooltip.top="'Send to Vendor'" />
                                    <Button v-if="!['received','closed','cancelled'].includes(data.status)" icon="pi pi-times-circle" severity="danger" text rounded size="small" @click="cancelPO(data)" v-tooltip.top="'Cancel'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Paginator -->
                    <div class="mt-4" v-if="purchaseOrders.total > 0">
                        <Paginator
                            :rows="purchaseOrders.per_page"
                            :totalRecords="purchaseOrders.total"
                            :rowsPerPageOptions="[15, 30, 50]"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        />
                    </div>
                </template>
            </Card>
        </div>

        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>