<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <aside :class="['bg-gray-900 text-white transition-all duration-300', 
                        sidebarCollapsed ? 'w-16' : 'w-64']">
            <div class="p-4">
                <div class="flex items-center justify-between mb-8">
                    <h1 v-if="!sidebarCollapsed" class="text-xl font-bold">Admin Panel</h1>
                    <button @click="toggleSidebar" class="text-gray-400 hover:text-white">
                        <Menu :size="24" v-if="sidebarCollapsed" />
                        <X :size="24" v-else />
                    </button>
                </div>
                
                <Menu :model="menuItems" class="w-full">
                    <template #item="{ item }">
                        <Link :href="item.route" class="flex items-center p-3 hover:bg-gray-800 rounded">
                            <component :is="item.icon" :size="20" />
                            <span v-if="!sidebarCollapsed" class="ml-3">{{ item.label }}</span>
                        </Link>
                    </template>
                </Menu>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Navbar -->
            <nav class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold">{{ pageTitle }}</h2>
                </div>
                
                <div class="flex items-center space-x-4">
                    <Bell :size="20" class="text-gray-600 cursor-pointer" />
                    <Avatar label="AD" shape="circle" />
                </div>
            </nav>
            
            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import { Menu as MenuIcon, X, LayoutDashboard, Building, CreditCard, Package, Bell } from 'lucide-vue-next';

defineProps({
    pageTitle: String
});

const sidebarCollapsed = ref(false);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const menuItems = ref([
    { label: 'Dashboard', icon: LayoutDashboard, route: '/admin/dashboard' },
    { label: 'Tenants', icon: Building, route: '/admin/tenants' },
    { label: 'Subscriptions', icon: CreditCard, route: '/admin/subscriptions' },
    { label: 'Modules', icon: Package, route: '/admin/modules' },
]);
</script>