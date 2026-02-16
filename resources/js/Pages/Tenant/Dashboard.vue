<template>
    <TenantLayout school-name="My School" :breadcrumb-items="breadcrumbs">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card v-for="stat in stats" :key="stat.label" class="hover:shadow-lg transition-shadow">
                <template #content>
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-2">{{ stat.label }}</p>
                        <p class="text-3xl font-bold text-primary-600">{{ stat.value }}</p>
                        <p v-if="stat.subtext" class="text-xs text-gray-500 mt-1">{{ stat.subtext }}</p>
                    </div>
                </template>
            </Card>
        </div>
        
        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Requisitions -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <FileText :size="20" class="text-primary-600" />
                        <span>Recent Requisitions</span>
                    </div>
                </template>
                <template #content>
                    <div class="text-center py-12">
                        <div class="inline-block p-6 bg-gray-100 rounded-full mb-4">
                            <FileText :size="48" class="text-gray-400" />
                        </div>
                        <p class="text-gray-500 mb-4">No requisitions yet</p>
                        <Button 
                            label="Create First Requisition" 
                            icon="pi pi-plus" 
                            @click="handleCreateRequisition"
                        />
                    </div>
                </template>
            </Card>
            
            <!-- Assets Overview -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <Box :size="20" class="text-primary-600" />
                        <span>Assets Overview</span>
                    </div>
                </template>
                <template #content>
                    <div class="text-center py-12">
                        <div class="inline-block p-6 bg-gray-100 rounded-full mb-4">
                            <Box :size="48" class="text-gray-400" />
                        </div>
                        <p class="text-gray-500 mb-4">No assets registered</p>
                        <Button 
                            label="Register First Asset" 
                            icon="pi pi-plus" 
                            @click="handleRegisterAsset"
                        />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card 
                    v-for="action in quickActions" 
                    :key="action.label"
                    class="cursor-pointer hover:shadow-lg transition-all hover:scale-105"
                    @click="handleQuickAction(action)"
                >
                    <template #content>
                        <div class="text-center py-4">
                            <component :is="action.icon" :size="32" class="mx-auto mb-2 text-primary-600" />
                            <p class="text-sm font-medium">{{ action.label }}</p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import { FileText, Box, ShoppingCart, Package, Truck, BarChart3 } from 'lucide-vue-next';

// ============================================
// CONSOLE LOGS FOR TESTING (Remove in production)
// ============================================
console.log('🏠 Dashboard: Page component loaded');

const toast = useToast();

const breadcrumbs = ref([
    { label: 'Home', route: '/dashboard' }
]);
console.log('🍞 Dashboard: Breadcrumbs initialized:', breadcrumbs.value);

const stats = ref([
    { 
        label: 'Pending Approvals', 
        value: '0',
        subtext: 'Waiting for action'
    },
    { 
        label: 'My Requisitions', 
        value: '0',
        subtext: 'All time'
    },
    { 
        label: 'My Assets', 
        value: '0',
        subtext: 'Under custody'
    },
    { 
        label: 'Maintenance Due', 
        value: '0',
        subtext: 'This week'
    },
]);
console.log('📊 Dashboard: Stats initialized:', stats.value);

const quickActions = ref([
    { label: 'New Requisition', icon: FileText, action: 'create-pr' },
    { label: 'Register Asset', icon: Box, action: 'register-asset' },
    { label: 'Receive Goods', icon: Truck, action: 'receive-goods' },
    { label: 'View Reports', icon: BarChart3, action: 'view-reports' },
]);
console.log('⚡ Dashboard: Quick actions loaded:', quickActions.value.length, 'actions');

// Handlers
const handleCreateRequisition = () => {
    console.log('📝 Action: Create Requisition clicked');
    toast.info('Coming in Week 4: Create Purchase Requisition');
};

const handleRegisterAsset = () => {
    console.log('📦 Action: Register Asset clicked');
    toast.info('Coming in Week 8: Register Asset');
};

const handleQuickAction = (action) => {
    console.log('⚡ Quick action clicked:', action.action);
    
    const messages = {
        'create-pr': 'Coming in Week 4: Purchase Requisitions',
        'register-asset': 'Coming in Week 8: Asset Management',
        'receive-goods': 'Coming in Week 7: Goods Receipt',
        'view-reports': 'Coming in Week 11: Reports Module'
    };
    
    toast.info(messages[action.action] || 'Feature coming soon!');
};

// Component mounted
onMounted(() => {
    console.log('✅ Dashboard: Page mounted successfully');
    console.log('📍 Current location: /dashboard');
    
    // Simulate loading data (for testing)
    console.log('🔄 Dashboard: Loading user data...');
    
    // You can fetch real data here later
    // For now, just log that we're ready
    setTimeout(() => {
        console.log('✅ Dashboard: Data loaded (simulated)');
    }, 500);
});
</script>

<style scoped>
/* Add smooth transitions */
.card {
    transition: all 0.3s ease;
}

/* Improve hover effects */
.hover\:shadow-lg:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\:scale-105:hover {
    transform: scale(1.05);
}
</style>