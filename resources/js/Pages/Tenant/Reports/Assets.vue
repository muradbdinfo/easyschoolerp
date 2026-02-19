<template>
    <TenantLayout :breadcrumb-items="breadcrumbs">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <Box :size="26" class="text-primary-600" />
                    Asset Reports
                </h1>
                <p class="text-sm text-gray-500 mt-1">Valuation, maintenance, warranty and depreciation analytics</p>
            </div>
            <div class="flex gap-2">
                <Button label="Export Excel" icon="pi pi-file-excel" severity="success" outlined size="small" @click="exportExcel" />
                <Button label="Print" icon="pi pi-print" severity="secondary" outlined size="small" @click="() => window.print()" />
            </div>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-600">Branch</label>
                        <Dropdown v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id"
                            placeholder="All Branches" showClear class="w-44" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-600">Category</label>
                        <Dropdown v-model="filters.categoryId" :options="categories" optionLabel="name" optionValue="id"
                            placeholder="All Categories" showClear class="w-44" />
                    </div>
                    <Button label="Apply" icon="pi pi-search" @click="applyFilters" />
                    <Button label="Reset" icon="pi pi-times" severity="secondary" outlined @click="resetFilters" />
                </div>
            </template>
        </Card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <Card v-for="s in summaryCards" :key="s.label" :class="['text-center', s.highlight ? 'border border-orange-300' : '']">
                <template #content>
                    <component :is="s.icon" :size="20" :class="['mx-auto mb-1', s.highlight ? 'text-orange-500' : 'text-primary-500']" />
                    <p :class="['text-xl font-bold', s.highlight ? 'text-orange-600' : 'text-gray-800']">{{ s.value }}</p>
                    <p class="text-xs text-gray-500">{{ s.label }}</p>
                </template>
            </Card>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Category Distribution -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2 text-base">
                        <PieChart :size="18" class="text-primary-600" /> Assets by Category
                    </div>
                </template>
                <template #content>
                    <div v-if="categoryDistribution.length" style="height:260px">
                        <Chart type="doughnut" :data="categoryChartData" :options="doughnutOptions" />
                    </div>
                    <EmptyChartState v-else />
                </template>
            </Card>

            <!-- Depreciation Summary Chart -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2 text-base">
                        <TrendingDown :size="18" class="text-primary-600" /> Depreciation by Category
                    </div>
                </template>
                <template #content>
                    <div v-if="depreciationSummary.length" style="height:260px">
                        <Chart type="bar" :data="depreciationChartData" :options="stackedBarOptions" />
                    </div>
                    <EmptyChartState v-else />
                </template>
            </Card>
        </div>

        <!-- Tabs -->
        <Card>
            <template #content>
                <TabView>
                    <!-- Asset Valuation -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <BarChart3 :size="16" /> Valuation
                                <Badge :value="valuation.length" severity="secondary" />
                            </div>
                        </template>

                        <!-- Valuation Totals -->
                        <div class="grid grid-cols-3 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
                            <div class="text-center">
                                <p class="text-xs text-gray-500">Total Cost</p>
                                <p class="text-lg font-bold text-gray-700">৳{{ formatCurrency(summary.total_cost) }}</p>
                            </div>
                            <div class="text-center border-x">
                                <p class="text-xs text-gray-500">Accumulated Depreciation</p>
                                <p class="text-lg font-bold text-red-600">৳{{ formatCurrency(summary.total_depreciation) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-500">Net Book Value</p>
                                <p class="text-lg font-bold text-green-600">৳{{ formatCurrency(summary.total_nbv) }}</p>
                            </div>
                        </div>

                        <DataTable :value="valuation" paginator :rows="15" :rowsPerPageOptions="[10,15,25,50]"
                            dataKey="id" stripedRows removableSort scrollable scrollHeight="400px" class="text-sm"
                            :globalFilterFields="['asset_tag','name','category','branch']">
                            <Column field="asset_tag" header="Asset Tag" sortable frozen style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.asset_tag }}</span>
                                </template>
                            </Column>
                            <Column field="name" header="Asset Name" sortable style="min-width:180px" />
                            <Column field="category" header="Category" sortable style="min-width:140px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="acquisition_cost" header="Cost (৳)" sortable style="min-width:120px;text-align:right">
                                <template #body="{data}">{{ formatCurrency(data.acquisition_cost) }}</template>
                            </Column>
                            <Column field="accumulated_dep" header="Depreciation (৳)" sortable style="min-width:140px;text-align:right">
                                <template #body="{data}">
                                    <span class="text-red-600">{{ formatCurrency(data.accumulated_dep) }}</span>
                                </template>
                            </Column>
                            <Column field="net_book_value" header="NBV (৳)" sortable style="min-width:120px;text-align:right">
                                <template #body="{data}">
                                    <span class="font-semibold text-green-700">{{ formatCurrency(data.net_book_value) }}</span>
                                </template>
                            </Column>
                            <Column field="status" header="Status" style="min-width:120px">
                                <template #body="{data}">
                                    <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'warning'" />
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- Warranty Expiring -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <AlertCircle :size="16" :class="warrantyExpiring.length ? 'text-orange-500' : ''" />
                                Warranty Expiring
                                <Badge v-if="warrantyExpiring.length" :value="warrantyExpiring.length" severity="warning" />
                            </div>
                        </template>
                        <div v-if="!warrantyExpiring.length" class="text-center py-12 text-gray-400">
                            <CheckCircle :size="40" class="mx-auto mb-3 text-green-400" />
                            <p>No warranties expiring in the next 90 days</p>
                        </div>
                        <DataTable v-else :value="warrantyExpiring" dataKey="id" stripedRows removableSort
                            scrollable scrollHeight="400px" class="text-sm">
                            <Column field="asset_tag" header="Asset Tag" sortable style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.asset_tag }}</span>
                                </template>
                            </Column>
                            <Column field="name" header="Asset Name" sortable style="min-width:180px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="custodian" header="Custodian" sortable style="min-width:140px" />
                            <Column field="expiry" header="Expiry Date" sortable style="min-width:130px" />
                            <Column field="days_remaining" header="Days Left" sortable style="min-width:110px">
                                <template #body="{data}">
                                    <span :class="['font-semibold px-2 py-0.5 rounded text-xs',
                                        data.days_remaining <= 15 ? 'bg-red-100 text-red-700' :
                                        data.days_remaining <= 30 ? 'bg-orange-100 text-orange-700' :
                                        'bg-yellow-100 text-yellow-700']">
                                        {{ data.days_remaining }} days
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- Maintenance Due -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <Wrench :size="16" :class="maintenanceDue.length ? 'text-orange-500' : ''" />
                                Maintenance Due
                                <Badge v-if="maintenanceDue.length" :value="maintenanceDue.length" severity="warning" />
                            </div>
                        </template>
                        <div v-if="!maintenanceDue.length" class="text-center py-12 text-gray-400">
                            <CheckCircle :size="40" class="mx-auto mb-3 text-green-400" />
                            <p>No maintenance due in the next 30 days</p>
                        </div>
                        <DataTable v-else :value="maintenanceDue" dataKey="id" stripedRows removableSort
                            scrollable scrollHeight="400px" class="text-sm">
                            <Column field="asset_tag" header="Asset Tag" sortable style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.asset_tag }}</span>
                                </template>
                            </Column>
                            <Column field="asset_name" header="Asset" sortable style="min-width:180px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="type" header="Type" sortable style="min-width:140px">
                                <template #body="{data}">
                                    <span class="capitalize">{{ data.type?.replace('_',' ') }}</span>
                                </template>
                            </Column>
                            <Column field="scheduled_date" header="Scheduled" sortable style="min-width:130px" />
                            <Column field="days_remaining" header="Status" sortable style="min-width:120px">
                                <template #body="{data}">
                                    <Tag v-if="data.days_remaining < 0" value="Overdue" severity="danger" />
                                    <Tag v-else-if="data.days_remaining <= 3" :value="`Due in ${data.days_remaining}d`" severity="warning" />
                                    <Tag v-else :value="`Due in ${data.days_remaining}d`" severity="info" />
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- Depreciation Summary -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <TrendingDown :size="16" /> Depreciation Summary
                            </div>
                        </template>
                        <DataTable :value="depreciationSummary" dataKey="category" stripedRows removableSort class="text-sm">
                            <Column field="category" header="Category" sortable style="min-width:160px" />
                            <Column field="asset_count" header="Assets" sortable style="min-width:90px;text-align:center" />
                            <Column field="total_cost" header="Total Cost (৳)" sortable style="min-width:140px;text-align:right">
                                <template #body="{data}">{{ formatCurrency(data.total_cost) }}</template>
                            </Column>
                            <Column field="total_depreciation" header="Depreciation (৳)" sortable style="min-width:150px;text-align:right">
                                <template #body="{data}">
                                    <span class="text-red-600">{{ formatCurrency(data.total_depreciation) }}</span>
                                </template>
                            </Column>
                            <Column field="total_nbv" header="NBV (৳)" sortable style="min-width:140px;text-align:right">
                                <template #body="{data}">
                                    <span class="font-semibold text-green-700">{{ formatCurrency(data.total_nbv) }}</span>
                                </template>
                            </Column>
                            <Column header="Depreciated %" sortable style="min-width:130px">
                                <template #body="{data}">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-red-400 h-2 rounded-full"
                                                :style="{width: Math.min(100, data.total_cost > 0 ? (data.total_depreciation/data.total_cost*100) : 0) + '%'}" />
                                        </div>
                                        <span class="text-xs">{{ data.total_cost > 0 ? (data.total_depreciation/data.total_cost*100).toFixed(1) : 0 }}%</span>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>
                </TabView>
            </template>
        </Card>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chart from 'primevue/chart';
