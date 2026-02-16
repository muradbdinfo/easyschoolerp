<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Toast Component -->
        <Toast position="top-right" />
        
        <!-- Sidebar -->
        <aside :class="['bg-white border-r border-gray-200 transition-all duration-300 shadow-sm', 
                        sidebarCollapsed ? 'w-20' : 'w-64']">
            <div class="flex flex-col h-full">
                <!-- School Logo & Toggle -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div v-if="!sidebarCollapsed" class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                {{ schoolInitials }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h1 class="text-sm font-bold text-gray-900 truncate">{{ schoolName }}</h1>
                                <p class="text-xs text-gray-500 truncate">{{ schoolSubdomain }}</p>
                            </div>
                        </div>
                        <button 
                            @click="toggleSidebar" 
                            class="text-gray-400 hover:text-gray-600 transition"
                        >
                            <MenuIcon :size="20" v-if="sidebarCollapsed" />
                            <X :size="20" v-else />
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
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group',
                                isActive(item.route) 
                                    ? 'bg-primary-50 text-primary-700 font-medium' 
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                            ]"
                        >
                            <component 
                                :is="item.icon" 
                                :size="20" 
                                class="flex-shrink-0"
                                :class="isActive(item.route) ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-600'"
                            />
                            <span v-if="!sidebarCollapsed" class="flex-1">{{ item.label }}</span>
                            <Badge 
                                v-if="item.badge && !sidebarCollapsed" 
                                :value="item.badge" 
                                severity="danger" 
                            />
                        </Link>
                    </div>

                    <!-- Module Sections -->
                    <div v-if="!sidebarCollapsed" class="mt-6">
                        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                            Modules
                        </p>
                        
                        <!-- Procurement Module -->
                        <div class="space-y-1 mb-4">
                            <button 
                                @click="toggleProcurement"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                            >
                                <ShoppingCart :size="20" class="text-gray-400" />
                                <span class="flex-1 text-left">Procurement</span>
                                <ChevronDown 
                                    :size="16" 
                                    class="text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': procurementExpanded }"
                                />
                            </button>
                            
                            <div v-show="procurementExpanded" class="ml-8 space-y-1">
                                <Link
                                    v-for="subItem in procurementItems"
                                    :key="subItem.route"
                                    :href="subItem.route"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                        isActive(subItem.route)
                                            ? 'bg-primary-50 text-primary-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    ]"
                                >
                                    <component :is="subItem.icon" :size="16" />
                                    <span>{{ subItem.label }}</span>
                                    <Badge 
                                        v-if="subItem.badge" 
                                        :value="subItem.badge" 
                                        severity="danger" 
                                        class="ml-auto"
                                    />
                                </Link>
                            </div>
                        </div>

                        <!-- Assets Module -->
                        <div class="space-y-1">
                            <button 
                                @click="toggleAssets"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                            >
                                <Box :size="20" class="text-gray-400" />
                                <span class="flex-1 text-left">Assets</span>
                                <ChevronDown 
                                    :size="16" 
                                    class="text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': assetsExpanded }"
                                />
                            </button>
                            
                            <div v-show="assetsExpanded" class="ml-8 space-y-1">
                                <Link
                                    v-for="subItem in assetsItems"
                                    :key="subItem.route"
                                    :href="subItem.route"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                        isActive(subItem.route)
                                            ? 'bg-primary-50 text-primary-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    ]"
                                >
                                    <component :is="subItem.icon" :size="16" />
                                    <span>{{ subItem.label }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- User Section -->
                <div class="p-4 border-t border-gray-200">
                    <div v-if="!sidebarCollapsed">
                        <div class="flex items-center gap-3">
                            <Avatar 
                                :label="userInitials" 
                                shape="circle" 
                                class="bg-primary-600"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ userName }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ userRole }}</p>
                            </div>
                            <button 
                                @click="logout"
                                class="text-gray-400 hover:text-gray-600 transition"
                                title="Logout"
                            >
                                <LogOut :size="18" />
                            </button>
                        </div>
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
                    <Breadcrumb :model="breadcrumbItems" class="text-sm">
                        <template #item="{ item }">
                            <Link v-if="item.route" :href="item.route" class="text-primary-600 hover:text-primary-700">
                                {{ item.label }}
                            </Link>
                            <span v-else class="text-gray-500">{{ item.label }}</span>
                        </template>
                    </Breadcrumb>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Branch Selector -->
                    <Dropdown 
                        v-model="selectedBranch" 
                        :options="branches" 
                        optionLabel="name" 
                        placeholder="All Branches"
                        class="w-48"
                        @change="handleBranchChange"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <Building :size="16" />
                                <span>{{ slotProps.value.name }}</span>
                            </div>
                            <span v-else class="flex items-center gap-2">
                                <Building :size="16" />
                                Select Branch
                            </span>
                        </template>
                    </Dropdown>

                    <!-- Quick Actions -->
                    <Button 
                        icon="pi pi-plus" 
                        label="New PR"
                        size="small"
                        @click="router.visit('/procurement/requisitions/create')"
                    />

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
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-gray-900">{{ userName }}</p>
                                <p class="text-xs text-gray-500">{{ userRole }}</p>
                            </div>
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
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import NotificationBell from '@/Components/NotificationBell.vue';
import { 
    Menu as MenuIcon, 
    X, 
    Home,
    ShoppingCart,
    Box,
    BarChart3,
    Settings,
    HelpCircle,
    Users,
    Tag,
    FileText,
    Package,
    Truck,
    Boxes,
    Folder,
    MoveRight,
    Wrench,
    TrendingDown,
    CheckSquare,
    LogOut,
    Building,
    ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
    schoolName: {
        type: String,
        default: 'My School'
    },
    schoolSubdomain: {
        type: String,
        default: 'myschool'
    },
    breadcrumbItems: {
        type: Array,
        default: () => [{ label: 'Dashboard', route: '/dashboard' }]
    }
});

