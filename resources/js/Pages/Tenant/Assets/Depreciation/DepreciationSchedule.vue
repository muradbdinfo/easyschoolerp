<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { TrendingDown } from 'lucide-vue-next';

const props = defineProps({
    asset: Object,
    schedule: Array,
});

const fmt = (v) => Number(v).toLocaleString('en-BD', { minimumFractionDigits: 2 });

const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const totalDepreciated = props.schedule.reduce((s, r) => s + parseFloat(r.depreciation_amount), 0);
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Depreciation', url: route('tenant.assets.depreciation.index') },
        { label: asset.asset_tag },
    ]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <TrendingDown class="w-7 h-7 text-blue-600" />
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ asset.name }}</h1>
                    <p class="text-sm text-gray-500">{{ asset.asset_tag }} · Depreciation Schedule</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                    <p class="text-xl font-bold text-gray-800">{{ fmt(asset.acquisition_cost) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Acquisition Cost</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                    <p class="text-xl font-bold text-red-600">{{ fmt(asset.accumulated_depreciation) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Accumulated Depreciation</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                    <p class="text-xl font-bold text-green-600">{{ fmt(asset.net_book_value) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Net Book Value</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                    <Tag :value="(asset.depreciation_method || 'none').toUpperCase()"
                        :severity="asset.depreciation_method === 'slm' ? 'info' : 'warning'" />
                    <p class="text-xs text-gray-500 mt-2">Method · {{ asset.depreciation_rate }}% / yr</p>
                </div>
            </div>

            <!-- Schedule Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b">
                    <h2 class="font-semibold text-gray-800">Monthly Depreciation Log</h2>
                </div>

                <DataTable :value="schedule" class="p-datatable-sm" stripedRows>
                    <template #empty>
                        <div class="text-center py-12 text-gray-400">
                            <p>No depreciation runs for this asset yet.</p>
                        </div>
                    </template>

                    <Column header="Period">
                        <template #body="{ data }">
                            {{ monthNames[data.month] }} {{ data.year }}
                        </template>
                    </Column>
                    <Column header="Opening Value (BDT)" class="text-right">
                        <template #body="{ data }">{{ fmt(data.opening_value) }}</template>
                    </Column>
                    <Column header="Depreciation (BDT)" class="text-right">
                        <template #body="{ data }">
                            <span class="text-red-600 font-medium">{{ fmt(data.depreciation_amount) }}</span>
                        </template>
                    </Column>
                    <Column header="Closing Value (BDT)" class="text-right">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ fmt(data.closing_value) }}</span>
                        </template>
                    </Column>
                    <Column header="Processed Date">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-500">
                                {{ new Date(data.processed_date).toLocaleDateString('en-BD') }}
                            </span>
                        </template>
                    </Column>

                    <template #footer>
                        <tr>
                            <td class="font-semibold px-4 py-2">Total</td>
                            <td></td>
                            <td class="text-right font-bold text-red-600 px-4 py-2">{{ fmt(totalDepreciated) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </template>
                </DataTable>
            </div>
        </div>
    </TenantLayout>
</template>