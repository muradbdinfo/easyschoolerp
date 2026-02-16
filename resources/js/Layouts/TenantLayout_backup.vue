<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Toast Component (IMPORTANT!) -->
        <Toast position="top-right" />
        
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
                <p class="text-gray-500 text-center py-8">No notifications yet</p>
            </div>
        </OverlayPanel>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import PrimeMenu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import OverlayPanel from 'primevue/overlaypanel';
import Toast from 'primevue/toast';
import { Menu, X, Home, ShoppingCart, Box, BarChart3, Settings, Bell } from 'lucide-vue-next';

// ============================================
// CONSOLE LOGS FOR TESTING (Remove in production)
// ============================================
console.log('🎯 TenantLayout: Component loaded');

defineProps({
    schoolName: {
        type: String,
        default: 'My School'
    },
    breadcrumbItems: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
console.log('📄 TenantLayout: Page props:', page.props);

const toast = useToast();
console.log('🍞 TenantLayout: Toast service initialized');

const sidebarCollapsed = ref(false);
const selectedBranch = ref(null);
const unreadCount = ref(0);
const notificationPanel = ref();

const branches = ref([
    { name: 'All Branches', code: 'all' },
    { name: 'Junior Branch', code: 'junior' },
    { name: 'Middle Branch', code: 'middle' },
    { name: 'Senior Branch', code: 'senior' },
    { name: 'High Care Branch', code: 'highcare' },
]);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    console.log('📱 Sidebar toggled:', sidebarCollapsed.value ? 'collapsed' : 'expanded');
};

const toggleNotifications = (event) => {
    notificationPanel.value.toggle(event);
    console.log('🔔 Notification panel toggled');
};

const userInitials = computed(() => {
    const user = page.props.auth?.user;
    if (!user) {
        console.log('⚠️ No user data found');
        return 'U';
    }
    
    const names = user.name.split(' ');
    const initials = names.length >= 2 
        ? names[0][0] + names[1][0]
        : user.name.substring(0, 2).toUpperCase();
    
    console.log('👤 User initials calculated:', initials);
    return initials;
});

const isActive = (route) => {
    const active = page.url.startsWith(route);
    if (active) {
        console.log('✅ Active route:', route);
    }
    return active;
};

const menuItems = ref([
    { label: 'Dashboard', icon: Home, route: '/dashboard', badge: null },
    { label: 'Procurement', icon: ShoppingCart, route: '/procurement', badge: null },
    { label: 'Assets', icon: Box, route: '/assets', badge: null },
    { label: 'Reports', icon: BarChart3, route: '/reports', badge: null },
    { label: 'Settings', icon: Settings, route: '/settings', badge: null },
]);
console.log('📋 Menu items loaded:', menuItems.value.length, 'items');

// Watch for flash messages
watch(() => page.props.flash, (flash) => {
    console.log('💬 Flash message received:', flash);
    
    if (flash?.success) {
        console.log('✅ Showing success toast:', flash.success);
        toast.success(flash.success);
    }
    if (flash?.error) {
        console.log('❌ Showing error toast:', flash.error);
        toast.error(flash.error);
    }
    if (flash?.warning) {
        console.log('⚠️ Showing warning toast:', flash.warning);
        toast.warning(flash.warning);
    }
    if (flash?.info) {
        console.log('ℹ️ Showing info toast:', flash.info);
        toast.info(flash.info);
    }
}, { immediate: true, deep: true });

// Component mounted
onMounted(() => {
    console.log('✅ TenantLayout: Component mounted successfully');
    console.log('👤 Current user:', page.props.auth?.user);
    console.log('🌐 Current URL:', page.url);
    console.log('🏫 School name:', page.props.schoolName || 'My School');
});
</script>