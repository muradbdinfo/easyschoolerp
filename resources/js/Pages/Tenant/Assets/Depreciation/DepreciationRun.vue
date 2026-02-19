<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import ConfirmDialog from 'primevue/confirmdialog';
import Message from 'primevue/message';
import { TrendingDown, AlertCircle, CheckCircle, Play } from 'lucide-vue-next';

const props = defineProps({
    assets: Array,
    month: Number,
    year: Number,
    alreadyRun: Boolean,
    summary: Object,
});

const toast = useToast();
const confirm = useConfirm();

const months = [
    { label: 'January', value: 1 }, { label: 'February', value: 2 }, { label: 'March', value: 3 },
    { label: 'April', value: 4 }, { label: 'May', value: 5 }, { label: 'June', value: 6 },
    { label: 'July', value: 7 }, { label: 'August', value: 8 }, { label: 'September', value: 9 },
    { label: 'October', value: 10 }, { label: 'November', value: 11 }, { label: 'December', value: 12 },
];
const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i);

const selectedMonth = ref(props.month);
const selectedYear  = ref(props.year);
const processing    = ref(false);

const fmt = (v) => Number(v).toLocaleString('en-BD', { minimumFractionDigits: 2 });

const monthName = computed(() => months.find(m => m.value === selectedMonth.value)?.label);

const preview = () => {
    router.get(route('tenant.assets.depreciation.run'), { month: selectedMonth.value, year: selectedYear.value });
};

const runDepreciation = () => {
    confirm.require({
        message: `Run depreciation for ${monthName.value} ${selectedYear.value}? This will update ${props.summary.total_assets} asset(s).`,
        header: 'Confirm Depreciation Run',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Run Depreciation',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => {
            processing.value = true;
            router.post(route('tenant.assets.depreciation.process'), {
                month: selectedMonth.value,
                year: selectedYear.value,
            }, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Done', detail: 'Depreciation processed successfully', life: 5000 }),
                onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e)[0], life: 5000 }),
                onFinish: () => { processing.value = false; },
            });
        },
    });
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Depreciation', url: route('tenant.assets.depreciation.index') },
        { label: 'Run' },
    ]">
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <TrendingDown class="w-7 h-7 text-blue-600" />
                    Run Depreciation
                </h1>
            </div>

            <!-- Period Selector -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Select Period</h2>
                <div class="flex items-center gap-4 flex-wrap">
                    <Select v-model="selectedMonth" :options="months" optionLabel="label" optionValue="value"
                        placeholder="Month" class="w-44" />
                    <Select v-model="selectedYear" :options="years" placeholder="Year" class="w-32" />
                    <Button label="Preview" icon="pi pi-eye" severity="secondary" outlined @click="preview" />
                </div>
            </div>

            <!-- Already Run Warning -->
            <Message v-if="alreadyRun" severity="warn" :closable="false">
                <template #icon><AlertCircle class="w-5 h-5" /></template>
                Depreciation for {{ monthName }} {{ year }} has already been processed. Running again is not allowed.
            </Message>

            <!-- Summary Cards -->
            <div v-if="assets.length" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-blue-700">{{ summary.total_assets }}</p>
                    <p class="text-sm text-blue-600 mt-1">Assets to Process</p>
                </div>
                <div class="bg-red-50 border border-red-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-red-700">{{ fmt(summary.total_depreciation) }}</p>
                    <p class="text-sm text-red-600 mt-1">Total Depreciation (BDT)</p>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center">
                    <p class="text-lg font-bold text-green-700">{{ monthName }} {{ year }}</p>
                    <p class="text-sm text-green-600 mt-1">Selected Period</p>
                </div>
            </div>

            <!-- Preview Table -->
            <div v-if="assets.length" class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Preview</h2>
                    <Button v-if="!alreadyRun" label="Run Depreciation" icon="pi pi-play"
                        severity="danger" :loading="processing" @click="runDepreciation" />
                </div>

                <DataTable :value="assets" class="p-datatable-sm" stripedRows scrollable scrollHeight="500px">
                    <Column field="asset_tag" header="Asset Tag" frozen style="min-width: 140px">
                        <template #body="{ data }">
                            <span class="font-mono text-sm font-semibold text-blue-700">{{ data.asset_tag }}</span>
                        </template>
                    </Column>
                    <Column field="name" header="Name" style="min-width: 200px" />
                    <Column field="category" header="Category" style="min-width: 150px" />
                    <Column field="depreciation_method" header="Method" class="text-center">
                        <template #body="{ data }">
                            <Tag :value="data.depreciation_method"
                                :severity="data.depreciation_method === 'SLM' ? 'info' : 'warning'" />
                        </template>
                    </Column>
                    <Column header="Opening Value" class="text-right">
                        <template #body="{ data }">{{ fmt(data.opening_value) }}</template>
                    </Column>
                    <Column header="Depreciation" class="text-right">
                        <template #body="{ data }">
                            <span class="text-red-600 font-medium">{{ fmt(data.depreciation_amount) }}</span>
                        </template>
                    </Column>
                    <Column header="Closing Value" class="text-right">
                        <template #body="{ data }">
                            <span class="font-semibold text-gray-800">{{ fmt(data.closing_value) }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center text-gray-400">
                <TrendingDown class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p class="font-medium">No eligible assets found</p>
                <p class="text-sm mt-1">Assets must be active with a depreciation method and rate set</p>
            </div>
        </div>
    </TenantLayout>
</template>