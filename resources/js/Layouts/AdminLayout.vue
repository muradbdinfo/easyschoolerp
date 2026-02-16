<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Toast Component -->
        <Toast position="top-right" />
        
        <!-- Sidebar -->
        <aside :class="['bg-gray-900 text-white transition-all duration-300', 
                        sidebarCollapsed ? 'w-20' : 'w-64']">
            <div class="flex flex-col h-full">
                <!-- Logo & Toggle -->
                <div class="p-4 border-b border-gray-800">
                    <div class="flex items-center justify-between">
                        <div v-if="!sidebarCollapsed" class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                                <Building :size="24" />
                            </div>
                            <div>
                                <h1 class="text-lg font-bold">ERP Admin</h1>
                                <p class="text-xs text-gray-400">Super Admin</p>
                            </div>
                        </div>
                        <button @click="toggleSidebar" class="text-gray-400 hover:text-white transition">
                            <Menu :size="24" v-if="sidebarCollapsed" />
                            <X :size="24" v-else />
                        </button>
                    </div>
                </div>
                
                <!-- Navigation Menu -->
                <nav class="flex-1 p-4 overflow-y-auto">
                    <div class="space-y-1">
                        <Link
                            v-for="item in menuItems"
                            :key="item.route"
                            :href="item.route"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all',
                                isActive(item.route) 
                                    ? 'bg-primary-600 text-white' 
                                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                            ]"
                        >
                            <component :is="item.icon" :size="20" class="flex-shrink-0" />
                            <span v-if="!sidebarCollapsed" class="flex-1">{{ item.label }}</span>
                            <Badge 
                                v-if="item.badge && !sidebarCollapsed" 
                                :value="item.badge" 
                                severity="danger" 
                                class="ml-auto"
                            />
                        </Link>
                    </div>
                </nav>

                <!-- User Section -->
                <div class="p-4 border-t border-gray-800">
                    <div v-if="!sidebarCollapsed" class="flex items-center gap-3">
                        <Avatar 
                            :label="userInitials" 
                            shape="circle" 
                            class="bg-primary-600"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ userName }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ userEmail }}</p>
                        </div>
                        <button 
                            @click="logout"
                            class="text-gray-400 hover:text-white transition"
                            title="Logout"
                        >
                            <LogOut :size="18" />
                        </button>
                    </div>
                    <div v-else class="flex justify-center">
                        <Avatar 
                            :label="userInitials" 
                            shape="circle" 
                            size="small"
                            class="bg-primary-600"
                        />
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <h2 class="text-xl font-semibold text-gray-900">{{ pageTitle }}</h2>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Search (Optional) -->
                    <div class="relative hidden md:block">
                        <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input 
                            type="text" 
                            placeholder="Search..." 
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Notifications -->
                    <NotificationBell />
                    
                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button 
                            @click="toggleProfileMenu"
                            class="flex items-center gap-2 hover:bg-gray-50 rounded-lg px-3 py-2 transition"
                        >
                            <Avatar 
                                :label="userInitials" 
                                shape="circle" 
                                size="normal"
                                class="bg-primary-600"
                            />
                            <ChevronDown :size="16" class="text-gray-400" />
                        </button>
                        
                        <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-auto bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Menu from 'primevue/menu';
import NotificationBell from '@/Components/NotificationBell.vue';
import { 
    Menu as MenuIcon, 
    X, 
    LayoutDashboard, 
    Building, 
    CreditCard, 
    Package, 
    Users,
    Settings,
    BarChart3,
    LogOut,
    User,
    Search,
    ChevronDown
} from 'lucide-vue-next';

defineProps({
    pageTitle: {
        type: String,
        default: 'Dashboard'
    }
});

const page = usePage();
const sidebarCollapsed = ref(false);
const profileMenu = ref();

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleProfileMenu = (event) => {
    profileMenu.value.toggle(event);
};

const isActive = (route) => {
    return page.url.startsWith(route);
};

const userName = computed(() => page.props.auth?.user?.name || 'Admin User');
const userEmail = computed(() => page.props.auth?.user?.email || 'admin@example.com');
const userInitials = computed(() => {
    const name = userName.value;
    const names = name.split(' ');
    return names.length >= 2 
        ? names[0][0] + names[names.length - 1][0]
        : name.substring(0, 2).toUpperCase();
});

const menuItems = ref([
    { 
        label: 'Dashboard', 
        icon: LayoutDashboard, 
        route: '/admin/dashboard',
        badge: null 
    },
    { 
        label: 'Tenants', 
        icon: Building, 
        route: '/admin/tenants',
        badge: null 
    },
    { 
        label: 'Subscriptions', 
        icon: CreditCard, 
        route: '/admin/subscriptions',
        badge: null 
    },
    { 
        label: 'Payments', 
        icon: CreditCard, 
        route: '/admin/payments',
        badge: null 
    },
    { 
        label: 'Modules', 
        icon: Package, 
        route: '/admin/modules',
        badge: null 
    },
    { 
        label: 'Users', 
        icon: Users, 
        route: '/admin/users',
        badge: null 
    },
    { 
        label: 'Analytics', 
        icon: BarChart3, 
        route: '/admin/analytics',
        badge: null 
    },
    { 
        label: 'Settings', 
        icon: Settings, 
        route: '/admin/settings',
        badge: null 
    },
]);

const profileMenuItems = ref([
    {
        label: 'My Profile',
        icon: 'pi pi-user',
        command: () => {
            router.visit('/admin/profile');
        }
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => {
            router.visit('/admin/settings');
        }
    },
    {
        separator: true
    },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: logout
    }
]);

function logout() {
    router.post('/logout');
}
</script>