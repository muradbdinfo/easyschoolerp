<script setup>
import { ref, computed, watch } from 'vue';
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
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import Image from 'primevue/image';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    items: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const toast = useToast();
const confirm = useConfirm();

const loading = ref(false);
const selectedItems = ref([]);
const showFilters = ref(false);
const viewMode = ref('table');

const localFilters = ref({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || null,
    type: props.filters?.type || null,
    status: props.filters?.status || null,
    stock_status: props.filters?.stock_status || null,
});

let searchTimeout = null;
watch(() => localFilters.value.search, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

const typeOptions = [
    { label: 'All Types', value: null },
    { label: 'Consumable', value: 'consumable' },
    { label: 'Asset', value: 'asset' },
    { label: 'Both', value: 'both' },
];

const statusOptions = [
    { label: 'All Status', value: null },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Discontinued', value: 'discontinued' },
];

const stockStatusOptions = [
    { label: 'All Stock', value: null },
    { label: 'Low Stock', value: 'low' },
    { label: 'Out of Stock', value: 'out' },
];

const applyFilters = () => {
    router.get(route('tenant.items.index'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        search: '',
        category_id: null,
        type: null,
        status: null,
        stock_status: null,
    };
    applyFilters();
};

const createItem = () => {
    router.visit(route('tenant.items.create'));
};

const viewItem = (item) => {
    router.visit(route('tenant.items.show', item.id));
};

const editItem = (item) => {
    router.visit(route('tenant.items.edit', item.id));
};

const deleteItem = (item) => {
    confirm.require({
        message: `Are you sure you want to delete item "${item.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.items.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Item deleted successfully',
                        life: 3000
                    });
                },
            });
        },
    });
};

const exportItems = () => {
    window.open(route('tenant.items.export', localFilters.value), '_blank');
};

const importItems = () => {
    toast.add({
        severity: 'info',
        summary: 'Coming Soon',
        detail: 'Import functionality will be available soon',
        life: 3000
    });
};

const getStockStatusSeverity = (stockStatus) => {
    const severityMap = {
        'Out of Stock': 'danger',
        'Reorder': 'warning',
        'Low Stock': 'warning',
        'Overstock': 'info',
        'In Stock': 'success',
    };
    return severityMap[stockStatus.label] || 'info';
};

const hasFilters = computed(() => {
    return localFilters.value.search || 
           localFilters.value.category_id || 
           localFilters.value.type || 
           localFilters.value.status ||
           localFilters.value.stock_status;
});
</script>

<template>
    <TenantLayout title="Items">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Items</h1>
                        <p class="text-gray-600 mt-1">Manage your inventory items</p>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-upload" label="Import" severity="secondary" @click="importItems" outlined />
                        <Button icon="pi pi-download" label="Export" severity="secondary" @click="exportItems" outlined />
                        <Button icon="pi pi-plus" label="Add Item" @click="createItem" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Total Items</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Active</p>
                                <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Low Stock</p>
                                <p class="text-2xl font-bold text-orange-600">{{ stats.low_stock }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Out of Stock</p>
                                <p class="text-2xl font-bold text-red-600">{{ stats.out_of_stock }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Consumables</p>
                                <p class="text-2xl font-bold text-blue-600">{{ stats.consumables }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card>
                        <template #content>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Assets</p>
                                <p class="text-2xl font-bold text-purple-600">{{ stats.assets }}</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <Card>
                <template #content>
                    <div class="mb-4 flex flex-wrap gap-2 justify-between items-center">
                        <div class="flex gap-2 flex-1 max-w-md">
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-search" />
                                <InputText v-model="localFilters.search" placeholder="Search items..." class="w-full" />
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <SelectButton v-model="viewMode" :options="[
                                { label: 'Table', value: 'table', icon: 'pi pi-list' },
                                { label: 'Cards', value: 'card', icon: 'pi pi-th-large' }
                            ]" optionLabel="label" optionValue="value">
                                <template #option="slotProps">
                                    <i :class="slotProps.option.icon"></i>
                                </template>
                            </SelectButton>

                            <Button :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'" :label="showFilters ? 'Hide Filters' : 'Show Filters'" severity="secondary" @click="showFilters = !showFilters" outlined :badge="hasFilters ? '!' : null" />
                        </div>
                    </div>

                    <Panel v-if="showFilters" header="Filters" :toggleable="true" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Category</label>
                                <Dropdown v-model="localFilters.category_id" :options="[{ id: null, name: 'All Categories' }, ...categories]" optionLabel="name" optionValue="id" placeholder="Select Category" class="w-full" @change="applyFilters" showClear />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Type</label>
                                <Dropdown v-model="localFilters.type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Select Type" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Status</label>
                                <Dropdown v-model="localFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Select Status" class="w-full" @change="applyFilters" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Stock Status</label>
                                <Dropdown v-model="localFilters.stock_status" :options="stockStatusOptions" optionLabel="label" optionValue="value" placeholder="Select Stock Status" class="w-full" @change="applyFilters" />
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Button label="Apply Filters" @click="applyFilters" size="small" />
                            <Button label="Reset" @click="resetFilters" severity="secondary" outlined size="small" />
                        </div>
                    </Panel>

                    <DataTable v-if="viewMode === 'table'" :value="items.data" :loading="loading" stripedRows showGridlines responsiveLayout="scroll" :paginator="false" v-model:selection="selectedItems" dataKey="id" class="p-datatable-sm">
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-box text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500 text-lg">No items found</p>
                                <p class="text-gray-400 text-sm mt-2">Create your first item to get started</p>
                                <Button label="Add Item" icon="pi pi-plus" @click="createItem" class="mt-4" size="small" />
                            </div>
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem" />
                        <Column header="Photo" style="width: 80px">
                            <template #body="{ data }">
                                <Image v-if="data.photo_url" :src="data.photo_url" :alt="data.name" width="50" preview />
                                <div v-else class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="pi pi-box text-gray-400"></i>
                                </div>
                            </template>
                        </Column>
                        <Column field="code" header="Code" :sortable="true" style="min-width: 120px">
                            <template #body="{ data }">
                                <span class="font-mono text-sm">{{ data.code }}</span>
                            </template>
                        </Column>
                        <Column field="name" header="Name" :sortable="true" style="min-width: 200px">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">{{ data.name }}</div>
                                    <div class="text-sm text-gray-500" v-if="data.category">{{ data.category.name }}</div>
                                </div>
                            </template>
                        </Column>
                        <Column field="type" header="Type" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.type_label" severity="info" />
                            </template>
                        </Column>
                        <Column field="unit" header="Unit" :sortable="true" />
                        <Column field="current_price" header="Price" :sortable="true">
                            <template #body="{ data }">
                                {{ data.current_price?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}
                            </template>
                        </Column>
                        <Column field="current_stock" header="Stock" :sortable="true">
                            <template #body="{ data }">
                                <div class="text-right">
                                    <div class="font-medium">{{ data.current_stock }} {{ data.unit }}</div>
                                    <Tag :value="data.stock_status.label" :severity="getStockStatusSeverity(data.stock_status)" class="text-xs mt-1" />
                                </div>
                            </template>
                        </Column>
                        <Column field="status" header="Status" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.status_badge.label" :severity="data.status_badge.severity" />
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 150px" frozen alignFrozen="right">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" severity="info" text rounded @click="viewItem(data)" v-tooltip.top="'View'" />
                                    <Button icon="pi pi-pencil" severity="success" text rounded @click="editItem(data)" v-tooltip.top="'Edit'" />
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteItem(data)" v-tooltip.top="'Delete'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <DataView v-else :value="items.data" :layout="'grid'">
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-box text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500 text-lg">No items found</p>
                            </div>
                        </template>

                        <template #grid="slotProps">
                            <div class="col-12 sm:col-6 lg:col-4 xl:col-3 p-2">
                                <Card class="h-full hover:shadow-lg transition-shadow cursor-pointer" @click="viewItem(slotProps.data)">
                                    <template #header>
                                        <div class="relative">
                                            <Image v-if="slotProps.data.photo_url" :src="slotProps.data.photo_url" :alt="slotProps.data.name" class="w-full h-48 object-cover" />
                                            <div v-else class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                                <i class="pi pi-box text-6xl text-gray-400"></i>
                                            </div>
                                            <Tag :value="slotProps.data.stock_status.label" :severity="getStockStatusSeverity(slotProps.data.stock_status)" class="absolute top-2 right-2" />
                                        </div>
                                    </template>
                                    <template #title>
                                        <div class="text-lg font-semibold truncate">{{ slotProps.data.name }}</div>
                                        <div class="text-sm text-gray-500 font-normal">{{ slotProps.data.code }}</div>
                                    </template>
                                    <template #content>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Category:</span>
                                                <span class="font-medium">{{ slotProps.data.category?.name || 'N/A' }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Stock:</span>
                                                <span class="font-medium">{{ slotProps.data.current_stock }} {{ slotProps.data.unit }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Price:</span>
                                                <span class="font-medium text-green-600">
                                                    {{ slotProps.data.current_price?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <template #footer>
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-eye" severity="info" outlined size="small" class="flex-1" @click.stop="viewItem(slotProps.data)" />
                                            <Button icon="pi pi-pencil" severity="success" outlined size="small" class="flex-1" @click.stop="editItem(slotProps.data)" />
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </template>
                    </DataView>

                    <div class="mt-4" v-if="items.data && items.data.length > 0">
                        <Paginator :rows="items.per_page" :totalRecords="items.total" :rowsPerPageOptions="[15, 30, 50, 100]" template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown" />
                    </div>
                </template>
            </Card>
        </div>

        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>

<style scoped>
.p-input-icon-left > i:first-of-type { left: 0.75rem; }
.p-input-icon-left > .p-inputtext { padding-left: 2.5rem; }
.col-12 { width: 100%; }
@media (min-width: 576px) { .sm\:col-6 { width: 50%; } }
@media (min-width: 992px) { .lg\:col-4 { width: 33.333%; } }
@media (min-width: 1200px) { .xl\:col-3 { width: 25%; } }
</style>