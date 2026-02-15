<template>
    <TenantLayout school-name="My School" :breadcrumb-items="breadcrumbs">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <Card v-for="stat in stats" :key="stat.label">
                <template #content>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">{{ stat.label }}</p>
                        <p class="text-2xl font-bold">{{ stat.value }}</p>
                    </div>
                </template>
            </Card>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
                <template #title>Recent Requisitions</template>
                <template #content>
                    <p class="text-gray-500">No requisitions yet</p>
                </template>
            </Card>
            
            <Card>
                <template #title>Assets Overview</template>
                <template #content>
                    <p class="text-gray-500">No assets registered</p>
                </template>
            </Card>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';

const breadcrumbs = ref([
    { label: 'Home', route: '/dashboard' }
]);

const stats = ref([
    { label: 'Pending Approvals', value: '0' },
    { label: 'My Requisitions', value: '0' },
    { label: 'My Assets', value: '0' },
    { label: 'Maintenance Due', value: '0' },
]);
</script>