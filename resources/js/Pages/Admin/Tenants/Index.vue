<template>
    <AdminLayout page-title="Tenant Management">
        <div class="space-y-6">
            <!-- Header with Actions -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tenants</h1>
                    <p class="text-gray-600 mt-1">Manage all school tenants</p>
                </div>
                <Button 
                    label="Create Tenant" 
                    icon="pi pi-plus"
                    @click="createTenant"
                />
            </div>

            <!-- Filters Card -->
            <Card>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <IconField iconPosition="left">
                                <InputIcon>
                                    <Search :size="16" />
                                </InputIcon>
                                <InputText 
                                    v-model="searchForm.search"
                                    placeholder="Search by name or subdomain..."
                                    class="w-full"
                                    @input="debouncedSearch"
                                />
                            </IconField>
                        </div>
                        <Dropdown 
                            v-model="searchForm.status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="All Status"
                            class="w-full"
                            showClear
                            @change="search"
                        />
                        <Dropdown 
                            v-model="searchForm.plan"
                            :options="planOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="All Plans"
                            class="w-full"
                            showClear
                            @change="search"
                        />
                    </div>
                </template>
            </Card>

            <!-- Tenants Table -->
            <Card>
                <template #content>
                    <DataTable 
                        :value="tenants.data" 
                        responsiveLayout="scroll"
                        stripedRows
                        :loading="loading"
                    >
                        <Column field="name" header="School" style="min-width: 250px">
                            <template #body="slotProps">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ slotProps.data.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ slotProps.data.subdomain }}.easyschool.local
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" style="min-width: 120px">
                            <template #body="slotProps">
                                <Tag 
                                    :value="slotProps.data.status" 
                                    :severity="getStatusSeverity(slotProps.data.status)"
                                />
                            </template>
                        </Column>

                        <Column field="plan" header="Plan" style="min-width: 120px">
                            <template #body="slotProps">
                                <span class="capitalize">{{ slotProps.data.plan }}</span>
                            </template>
                        </Column>

                        <Column field="active_modules" header="Modules" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="flex gap-1">
                                    <Chip 
                                        v-for="module in slotProps.data.active_modules" 
                                        :key="module"
                                        :label="module"
                                        class="text-xs"
                                    />
                                </div>
                            </template>
                        </Column>

                        <Column field="users_count" header="Users" style="min-width: 80px">
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.users_count || 0" />
                            </template>
                        </Column>

                        <Column field="mrr" header="MRR" style="min-width: 100px">
                            <template #body="slotProps">
                                <span class="font-semibold">${{ slotProps.data.mrr }}</span>
                            </template>
                        </Column>

                        <Column field="created_at" header="Created" style="min-width: 120px">
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.created_at) }}
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="flex gap-2">
                                    <Button 
                                        icon="pi pi-eye" 
                                        text 
                                        rounded 
                                        size="small"
                                        v-tooltip.top="'View Details'"
                                        @click="viewTenant(slotProps.data)"
                                    />
                                    <Button 
                                        icon="pi pi-pencil" 
                                        text 
                                        rounded 
                                        size="small"
                                        severity="secondary"
                                        v-tooltip.top="'Edit'"
                                        @click="editTenant(slotProps.data)"
                                    />
                                    <Button 
                                        v-if="slotProps.data.status === 'active'"
                                        icon="pi pi-pause" 
                                        text 
                                        rounded 
                                        size="small"
                                        severity="warning"
                                        v-tooltip.top="'Suspend'"
                                        @click="confirmSuspend(slotProps.data)"
                                    />
                                    <Button 
                                        v-if="slotProps.data.status === 'suspended'"
                                        icon="pi pi-check" 
                                        text 
                                        rounded 
                                        size="small"
                                        severity="success"
                                        v-tooltip.top="'Activate'"
                                        @click="confirmActivate(slotProps.data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <Paginator 
                        v-if="tenants.total > tenants.per_page"
                        :rows="tenants.per_page"
                        :totalRecords="tenants.total"
                        :first="(tenants.current_page - 1) * tenants.per_page"
                        @page="onPage"
                        class="mt-4"
                    />
                </template>
            </Card>
        </div>

        <!-- Tenant Detail Dialog -->
        <Dialog 
            v-model:visible="showDetailDialog" 
            :style="{ width: '600px' }"
            modal
            header="Tenant Details"
        >
            <div v-if="selectedTenant" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-600">School Name</label>
                        <p class="text-gray-900">{{ selectedTenant.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">Subdomain</label>
                        <p class="text-gray-900">{{ selectedTenant.subdomain }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">Status</label>
                        <Tag 
                            :value="selectedTenant.status" 
                            :severity="getStatusSeverity(selectedTenant.status)"
                            class="mt-1"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">Plan</label>
                        <p class="text-gray-900 capitalize">{{ selectedTenant.plan }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">MRR</label>
                        <p class="text-gray-900 font-semibold">${{ selectedTenant.mrr }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">Users</label>
                        <p class="text-gray-900">{{ selectedTenant.users_count || 0 }}</p>
                    </div>
                </div>

                <Divider />

                <div>
                    <label class="text-sm font-semibold text-gray-600">Contact Information</label>
                    <div class="mt-2 space-y-2">
                        <p class="text-gray-900">{{ selectedTenant.contact_name }}</p>
                        <p class="text-gray-600">{{ selectedTenant.contact_email }}</p>
                        <p class="text-gray-600">{{ selectedTenant.contact_phone }}</p>
                    </div>
                </div>

                <Divider />

                <div>
                    <label class="text-sm font-semibold text-gray-600">Active Modules</label>
                    <div class="flex gap-2 mt-2">
                        <Chip 
                            v-for="module in selectedTenant.active_modules" 
                            :key="module"
                            :label="module"
                        />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Confirm Dialog -->
        <ConfirmDialog />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from '@/Composables/useToast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import ConfirmDialog from 'primevue/confirmdialog';
import { Search } from 'lucide-vue-next';
import { debounce } from 'lodash';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const confirm = useConfirm();
const toast = useToast();
const loading = ref(false);
const showDetailDialog = ref(false);
const selectedTenant = ref(null);

const searchForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    plan: props.filters?.plan || null,
});

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Trial', value: 'trial' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Cancelled', value: 'cancelled' },
];

const planOptions = [
    { label: 'Basic', value: 'basic' },
    { label: 'Professional', value: 'professional' },
    { label: 'Enterprise', value: 'enterprise' },
];

// Use direct URLs instead of route() helper
const createTenant = () => {
    router.visit('/admin/tenants/create');
};

const search = () => {
    router.get('/admin/tenants', searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedSearch = debounce(search, 500);

const onPage = (event) => {
    router.get('/admin/tenants', {
        ...searchForm,
        page: event.page + 1,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewTenant = (tenant) => {
    selectedTenant.value = tenant;
    showDetailDialog.value = true;
};

const editTenant = (tenant) => {
    router.visit(`/admin/tenants/${tenant.id}/edit`);
};

const confirmSuspend = (tenant) => {
    confirm.require({
        message: `Are you sure you want to suspend ${tenant.name}?`,
        header: 'Confirm Suspension',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(`/admin/tenants/${tenant.id}/suspend`, {}, {
                onSuccess: () => {
                    toast.success('Tenant suspended successfully');
                },
            });
        },
    });
};

const confirmActivate = (tenant) => {
    confirm.require({
        message: `Are you sure you want to activate ${tenant.name}?`,
        header: 'Confirm Activation',
        icon: 'pi pi-check-circle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(`/admin/tenants/${tenant.id}/activate`, {}, {
                onSuccess: () => {
                    toast.success('Tenant activated successfully');
                },
            });
        },
    });
};

const getStatusSeverity = (status) => {
    const severities = {
        active: 'success',
        trial: 'warning',
        suspended: 'danger',
        cancelled: 'secondary',
    };
    return severities[status] || 'info';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>