const page = usePage();
const sidebarCollapsed = ref(false);
const procurementExpanded = ref(true);
const assetsExpanded = ref(true);
const selectedBranch = ref(null);
const profileMenu = ref();

const branches = ref([
    { name: 'All Branches', code: 'all' },
    { name: 'Junior Branch', code: 'junior' },
    { name: 'Middle Branch', code: 'middle' },
    { name: 'Senior Branch', code: 'senior' },
]);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleProcurement = () => {
    procurementExpanded.value = !procurementExpanded.value;
};

const toggleAssets = () => {
    assetsExpanded.value = !assetsExpanded.value;
};

const toggleProfileMenu = (event) => {
    profileMenu.value.toggle(event);
};

const handleBranchChange = () => {
    console.log('Branch changed:', selectedBranch.value);
};

const isActive = (route) => {
    return page.url.startsWith(route);
};

const userName = computed(() => page.props.auth?.user?.name || 'User');
const userRole = computed(() => {
    const role = page.props.auth?.user?.role || 'staff';
    return role.charAt(0).toUpperCase() + role.slice(1);
});
const userInitials = computed(() => {
    const name = userName.value;
    const names = name.split(' ');
    return names.length >= 2 
        ? names[0][0] + names[names.length - 1][0]
        : name.substring(0, 2).toUpperCase();
});

const schoolInitials = computed(() => {
    const name = props.schoolName;
    const words = name.split(' ');
    return words.length >= 2 
        ? words[0][0] + words[1][0]
        : name.substring(0, 2).toUpperCase();
});

const menuItems = ref([
    { label: 'Dashboard', icon: Home, route: '/dashboard', badge: null },
    { label: 'Reports', icon: BarChart3, route: '/reports', badge: null },
    { label: 'Settings', icon: Settings, route: '/settings', badge: null },
    { label: 'Help', icon: HelpCircle, route: '/help', badge: null },
]);

const procurementItems = ref([
    { label: 'Vendors', icon: Users, route: '/procurement/vendors', badge: null },
    { label: 'Items', icon: Tag, route: '/procurement/items', badge: null },
    { label: 'Requisitions', icon: FileText, route: '/procurement/requisitions', badge: 3 },
    { label: 'Purchase Orders', icon: Package, route: '/procurement/orders', badge: null },
    { label: 'Goods Receipt', icon: Truck, route: '/procurement/grn', badge: null },
]);

const assetsItems = ref([
    { label: 'Register', icon: Boxes, route: '/assets/register', badge: null },
    { label: 'Categories', icon: Folder, route: '/assets/categories', badge: null },
    { label: 'Transfers', icon: MoveRight, route: '/assets/transfers', badge: null },
    { label: 'Maintenance', icon: Wrench, route: '/assets/maintenance', badge: 2 },
    { label: 'Depreciation', icon: TrendingDown, route: '/assets/depreciation', badge: null },
    { label: 'Verification', icon: CheckSquare, route: '/assets/verification', badge: null },
]);

const profileMenuItems = ref([
    {
        label: 'My Profile',
        icon: 'pi pi-user',
        command: () => {
            router.visit('/profile');
        }
    },
    {
        label: 'My Requisitions',
        icon: 'pi pi-file',
        command: () => {
            router.visit('/procurement/requisitions?filter=mine');
        }
    },
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => {
            router.visit('/settings');
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