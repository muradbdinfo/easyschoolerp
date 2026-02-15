<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <aside :class="['bg-white border-r border-gray-200 transition-all duration-300', 
                        sidebarCollapsed ? 'w-16' : 'w-64']">
            <div class="p-4">
                <div class="flex items-center justify-between mb-8">
                    <h1 v-if="!sidebarCollapsed" class="text-xl font-bold text-primary-600">
                        {{ schoolName }}
                    </h1>
                    <button @click="toggleSidebar" class="text-gray-400 hover:text-gray-600">
                        <Menu :size="24" v-if="sidebarCollapsed" />
                        <X :size="24" v-else />
                    </button>
                </div>
                
                <PrimeMenu :model="menuItems" class="border-0">
                    <template #item="{ item }">
                        <Link :href="item.route" 
                              :class="['flex items-center p-3 rounded transition-colors',
                                       isActive(item.route) ? 'bg-primary-50 text-primary-600' : 'hover:bg-gray-50']">
                            <component :is="item.icon" :size="20" />
                            <span v-if="!sidebarCollapsed" class="ml-3">{{ item.label }}</span>
                            <Badge v-if="item.badge && !sidebarCollapsed" 
                                   :value="item.badge" 
                                   severity="danger" 
                                   class="ml-auto" />
                        </Link>
                    </template>
                </PrimeMenu>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Navbar -->
            <nav class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6">
                <div class="flex items-center space-x-4">
                    <Breadcrumb :model="breadcrumbItems" />
                </div>
                
                <div class="flex items-center space-x-4">
                    <Dropdown v-model="selectedBranch" 
                             :options="branches" 
                             optionLabel="name" 
                             placeholder="Select Branch" 
                             class="w-48" />
                    
                    <div class="relative">
                        <Bell :size="20" 
                              class="text-gray-600 cursor-pointer" 
                              @click="toggleNotifications" />
                        <Badge v-if="unreadCount > 0" 
                               :value="unreadCount" 
                               severity="danger" 
                               class="absolute -top-2 -right-2" />
                    </div>
                    
                    <Avatar :label="userInitials" shape="circle" />
                </div>
            </nav>
            
            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-auto">
                <slot />
            </main>
        </div>
        
        <!-- Notification Panel -->
        <OverlayPanel ref="notificationPanel">
            <div class="w-80">
                <h3 class="font-semibold mb-4">Notifications</h3>
                <!-- Notifications will go here -->
            </div>
        </OverlayPanel>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PrimeMenu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import OverlayPanel from 'primevue/overlaypanel';
import { Menu, X, Home, ShoppingCart, Box, BarChart3, Settings, Bell } from 'lucide-vue-next';

defineProps({
    schoolName: String,
    breadcrumbItems: Array
});

const page = usePage();
const sidebarCollapsed = ref(false);
const selectedBranch = ref(null);
const unreadCount = ref(5);
const notificationPanel = ref();

const branches = ref([
    { name: 'All Branches', code: 'all' },
    { name: 'Junior Branch', code: 'junior' },
    { name: 'Senior Branch', code: 'senior' },
]);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleNotifications = (event) => {
    notificationPanel.value.toggle(event);
};

const userInitials = computed(() => {
    // Calculate from user name
    return 'JD';
});

const isActive = (route) => {
    return page.url.startsWith(route);
};

const menuItems = ref([
    { label: 'Dashboard', icon: Home, route: '/dashboard', badge: null },
    { label: 'Procurement', icon: ShoppingCart, route: '/procurement', badge: 3 },
    { label: 'Assets', icon: Box, route: '/assets', badge: null },
    { label: 'Reports', icon: BarChart3, route: '/reports', badge: null },
    { label: 'Settings', icon: Settings, route: '/settings', badge: null },
]);
</script>