import { Box, BarChart3, PieChart, TrendingDown, AlertCircle, CheckCircle, Wrench, Shield, Cog } from 'lucide-vue-next';

const EmptyChartState = {
    template: `<div class="flex flex-col items-center justify-center h-40 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
        <p class="mt-2 text-sm">No data available</p>
    </div>`
};

const props = defineProps({
    valuation: Array,
    categoryDistribution: Array,
    warrantyExpiring: Array,
    maintenanceDue: Array,
    depreciationSummary: Array,
    summary: Object,
    branches: Array,
    categories: Array,
    branchId: [Number, String, null],
    categoryId: [Number, String, null],
});

const breadcrumbs = [
    { label: 'Dashboard', route: '/dashboard' },
    { label: 'Reports', route: '/reports/assets' },
    { label: 'Assets' },
];

const filters = ref({ branchId: props.branchId, categoryId: props.categoryId });

const applyFilters = () => {
    router.get(route('reports.assets'), {
        branch_id: filters.value.branchId,
        category_id: filters.value.categoryId,
    }, { preserveState: true });
};

const resetFilters = () => {
    filters.value = { branchId: null, categoryId: null };
    router.get(route('reports.assets'));
};

const summaryCards = computed(() => [
    { label: 'Total Assets',    value: props.summary.total_assets,           icon: Box },
    { label: 'Total Cost',      value: '৳' + formatCurrency(props.summary.total_cost), icon: BarChart3 },
    { label: 'Net Book Value',  value: '৳' + formatCurrency(props.summary.total_nbv),  icon: TrendingDown },
    { label: 'Depreciated',     value: '৳' + formatCurrency(props.summary.total_depreciation), icon: TrendingDown },
    { label: 'Warranty Expiring', value: props.summary.warranty_expiring_soon, icon: Shield, highlight: props.summary.warranty_expiring_soon > 0 },
    { label: 'Maintenance Due',   value: props.summary.maintenance_due,         icon: Wrench, highlight: props.summary.maintenance_due > 0 },
]);

