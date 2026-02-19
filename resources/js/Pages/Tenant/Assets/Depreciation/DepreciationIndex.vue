<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import { TrendingDown, Play, BarChart3, Calendar } from 'lucide-vue-next';

const props = defineProps({
    runs: Object,
    filters: Object,
});

const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const fmt = (v) => Number(v).toLocaleString('en-BD', { minimumFractionDigits: 2 });

const yearFilter = ref(props.filters?.year || null);
const years = Array.from({ length: 6 }, (_, i) => ({ label: String(new Date().getFullYear() - i), value: new Date().getFullYear() - i }));

const applyFilter = () => router.get(route('tenant.assets.depreciation.index'), { year: yearFilter.value }, { preserveState: true });
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Depreciation', url: route('tenant.assets.depreciation.index') }]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <TrendingDown class="w-7 h-7 text-blue-600" />
                        Asset Depreciation
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Monthly depreciation runs (SLM & WDV)</p>
                </div>
                <Link :href="route('tenant.assets.depreciation.run')">
                    <Button label="Run Depreciation" icon="pi pi-play" severity="primary" />
                </Link>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-3">
                <Select v-model="yearFilter" :options="years" optionLabel="label" optionValue="value"
                    placeholder="All Years" showClear class="w-40" @change="applyFilter" />
                <span class="text-sm text-gray-500">{{ runs.total }} runs total</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="runs.data" class="p-datatable-sm" stripedRows>
                    <template #empty>
                        <div class="text-center py-16 text-gray-400">
                            <TrendingDown class="w-12 h-12 mx-auto mb-3 opacity-30" />
                            <p class="font-medium">No depreciation runs yet</p>
                            <p class="text-sm mt-1">Click "Run Depreciation" to process your first run</p>
                        </div>
                    </template>

                    <Column header="Period" class="font-medium">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-gray-400" />
                                <span>{{ monthNames[data.month] }} {{ data.year }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Assets Processed" field="assets_processed" class="text-center">
                        <template #body="{ data }">
                            <Tag :value="String(data.assets_processed)" severity="info" />
                        </template>
                    </Column>

                    <Column header="Total Depreciation (BDT)">
                        <template #body="{ data }">
                            <span class="font-semibold text-red-600">{{ fmt(data.total_depreciation) }}</span>
                        </template>
                    </Column>

                    <Column header="Processed Date">
                        <template #body="{ data }">
                            <span class="text-gray-600 text-sm">
                                {{ data.processed_date ? new Date(data.processed_date).toLocaleDateString('en-BD') : '-' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" class="text-center">
                        <template #body="{ data }">
                            <!-- We'd link to a detail/schedule breakdown page per period -->
                            <span class="text-xs text-gray-400">Processed</span>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="runs.last_page > 1" class="flex justify-center py-4 border-t">
                    <div class="flex gap-2">
                        <Link v-for="page in runs.last_page" :key="page"
                            :href="runs.links[page]?.url || '#'"
                            class="px-3 py-1 text-sm rounded border"
                            :class="page === runs.current_page ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 hover:bg-gray-50'">
                            {{ page }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>