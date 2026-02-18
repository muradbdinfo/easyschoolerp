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
import Panel from 'primevue/panel';
import Paginator from 'primevue/paginator';
import Toast from 'primevue/toast';

const props = defineProps({
    grns: Object,
    filters: Object,
    stats: Object,
    branches: Array,
});

const showFilters = ref(false);
const f = ref({
    search:    props.filters?.search    || '',
    status:    props.filters?.status    || null,
    branch_id: props.filters?.branch_id || null,
    date_from: props.filters?.date_from || '',
    date_to:   props.filters?.date_to   || '',
});

let debounce = null;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 500);
});

const statusOptions = [
    { label: 'All Status', value: null },
    { label: 'Passed',  value: 'passed' },
    { label: 'Partial', value: 'partial' },
    { label: 'Failed',  value: 'failed' },
];

const apply  = () => router.get(route('tenant.grn.index'), f.value, { preserveState: true, preserveScroll: true });
const reset  = () => { f.value = { search:'', status:null, branch_id:null, date_from:'', date_to:'' }; apply(); };
const viewGRN = (grn) => router.visit(route('tenant.grn.show', grn.id));

const statColors = { total: 'text-blue-700', passed: 'text-green-600', partial: 'text-orange-500', failed: 'text-red-600' };
</script>

<template>
    <TenantLayout title="Goods Receipt Notes">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Goods Receipt Notes</h1>
                    <p class="text-gray-600 mt-1">Track received goods against purchase orders</p>
                </div>
                <Button icon="pi pi-plus" label="Receive Goods" @click="router.visit(route('tenant.grn.create'))" />
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <Card v-for="(val, key) in stats" :key="key">
                    <template #content>
                        <p class="text-sm text-gray-500 capitalize">{{ key }}</p>
                        <p class="text-2xl font-bold" :class="statColors[key]">{{ val }}</p>
                    </template>
                </Card>
            </div>

            <Card>
                <template #content>
                    <div class="flex gap-2 justify-between mb-4 flex-wrap">
                        <span class="p-input-icon-left flex-1 max-w-sm">
                            <i class="pi pi-search" />
                            <InputText v-model="f.search" placeholder="Search GRN#, vendor, invoice…" class="w-full" />
                        </span>
                        <Button :icon="showFilters ? 'pi pi-times':'pi pi-filter'"
                            :label="showFilters ? 'Hide':'Filters'"
                            severity="secondary" outlined @click="showFilters = !showFilters" />
                    </div>

                    <Panel v-if="showFilters" header="Filters" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Status</label>
                                <Dropdown v-model="f.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" @change="apply" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Branch</label>
                                <Dropdown v-model="f.branch_id" :options="[{id:null,name:'All'},...branches]" optionLabel="name" optionValue="id" class="w-full" @change="apply" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Date From</label>
                                <InputText v-model="f.date_from" type="date" class="w-full" @change="apply" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Date To</label>
                                <InputText v-model="f.date_to" type="date" class="w-full" @change="apply" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <Button label="Reset" severity="secondary" outlined size="small" @click="reset" />
                        </div>
                    </Panel>

                    <DataTable :value="grns.data" stripedRows responsiveLayout="scroll" dataKey="id">
                        <template #empty>
                            <div class="text-center py-10">
                                <i class="pi pi-truck text-6xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No GRNs found</p>
                                <Button label="Receive Goods" icon="pi pi-plus" size="small" class="mt-3"
                                    @click="router.visit(route('tenant.grn.create'))" />
                            </div>
                        </template>

                        <Column field="grn_number" header="GRN #" :sortable="true">
                            <template #body="{ data }">
                                <span class="font-mono font-medium text-blue-700 cursor-pointer hover:underline" @click="viewGRN(data)">
                                    {{ data.grn_number }}
                                </span>
                            </template>
                        </Column>
                        <Column field="receipt_date" header="Date" :sortable="true">
                            <template #body="{ data }">{{ new Date(data.receipt_date).toLocaleDateString() }}</template>
                        </Column>
                        <Column field="purchaseOrder.po_number" header="PO #">
                            <template #body="{ data }">
                                <span class="font-mono text-sm">{{ data.purchase_order?.po_number }}</span>
                            </template>
                        </Column>
                        <Column field="vendor.name" header="Vendor">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">{{ data.vendor?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ data.vendor?.code }}</div>
                                </div>
                            </template>
                        </Column>
                        <Column field="branch.name" header="Branch" />
                        <Column field="supplier_invoice_no" header="Invoice #">
                            <template #body="{ data }">
                                <span class="text-sm">{{ data.supplier_invoice_no || '—' }}</span>
                            </template>
                        </Column>
                        <Column field="overall_status" header="Status" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.status_badge.label" :severity="data.status_badge.severity" />
                            </template>
                        </Column>
                        <Column header="Actions" style="width:80px">
                            <template #body="{ data }">
                                <Button icon="pi pi-eye" severity="info" text rounded size="small"
                                    @click="viewGRN(data)" v-tooltip.top="'View'" />
                            </template>
                        </Column>
                    </DataTable>

                    <div class="mt-4" v-if="grns.total > 0">
                        <Paginator :rows="grns.per_page" :totalRecords="grns.total"
                            :rowsPerPageOptions="[15,30,50]"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown" />
                    </div>
                </template>
            </Card>
        </div>
        <Toast />
    </TenantLayout>
</template>