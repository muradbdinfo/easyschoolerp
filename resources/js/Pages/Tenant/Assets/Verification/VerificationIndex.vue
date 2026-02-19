<script setup>
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { CheckSquare, Plus } from 'lucide-vue-next';

const props = defineProps({
    cycles: Object,
    filters: Object,
});
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Physical Verification', url: route('tenant.assets.verification.index') },
    ]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <CheckSquare class="w-7 h-7 text-blue-600" />
                        Physical Verification
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage asset verification cycles</p>
                </div>
                <Link :href="route('tenant.assets.verification.create')">
                    <Button label="New Cycle" icon="pi pi-plus" severity="primary" />
                </Link>
            </div>

            <!-- Cycles Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="cycles.data" class="p-datatable-sm" stripedRows>
                    <template #empty>
                        <div class="text-center py-16 text-gray-400">
                            <CheckSquare class="w-12 h-12 mx-auto mb-3 opacity-30" />
                            <p class="font-medium">No verification cycles yet</p>
                            <Link :href="route('tenant.assets.verification.create')" class="text-blue-600 text-sm mt-1 inline-block">
                                Create your first cycle →
                            </Link>
                        </div>
                    </template>

                    <Column field="name" header="Cycle Name" style="min-width: 200px">
                        <template #body="{ data }">
                            <Link :href="route('tenant.assets.verification.show', data.id)"
                                class="font-semibold text-blue-700 hover:underline">
                                {{ data.name }}
                            </Link>
                            <p class="text-xs text-gray-400">{{ data.cycle_year }}</p>
                        </template>
                    </Column>

                    <Column header="Duration" style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-600">{{ data.start_date }} – {{ data.end_date }}</span>
                        </template>
                    </Column>

                    <Column header="Progress" style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs text-gray-600">
                                    <span>{{ data.verified_count }} / {{ data.total_assets }}</span>
                                    <span>{{ data.progress_percent }}%</span>
                                </div>
                                <ProgressBar :value="data.progress_percent" style="height: 6px" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Discrepancies" class="text-center">
                        <template #body="{ data }">
                            <Tag v-if="data.discrepancy_count > 0"
                                :value="String(data.discrepancy_count)"
                                severity="danger" />
                            <span v-else class="text-green-600 text-sm">None</span>
                        </template>
                    </Column>

                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.status_badge.label" :severity="data.status_badge.severity" />
                        </template>
                    </Column>

                    <Column header="Actions" class="text-center">
                        <template #body="{ data }">
                            <Link :href="route('tenant.assets.verification.show', data.id)">
                                <Button icon="pi pi-eye" severity="secondary" text size="small" />
                            </Link>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </TenantLayout>
</template>