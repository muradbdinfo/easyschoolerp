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
import Rating from 'primevue/rating';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Panel from 'primevue/panel';
import Paginator from 'primevue/paginator';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    vendors: Object,
    filters: Object,
    stats: Object,
});

const toast = useToast();
const confirm = useConfirm();

const loading = ref(false);
const selectedVendors = ref([]);
const showFilters = ref(false);

const localFilters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || null,
    status: props.filters?.status || null,
    rating_min: props.filters?.rating_min || null,
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
    { label: 'Supplier', value: 'supplier' },
    { label: 'Contractor', value: 'contractor' },
    { label: 'Service Provider', value: 'service_provider' },
];

const statusOptions = [
    { label: 'All Status', value: null },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Blacklisted', value: 'blacklisted' },
];

const ratingOptions = [
    { label: 'All Ratings', value: null },
    { label: '4+ Stars', value: 4 },
    { label: '3+ Stars', value: 3 },
    { label: '2+ Stars', value: 2 },
];

const applyFilters = () => {
    router.get(route('tenant.vendors.index'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        search: '',
        type: null,
        status: null,
        rating_min: null,
    };
    applyFilters();
};

const createVendor = () => {
    router.visit(route('tenant.vendors.create'));
};

const viewVendor = (vendor) => {
    router.visit(route('tenant.vendors.show', vendor.id));
};

const editVendor = (vendor) => {
    router.visit(route('tenant.vendors.edit', vendor.id));
};

const deleteVendor = (vendor) => {
    confirm.require({
        message: `Are you sure you want to delete vendor "${vendor.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.vendors.destroy', vendor.id), {
                preserveScroll: true,
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

const blacklistVendor = (vendor) => {
    const reason = prompt('Please provide a reason for blacklisting:');
    if (reason) {
        router.post(route('tenant.vendors.blacklist', vendor.id), {
            reason: reason
        }, {
            preserveScroll: true,
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
};

const activateVendor = (vendor) => {
    confirm.require({
        message: `Activate vendor "${vendor.name}"?`,
        header: 'Activate Vendor',
        icon: 'pi pi-check-circle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('tenant.vendors.activate', vendor.id), {}, {
                preserveScroll: true,
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

const exportVendors = () => {
    window.open(route('tenant.vendors.export', localFilters.value), '_blank');
};
</script>

<template>
    <TenantLayout title="Vendors">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Vendors</h1>
                        <p class="text-gray-600 mt-1">Manage your vendors and suppliers</p>
                    </div>
                    <div class="flex gap-2">
                        <Button 
                            icon="pi pi-download" 
                            label="Export"
                            severity="secondary"
                            @click="exportVendors"
                            outlined
                        />
                        <Button 
                            icon="pi pi-plus" 
                            label="Add Vendor"
                            @click="createVendor"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Vendors</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                                </div>
                                <i class="pi pi-users text-4xl text-blue-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Active</p>
                                    <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                                </div>
                                <i class="pi pi-check-circle text-4xl text-green-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Blacklisted</p>
                                    <p class="text-2xl font-bold text-red-600">{{ stats.blacklisted }}</p>
                                </div>
                                <i class="pi pi-ban text-4xl text-red-500"></i>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Suppliers</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ stats.suppliers }}</p>
                                </div>
                                <i class="pi pi-shopping-cart text-4xl text-blue-500"></i>
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
                                <InputText 
                                    v-model="localFilters.search" 
                                    placeholder="Search vendors..." 
                                    class="w-full"
                                />
                            </span>
                        </div>

                        <Button 
                            :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'"
                            :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                            severity="secondary"
                            @click="showFilters = !showFilters"
                            outlined
                        />
                    </div>

                    <Panel v-if="showFilters" header="Filters" :toggleable="true" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Type</label>
                                <Dropdown 
                                    v-model="localFilters.type" 
                                    :options="typeOptions" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    placeholder="Select Type"
                                    class="w-full"
                                    @change="applyFilters"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Status</label>
                                <Dropdown 
                                    v-model="localFilters.status" 
                                    :options="statusOptions" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    placeholder="Select Status"
                                    class="w-full"
                                    @change="applyFilters"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Minimum Rating</label>
                                <Dropdown 
                                    v-model="localFilters.rating_min" 
                                    :options="ratingOptions" 
                                    optionLabel="label" 
                                    optionValue="value"
                                    placeholder="Select Rating"
                                    class="w-full"
                                    @change="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button label="Reset" @click="resetFilters" severity="secondary" outlined size="small" />
                        </div>
                    </Panel>

                    <DataTable 
                        :value="vendors.data" 
                        :loading="loading"
                        stripedRows
                        responsiveLayout="scroll"
                        v-model:selection="selectedVendors"
                        dataKey="id"
                    >
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-users text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500 text-lg">No vendors found</p>
                                <p class="text-gray-400 text-sm mt-2">Create your first vendor to get started</p>
                                <Button 
                                    label="Add Vendor" 
                                    icon="pi pi-plus" 
                                    @click="createVendor" 
                                    class="mt-4"
                                    size="small"
                                />
                            </div>
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem" />
                        
                        <Column field="code" header="Code" :sortable="true">
                            <template #body="{ data }">
                                <span class="font-mono text-sm">{{ data.code }}</span>
                            </template>
                        </Column>

                        <Column field="name" header="Name" :sortable="true">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">{{ data.name }}</div>
                                    <div class="text-sm text-gray-500" v-if="data.contact_person">
                                        {{ data.contact_person }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="type" header="Type" :sortable="true">
                            <template #body="{ data }">
                                <Tag :value="data.type_label" severity="info" />
                            </template>
                        </Column>

                        <Column field="phone" header="Contact">
                            <template #body="{ data }">
                                <div class="text-sm">
                                    <div v-if="data.phone">{{ data.phone }}</div>
                                    <div v-if="data.email" class="text-gray-500">{{ data.email }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="rating" header="Rating" :sortable="true">
                            <template #body="{ data }">
                                <Rating :modelValue="data.rating" :readonly="true" :cancel="false" />
                            </template>
                        </Column>

                        <Column field="status" header="Status" :sortable="true">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.status_badge.label" 
                                    :severity="data.status_badge.severity" 
                                />
                            </template>
                        </Column>

                        <Column header="Actions">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button 
                                        icon="pi pi-eye" 
                                        severity="info" 
                                        text 
                                        rounded 
                                        @click="viewVendor(data)"
                                        v-tooltip.top="'View'"
                                        size="small"
                                    />
                                    <Button 
                                        icon="pi pi-pencil" 
                                        severity="success" 
                                        text 
                                        rounded 
                                        @click="editVendor(data)"
                                        v-tooltip.top="'Edit'"
                                        size="small"
                                    />
                                    <Button 
                                        v-if="data.status === 'blacklisted'"
                                        icon="pi pi-check"
                                        severity="success"
                                        text
                                        rounded
                                        @click="activateVendor(data)"
                                        v-tooltip.top="'Activate'"
                                        size="small"
                                    />
                                    <Button 
                                        v-else-if="data.status === 'active'"
                                        icon="pi pi-ban"
                                        severity="warning"
                                        text
                                        rounded
                                        @click="blacklistVendor(data)"
                                        v-tooltip.top="'Blacklist'"
                                        size="small"
                                    />
                                    <Button 
                                        icon="pi pi-trash" 
                                        severity="danger" 
                                        text 
                                        rounded 
                                        @click="deleteVendor(data)"
                                        v-tooltip.top="'Delete'"
                                        size="small"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <div class="mt-4" v-if="vendors.data && vendors.data.length > 0">
                        <Paginator 
                            :rows="vendors.per_page" 
                            :totalRecords="vendors.total"
                            :rowsPerPageOptions="[15, 30, 50, 100]"
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

<style scoped>
.p-input-icon-left > i:first-of-type {
    left: 0.75rem;
}

.p-input-icon-left > .p-inputtext {
    padding-left: 2.5rem;
}
</style>