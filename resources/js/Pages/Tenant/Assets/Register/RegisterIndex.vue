<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Panel from 'primevue/panel';
import {
    Box, Plus, Search, Filter, Eye, Edit, Trash2,
    Building, User, TrendingDown, Wrench, MoveRight
} from 'lucide-vue-next';

const props = defineProps({
    assets:     Object,
    stats:      Object,
    categories: Array,
    branches:   Array,
    filters:    Object,
});

// ── Filters ────────────────────────────────────────────────
const search      = ref(props.filters.search ?? '');
const categoryId  = ref(props.filters.category_id ?? null);
const branchId    = ref(props.filters.branch_id ?? null);
const status      = ref(props.filters.status ?? null);

const statusOptions = [
    { label: 'Active',            value: 'active' },
    { label: 'Under Maintenance', value: 'under_maintenance' },
    { label: 'Disposed',          value: 'disposed' },
    { label: 'Lost',              value: 'lost' },
    { label: 'Damaged',           value: 'damaged' },
    { label: 'Written Off',       value: 'written_off' },
];

function applyFilters() {
    router.get(route('tenant.assets.index'), {
        search:      search.value || undefined,
        category_id: categoryId.value || undefined,
        branch_id:   branchId.value || undefined,
        status:      status.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = ''; categoryId.value = null;
    branchId.value = null; status.value = null;
    applyFilters();
}

// Pagination
function goToPage(p) { router.get(route('tenant.assets.index'), { ...props.filters, page: p }); }

const formatCurrency = (v) => '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 0 });
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Register' }]">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asset Register</h1>
                    <p class="text-sm text-gray-500 mt-0.5">All registered assets</p>
                </div>
                <Link :href="route('tenant.assets.create')">
                    <Button severity="primary">
                        <Plus :size="16" class="mr-1.5" />
                        Register Asset
                    </Button>
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Total Assets</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">In Maintenance</p>
                    <p class="text-2xl font-bold text-amber-500 mt-1">{{ stats.maintenance }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Total Book Value</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.total_value) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <Panel :collapsed="false" toggleable>
                <template #header><div class="flex items-center gap-2"><Filter :size="16" /><span class="font-medium">Filters</span></div></template>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="relative">
                        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <InputText v-model="search" placeholder="Search tag, name..." class="pl-9 w-full" @keydown.enter="applyFilters" />
                    </div>
                    <Select v-model="categoryId" :options="categories" optionLabel="name" optionValue="id"
                        placeholder="All Categories" class="w-full" showClear />
                    <Select v-model="branchId" :options="branches" optionLabel="name" optionValue="id"
                        placeholder="All Branches" class="w-full" showClear />
                    <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value"
                        placeholder="All Statuses" class="w-full" showClear />
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <Button @click="clearFilters"  severity="secondary" label="Clear" outlined size="small" />
                    <Button @click="applyFilters"  severity="primary"   label="Apply" size="small" />
                </div>
            </Panel>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="assets.data" class="p-datatable-sm" stripedRows>
                    <Column header="Asset" style="min-width: 220px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    <img v-if="data.primary_photo_url" :src="data.primary_photo_url" class="w-full h-full object-cover" />
                                    <Box v-else :size="18" class="text-gray-400" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ data.name }}</p>
                                    <p class="text-xs font-mono text-blue-600">{{ data.asset_tag }}</p>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Category" style="width: 140px">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-600">{{ data.category?.name ?? '—' }}</span>
                        </template>
                    </Column>

                    <Column header="Location" style="width: 150px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                <Building :size="13" class="text-gray-400" />
                                <span>{{ data.branch?.name ?? '—' }}</span>
                            </div>
                            <p v-if="data.room" class="text-xs text-gray-400 mt-0.5 pl-4">{{ data.room }}</p>
                        </template>
                    </Column>

                    <Column header="Custodian" style="width: 140px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                <User :size="13" class="text-gray-400" />
                                <span>{{ data.custodian?.name ?? '—' }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Book Value" style="width: 130px">
                        <template #body="{ data }">
                            <span class="font-semibold text-sm text-gray-900">{{ formatCurrency(data.net_book_value) }}</span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 130px">
                        <template #body="{ data }">
                            <Tag :severity="data.status_badge.severity" :value="data.status_badge.label" />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 120px">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('tenant.assets.show', data.id)">
                                    <Button severity="secondary" size="small" text rounded title="View">
                                        <Eye :size="14" />
                                    </Button>
                                </Link>
                                <Link :href="route('tenant.assets.edit', data.id)">
                                    <Button severity="secondary" size="small" text rounded title="Edit">
                                        <Edit :size="14" />
                                    </Button>
                                </Link>
                                <Link :href="route('tenant.assets.transfers.create', { asset_id: data.id })">
                                    <Button severity="info" size="small" text rounded title="Transfer">
                                        <MoveRight :size="14" />
                                    </Button>
                                </Link>
                                <Link :href="route('tenant.assets.maintenance.create', { asset_id: data.id })">
                                    <Button severity="warning" size="small" text rounded title="Maintenance">
                                        <Wrench :size="14" />
                                    </Button>
                                </Link>
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-12 text-gray-500">
                            <Box :size="40" class="mx-auto text-gray-300 mb-3" />
                            <p class="font-medium">No assets found</p>
                            <p class="text-sm mt-1">Register your first asset to get started</p>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div v-if="assets.last_page > 1" class="flex justify-between items-center px-4 py-3 border-t border-gray-100">
                    <span class="text-sm text-gray-500">
                        Showing {{ assets.from }}–{{ assets.to }} of {{ assets.total }}
                    </span>
                    <div class="flex gap-1">
                        <Button v-for="p in assets.last_page" :key="p"
                            :severity="p === assets.current_page ? 'primary' : 'secondary'"
                            size="small" :label="String(p)" @click="goToPage(p)" text />
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>