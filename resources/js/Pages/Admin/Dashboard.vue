<template>
    <AdminLayout page-title="Dashboard">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <Card v-for="stat in stats" :key="stat.label">
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 mb-2">{{ stat.label }}</p>
                        <p class="text-3xl font-bold text-primary-600">{{ stat.value }}</p>
                    </div>
                </template>
            </Card>
        </div>
        
        <Card>
            <template #title>Recent Tenants</template>
            <template #content>
                <DataTable :value="tenants" responsiveLayout="scroll">
                    <Column field="name" header="School Name"></Column>
                    <Column field="subdomain" header="Subdomain"></Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status" 
                                 :severity="getStatusSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column field="created_at" header="Created"></Column>
                </DataTable>
            </template>
        </Card>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const stats = ref([
    { label: 'Total Tenants', value: '12' },
    { label: 'MRR', value: '$2,400' },
    { label: 'New This Month', value: '3' },
    { label: 'Active Trials', value: '5' },
]);

const tenants = ref([
    { name: 'Green Valley School', subdomain: 'greenvalley', status: 'Active', created_at: '2024-01-15' },
    { name: 'Sunshine Academy', subdomain: 'sunshine', status: 'Trial', created_at: '2024-02-01' },
]);

const getStatusSeverity = (status) => {
    return status === 'Active' ? 'success' : 'warning';
};
</script>