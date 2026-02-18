<template>
    <AdminLayout page-title="Dashboard">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card v-for="stat in statsCards" :key="stat.label" class="overflow-hidden">
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">{{ stat.label }}</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stat.value }}</p>
                            <p v-if="stat.change" :class="['text-sm mt-2', stat.change > 0 ? 'text-green-600' : 'text-red-600']">
                                {{ stat.change > 0 ? '+' : '' }}{{ stat.change }}% from last month
                            </p>
                        </div>
                        <div :class="['p-3 rounded-full', stat.bgColor]">
                            <component :is="stat.icon" :size="24" :class="stat.iconColor" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            <Card>
                <template #title>Monthly Revenue</template>
                <template #content>
                    <Chart type="line" :data="revenueChartData" :options="chartOptions" />
                </template>
            </Card>

            <!-- Tenant Status Distribution -->
            <Card>
                <template #title>Tenant Status Distribution</template>
                <template #content>
                    <Chart type="doughnut" :data="statusChartData" :options="doughnutOptions" />
                </template>
            </Card>
        </div>

        <!-- Recent Tenants Table -->
        <Card>
            <template #title>
                <div class="flex justify-between items-center">
                    <span>Recent Tenants</span>
                    <Button 
                        label="View All" 
                        icon="pi pi-arrow-right" 
                        icon-pos="right"
                        text 
                        @click="$inertia.visit('/admin/tenants')"
                    />
                </div>
            </template>
            <template #content>
                <DataTable 
                    :value="recentTenants" 
                    responsiveLayout="scroll"
                    :paginator="false"
                    stripedRows
                >
                    <Column field="name" header="School Name" style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="font-semibold">{{ slotProps.data.name }}</div>
                            <div class="text-sm text-gray-500">{{ slotProps.data.subdomain }}.erp.local</div>
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <Tag 
                                :value="slotProps.data.status" 
                                :severity="getStatusSeverity(slotProps.data.status)" 
                            />
                        </template>
                    </Column>
                    <Column field="plan" header="Plan">
                        <template #body="slotProps">
                            <span class="capitalize">{{ slotProps.data.plan }}</span>
                        </template>
                    </Column>
                    <Column field="mrr" header="MRR">
                        <template #body="slotProps">
                            ${{ slotProps.data.mrr }}
                        </template>
                    </Column>
                    <Column field="user_count" header="Users">
                        <template #body="slotProps">
                            <Badge :value="slotProps.data.user_count" />
                        </template>
                    </Column>
                    <Column field="created_at" header="Created"></Column>
                    <Column header="Actions">
                        <template #body="slotProps">
                            <div class="flex gap-2">
                                <Button icon="pi pi-eye" text rounded size="small" @click="viewTenant(slotProps.data.id)" />
                                <Button icon="pi pi-pencil" text rounded size="small" severity="secondary" />
                                <Button icon="pi pi-ellipsis-v" text rounded size="small" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import { Building, DollarSign, Users, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    recentTenants: Array,
    revenueData: Array,
});

const statsCards = computed(() => [
    {
        label: 'Total Tenants',
        value: props.stats.total_tenants,
        icon: Building,
        bgColor: 'bg-blue-100',
        iconColor: 'text-blue-600',
        change: 12,
    },
    {
        label: 'Monthly Revenue',
        value: `$${props.stats.total_mrr}`,
        icon: DollarSign,
        bgColor: 'bg-green-100',
        iconColor: 'text-green-600',
        change: 8,
    },
    {
        label: 'New This Month',
        value: props.stats.new_this_month,
        icon: TrendingUp,
        bgColor: 'bg-purple-100',
        iconColor: 'text-purple-600',
        change: 15,
    },
    {
        label: 'Active Trials',
        value: props.stats.trial_tenants,
        icon: Users,
        bgColor: 'bg-orange-100',
        iconColor: 'text-orange-600',
        change: -3,
    },
]);

// Revenue Chart Data
const revenueChartData = computed(() => ({
    labels: props.revenueData.map(d => d.month),
    datasets: [
        {
            label: 'Revenue',
            data: props.revenueData.map(d => d.revenue),
            fill: true,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
        }
    ]
}));

// Status Distribution Chart
const statusChartData = computed(() => ({
    labels: ['Active', 'Trial', 'Suspended'],
    datasets: [
        {
            data: [
                props.stats.active_tenants,
                props.stats.trial_tenants,
                props.stats.suspended_tenants,
            ],
            backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const getStatusSeverity = (status) => {
    const severities = {
        active: 'success',
        trial: 'warning',
        suspended: 'danger',
        cancelled: 'secondary',
    };
    return severities[status] || 'info';
};

const viewTenant = (id) => {
    // Navigate to tenant details
    console.log('View tenant:', id);
};
</script>