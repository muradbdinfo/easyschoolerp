<template>
    <TenantLayout :breadcrumb-items="breadcrumbs">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <BarChart3 :size="26" class="text-primary-600" />
                    Procurement Reports
                </h1>
                <p class="text-sm text-gray-500 mt-1">Analytics and registers for procurement activities</p>
            </div>
            <div class="flex gap-2">
                <Button label="Export Excel" icon="pi pi-file-excel" severity="success" outlined size="small" @click="exportExcel" />
                <Button label="Print" icon="pi pi-print" severity="secondary" outlined size="small" @click="printPage" />
            </div>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-600">From Date</label>
                        <Calendar v-model="filters.dateFrom" dateFormat="yy-mm-dd" showIcon class="w-40" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-600">To Date</label>
                        <Calendar v-model="filters.dateTo" dateFormat="yy-mm-dd" showIcon class="w-40" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-600">Branch</label>
                        <Dropdown v-model="filters.branchId" :options="branches" optionLabel="name" optionValue="id"
                            placeholder="All Branches" showClear class="w-44" />
                    </div>
                    <Button label="Apply" icon="pi pi-search" @click="applyFilters" />
                    <Button label="Reset" icon="pi pi-times" severity="secondary" outlined @click="resetFilters" />
                </div>
            </template>
        </Card>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <Card v-for="s in summaryCards" :key="s.label" class="text-center">
                <template #content>
                    <component :is="s.icon" :size="20" class="mx-auto mb-1 text-primary-500" />
                    <p class="text-2xl font-bold text-gray-800">{{ s.value }}</p>
                    <p class="text-xs text-gray-500">{{ s.label }}</p>
                </template>
            </Card>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Monthly Spending Trend -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2 text-base">
                        <TrendingUp :size="18" class="text-primary-600" /> Monthly Spending Trend
                    </div>
                </template>
                <template #content>
                    <div v-if="monthlyTrend.length" style="height:260px">
                        <Chart type="line" :data="monthlyChartData" :options="lineChartOptions" />
                    </div>
                    <EmptyChartState v-else />
                </template>
            </Card>

            <!-- Department-wise Spending -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2 text-base">
                        <PieChart :size="18" class="text-primary-600" /> Department-wise Spending
                    </div>
                </template>
                <template #content>
                    <div v-if="deptSpending.length" style="height:260px">
                        <Chart type="doughnut" :data="deptChartData" :options="doughnutOptions" />
                    </div>
                    <EmptyChartState v-else />
                </template>
            </Card>
        </div>

        <!-- Vendor Spending Bar Chart -->
        <Card class="mb-6">
            <template #title>
                <div class="flex items-center gap-2 text-base">
                    <Users :size="18" class="text-primary-600" /> Top Vendors by Spend
                </div>
            </template>
            <template #content>
                <div v-if="vendorSpending.length" style="height:280px">
                    <Chart type="bar" :data="vendorChartData" :options="barChartOptions" />
                </div>
                <EmptyChartState v-else />
            </template>
        </Card>

        <!-- Tabs for Registers -->
        <Card>
            <template #content>
                <TabView>
                    <!-- PR Register -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <FileText :size="16" /> PR Register
                                <Badge :value="prRegister.length" severity="secondary" />
                            </div>
                        </template>
                        <DataTable :value="prRegister" paginator :rows="15" :rowsPerPageOptions="[10,15,25,50]"
                            dataKey="id" filterDisplay="menu" stripedRows removableSort
                            :globalFilterFields="['pr_number','requester','department','branch']"
                            scrollable scrollHeight="450px" class="text-sm">
                            <template #header>
                                <div class="flex justify-between">
                                    <span class="font-semibold text-gray-700">Purchase Requisitions</span>
                                    <IconField>
                                        <InputIcon class="pi pi-search" />
                                        <InputText v-model="prSearch" placeholder="Search..." size="small" />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="pr_number" header="PR #" sortable frozen style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.pr_number }}</span>
                                </template>
                            </Column>
                            <Column field="pr_date" header="Date" sortable style="min-width:120px" />
                            <Column field="requester" header="Requester" sortable style="min-width:140px" />
                            <Column field="department" header="Department" sortable style="min-width:140px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="total" header="Amount (৳)" sortable style="min-width:130px;text-align:right">
                                <template #body="{data}">
                                    <span class="font-semibold">{{ formatCurrency(data.total) }}</span>
                                </template>
                            </Column>
                            <Column field="status" header="Status" style="min-width:140px">
                                <template #body="{data}">
                                    <Tag :value="data.status_badge?.label" :severity="data.status_badge?.severity" />
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- PO Register -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <Package :size="16" /> PO Register
                                <Badge :value="poRegister.length" severity="secondary" />
                            </div>
                        </template>
                        <DataTable :value="poRegister" paginator :rows="15" :rowsPerPageOptions="[10,15,25,50]"
                            dataKey="id" stripedRows removableSort scrollable scrollHeight="450px" class="text-sm">
                            <Column field="po_number" header="PO #" sortable frozen style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.po_number }}</span>
                                </template>
                            </Column>
                            <Column field="po_date" header="Date" sortable style="min-width:120px" />
                            <Column field="vendor" header="Vendor" sortable style="min-width:160px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="total" header="Amount (৳)" sortable style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-semibold">{{ formatCurrency(data.total) }}</span>
                                </template>
                            </Column>
                            <Column field="status" header="Status" style="min-width:140px">
                                <template #body="{data}">
                                    <Tag :value="data.status_badge?.label" :severity="data.status_badge?.severity" />
                                </template>
                            </Column>
                        </DataTable>
                    </TabPanel>

                    <!-- GRN Register -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <Truck :size="16" /> GRN Register
                                <Badge :value="grnRegister.length" severity="secondary" />
                            </div>
                        </template>
                        <DataTable :value="grnRegister" paginator :rows="15" :rowsPerPageOptions="[10,15,25,50]"
                            dataKey="id" stripedRows removableSort scrollable scrollHeight="450px" class="text-sm">
                            <Column field="grn_number" header="GRN #" sortable frozen style="min-width:130px">
                                <template #body="{data}">
                                    <span class="font-mono font-semibold text-primary-700">{{ data.grn_number }}</span>
                                </template>
                            </Column>
                            <Column field="receipt_date" header="Date" sortable style="min-width:120px" />
                            <Column field="po_number" header="PO #" sortable style="min-width:130px" />
                            <Column field="vendor" header="Vendor" sortable style="min-width:160px" />
                            <Column field="branch" header="Branch" sortable style="min-width:120px" />
                            <Column field="status" header="Status" style="min-width:120px">
                                <template #body="{data}">
                                    <Tag :value="data.status" :severity="data.status === 'passed' ? 'success' : data.status === 'failed' ? 'danger' : 'warning'" />
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
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chart from 'primevue/chart';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { BarChart3, TrendingUp, PieChart, FileText, Package, Truck, Users, ShoppingCart, CheckCircle, Clock } from 'lucide-vue-next';

