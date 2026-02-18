<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Card from 'primevue/card';
import ConfirmDialog from 'primevue/confirmdialog';
import { TrendingDown, Play, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    assets:     Array,
    month:      Number,
    year:       Number,
    alreadyRun: Boolean,
    summary:    Object,
});

const confirm = useConfirm();

const monthOptions = [
    { label: 'January', value: 1 }, { label: 'February', value: 2 },
    { label: 'March',   value: 3 }, { label: 'April',    value: 4 },
    { label: 'May',     value: 5 }, { label: 'June',     value: 6 },
    { label: 'July',    value: 7 }, { label: 'August',   value: 8 },
    { label: 'September', value: 9 }, { label: 'October', value: 10 },
    { label: 'November',  value: 11 }, { label: 'December', value: 12 },
];
const years = Array.from({ length: 6 }, (_, i) => {
    const y = new Date().getFullYear() - 2 + i;
    return { label: String(y), value: y };
});

const selectedMonth = ref(props.month);
const selectedYear  = ref(props.year);

function preview() {
    window.location.href = route('tenant.assets.depreciation.run') + `?month=${selectedMonth.value}&year=${selectedYear.value}`;
}

const form = useForm({ month: props.month, year: props.year });

function processDepreciation() {
    const monthLabel = monthOptions.find(m => m.value === props.month)?.label;
    confirm.require({
        message: `Process depreciation for ${monthLabel} ${props.year}? This cannot be reversed.`,
        header:  'Confirm Depreciation Run',
        acceptProps: { label: 'Process', severity: 'danger' },
        rejectProps: { label: 'Cancel', severity: 'secondary' },
        accept: () => {
            form.post(route('tenant.assets.depreciation.process'));
        },
    });
}

const formatCurrency = (v) => '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });
const monthName = monthOptions.find(m => m.value === props.month)?.label;
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Depreciation', url: route('tenant.assets.depreciation.index') },
        { label: 'Run' },
    ]">
        <ConfirmDialog />

        <div class="space-y-4 max-w-5xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Run Depreciation</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Preview and process monthly depreciation</p>
                </div>
                <Link :href="route('tenant.assets.depreciation.index')">
                    <Button severity="secondary" outlined label="Back to History" />
                </Link>
            </div>

            <!-- Period Selection -->
            <Card>
                <template #content>
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                            <Select v-model="selectedMonth" :options="monthOptions" optionLabel="label" optionValue="value" class="w-44" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <Select v-model="selectedYear" :options="years" optionLabel="label" optionValue="value" class="w-28" />
                        </div>
                        <Button @click="preview" severity="secondary" label="Preview" outlined />
                    </div>
                </template>
            </Card>

            <!-- Already Run Warning -->
            <div v-if="alreadyRun" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
                <AlertCircle :size="20" class="text-amber-500 flex-shrink-0" />
                <p class="text-amber-700 font-medium">Depreciation for {{ monthName }} {{ year }} has already been processed.</p>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Assets to Process</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.total_assets }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Total Depreciation</p>
                    <p class="text-2xl font-bold text-red-500 mt-1">{{ formatCurrency(summary.total_depreciation) }}</p>
                </div>
            </div>

            <!-- Preview Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                    <TrendingDown :size="16" class="text-orange-500" />
                    <span class="font-semibold text-gray-700">Preview: {{ monthName }} {{ year }}</span>
                </div>
                <DataTable :value="assets" class="p-datatable-sm" :rows="20">
                    <Column field="asset_tag" header="Asset Tag" style="width: 140px">
                        <template #body="{ data }"><span class="font-mono text-xs text-blue-600">{{ data.asset_tag }}</span></template>
                    </Column>
                    <Column field="name" header="Asset Name" />
                    <Column field="category" header="Category" style="width: 140px" />
                    <Column field="depreciation_method" header="Method" style="width: 90px">
                        <template #body="{ data }"><span class="text-xs font-mono">{{ data.depreciation_method }}</span></template>
                    </Column>
                    <Column header="Opening Value" style="width: 140px">
                        <template #body="{ data }"><span class="text-sm">{{ formatCurrency(data.opening_value) }}</span></template>
                    </Column>
                    <Column header="Depreciation" style="width: 130px">
                        <template #body="{ data }"><span class="text-sm text-red-500 font-medium">{{ formatCurrency(data.depreciation_amount) }}</span></template>
                    </Column>
                    <Column header="Closing Value" style="width: 140px">
                        <template #body="{ data }"><span class="text-sm font-semibold text-gray-900">{{ formatCurrency(data.closing_value) }}</span></template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-8 text-gray-500 text-sm">No eligible assets for depreciation.</div>
                    </template>
                </DataTable>
            </div>

            <!-- Process Button -->
            <div class="flex justify-end gap-3" v-if="assets.length > 0 && !alreadyRun">
                <Link :href="route('tenant.assets.depreciation.index')">
                    <Button label="Cancel" severity="secondary" outlined />
                </Link>
                <Button @click="processDepreciation" :loading="form.processing" severity="danger">
                    <Play :size="14" class="mr-1.5" />
                    Process {{ summary.total_assets }} Assets
                </Button>
            </div>
        </div>
    </TenantLayout>
</template>