const COLORS = ['#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#f97316'];

const categoryChartData = computed(() => ({
    labels: props.categoryDistribution.map(c => c.category),
    datasets: [{
        data: props.categoryDistribution.map(c => c.count),
        backgroundColor: COLORS,
    }]
}));

const depreciationChartData = computed(() => ({
    labels: props.depreciationSummary.map(d => d.category),
    datasets: [
        {
            label: 'NBV',
            data: props.depreciationSummary.map(d => d.total_nbv),
            backgroundColor: '#22c55e',
            borderRadius: 4,
        },
        {
            label: 'Depreciation',
            data: props.depreciationSummary.map(d => d.total_depreciation),
            backgroundColor: '#ef4444',
            borderRadius: 4,
        }
    ]
}));

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'right' } }
};

const stackedBarOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: {
        x: { stacked: true, ticks: { maxRotation: 30 } },
        y: { stacked: true, ticks: { callback: v => '৳' + (v/1000).toFixed(0) + 'k' } }
    }
};

const formatCurrency = (val) => {
    if (!val) return '0';
    return Number(val).toLocaleString('en-BD');
};

const exportExcel = () => {
    window.location.href = route('reports.assets') + '?export=excel&' + new URLSearchParams({
        branch_id:   filters.value.branchId   || '',
        category_id: filters.value.categoryId || '',
    });
};
</script>

<style scoped>
@media print {
    button, .p-button { display: none !important; }
}
</style>