// Empty chart state component
const EmptyChartState = {
    template: `<div class="flex flex-col items-center justify-center h-40 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
        <p class="mt-2 text-sm">No data for selected period</p>
    </div>`
};

const props = defineProps({
    prRegister: Array,
    poRegister: Array,
    grnRegister: Array,
    vendorSpending: Array,
    deptSpending: Array,
    monthlyTrend: Array,
    summary: Object,
    branches: Array,
    departments: Array,
    dateFrom: String,
    dateTo: String,
    branchId: [Number, String, null],
});

const breadcrumbs = [
    { label: 'Dashboard', route: '/dashboard' },
    { label: 'Reports', route: '/reports/procurement' },
    { label: 'Procurement' },
];

// Filters
const filters = ref({
    dateFrom: props.dateFrom,
    dateTo: props.dateTo,
    branchId: props.branchId,
});
const prSearch = ref('');

const applyFilters = () => {
    router.get(route('reports.procurement'), {
        date_from: filters.value.dateFrom,
        date_to:   filters.value.dateTo,
        branch_id: filters.value.branchId,
    }, { preserveState: true });
};

const resetFilters = () => {
    filters.value = { dateFrom: null, dateTo: null, branchId: null };
    router.get(route('reports.procurement'));
};

// Summary cards
const summaryCards = computed(() => [
    { label: 'Total PRs',      value: props.summary.total_prs,   icon: FileText },
    { label: 'Total POs',      value: props.summary.total_pos,   icon: Package },
    { label: 'Total GRNs',     value: props.summary.total_grns,  icon: Truck },
    { label: 'Pending PRs',    value: props.summary.pending_prs, icon: Clock },
    { label: 'Total Spend',    value: '৳' + formatCurrency(props.summary.total_spend), icon: ShoppingCart },
]);

// Chart colours
const COLORS = ['#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#f97316'];

const monthlyChartData = computed(() => ({
    labels: props.monthlyTrend.map(m => m.month),
    datasets: [{
        label: 'Total Spend (৳)',
        data: props.monthlyTrend.map(m => m.total),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.1)',
        tension: 0.4, fill: true,
        pointBackgroundColor: '#6366f1',
    }]
}));

const deptChartData = computed(() => ({
    labels: props.deptSpending.map(d => d.department),
    datasets: [{
        data: props.deptSpending.map(d => d.total),
        backgroundColor: COLORS,
    }]
}));

const vendorChartData = computed(() => ({
    labels: props.vendorSpending.map(v => v.vendor),
    datasets: [{
        label: 'Spend (৳)',
        data: props.vendorSpending.map(v => v.total),
        backgroundColor: COLORS,
        borderRadius: 6,
    }]
}));

const lineChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { ticks: { callback: v => '৳' + (v/1000).toFixed(0) + 'k' } }
    }
};

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'right' } }
};

const barChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { ticks: { callback: v => '৳' + (v/1000).toFixed(0) + 'k' } },
        x: { ticks: { maxRotation: 30 } }
    }
};

const formatCurrency = (val) => {
    if (!val) return '0';
    return Number(val).toLocaleString('en-BD');
};

const exportExcel = () => {
    window.location.href = route('reports.procurement') + '?export=excel&' + new URLSearchParams({
        date_from: filters.value.dateFrom || '',
        date_to:   filters.value.dateTo   || '',
        branch_id: filters.value.branchId || '',
    });
};

const printPage = () => window.print();
</script>

<style scoped>
@media print {
    button, .p-button { display: none !important; }
}
</style>