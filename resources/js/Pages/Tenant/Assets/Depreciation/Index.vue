<script setup>
// DepreciationIndex.vue — pages for listing runs
import { Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { TrendingDown, Play } from 'lucide-vue-next';

const props = defineProps({ runs: Object, filters: Object });

const monthName = (m) => new Date(2000, m-1).toLocaleString('default', { month: 'long' });
const formatCurrency = (v) => '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Depreciation' }]">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Depreciation</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Monthly depreciation processing history</p>
                </div>
                <Link :href="route('tenant.assets.depreciation.run')">
                    <Button severity="primary"><Play :size="16" class="mr-1.5" /> Run Depreciation</Button>
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="runs.data" class="p-datatable-sm" stripedRows>
                    <Column header="Period" style="width: 150px">
                        <template #body="{ data }">
                            <span class="font-semibold">{{ monthName(data.month) }} {{ data.year }}</span>
                        </template>
                    </Column>
                    <Column field="assets_processed" header="Assets" style="width: 100px">
                        <template #body="{ data }"><span class="font-bold text-blue-600">{{ data.assets_processed }}</span></template>
                    </Column>
                    <Column header="Total Depreciation" style="width: 180px">
                        <template #body="{ data }"><span class="font-semibold text-red-500">{{ formatCurrency(data.total_depreciation) }}</span></template>
                    </Column>
                    <Column field="processed_date" header="Processed On" style="width: 130px">
                        <template #body="{ data }"><span class="text-sm text-gray-600">{{ new Date(data.processed_date).toLocaleDateString('en-BD') }}</span></template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-10 text-gray-500">
                            <TrendingDown :size="36" class="mx-auto text-gray-300 mb-2" />
                            <p>No depreciation runs yet.</p>
                            <Link :href="route('tenant.assets.depreciation.run')" class="text-blue-600 text-sm hover:underline mt-1 inline-block">Run your first depreciation →</Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </TenantLayout>
</template>