<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast   from 'primevue/toast';
import Avatar  from 'primevue/avatar';
import Badge   from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Select     from 'primevue/select';
import Button     from 'primevue/button';
import Menu       from 'primevue/menu';
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
    Building2,
    GitBranch,
    ChevronDown,
} from 'lucide-vue-next';

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
    breadcrumbItems: { type: Array, default: () => [] },
});

// ─── Page / auth ─────────────────────────────────────────────────────────────
const page = usePage();

const userName    = computed(() => page.props.auth?.user?.name ?? 'User');
const userRole    = computed(() => {
    const r = page.props.auth?.user?.role ?? 'staff';
    return r.charAt(0).toUpperCase() + r.slice(1);
});
const userInitials = computed(() => {
    const parts = userName.value.split(' ');
    return parts.length >= 2
        ? parts[0][0] + parts[parts.length - 1][0]
        : userName.value.substring(0, 2).toUpperCase();
});

const schoolName     = computed(() => page.props.school?.name     ?? 'My School');
const schoolSubdomain= computed(() => page.props.school?.subdomain ?? window.location.hostname.split('.')[0]);
const schoolInitials = computed(() => {
    const w = schoolName.value.split(' ');
    return w.length >= 2 ? w[0][0] + w[1][0] : schoolName.value.substring(0, 2).toUpperCase();
});

// ─── Sidebar state ───────────────────────────────────────────────────────────
const sidebarCollapsed    = ref(false);
const procurementExpanded = ref(true);
const assetsExpanded      = ref(false);
const settingsExpanded    = ref(false);   // ← Settings dropdown

const toggleSidebar     = () => { sidebarCollapsed.value    = !sidebarCollapsed.value; };
const toggleProcurement = () => { procurementExpanded.value = !procurementExpanded.value; };
const toggleAssets      = () => { assetsExpanded.value      = !assetsExpanded.value; };
const toggleSettings    = () => { settingsExpanded.value    = !settingsExpanded.value; };

// Auto-expand section if current URL is inside it
const url = page.url;
if (url.startsWith('/procurement'))  procurementExpanded.value = true;
if (url.startsWith('/assets'))       assetsExpanded.value      = true;
if (url.startsWith('/settings'))     settingsExpanded.value    = true;

const isActive = (path) => page.url.startsWith(path);

// ─── Branch selector ─────────────────────────────────────────────────────────
const selectedBranch = ref(null);
const branches = computed(() => page.props.branches ?? []);

// ─── Profile menu ────────────────────────────────────────────────────────────
const profileMenu = ref();
const toggleProfileMenu = (e) => profileMenu.value.toggle(e);
const profileMenuItems = [
    { label: 'My Profile',       icon: 'pi pi-user',     command: () => router.visit('/profile') },
    { label: 'My Requisitions',  icon: 'pi pi-file',     command: () => router.visit('/procurement/requisitions?filter=mine') },
    { separator: true },
    { label: 'Logout',           icon: 'pi pi-sign-out', command: () => router.post('/logout') },
];

// ─── Menu definitions ─────────────────────────────────────────────────────────
const topMenuItems = [
    { label: 'Dashboard', icon: Home,      route: '/dashboard' },
    { label: 'Reports',   icon: BarChart3, route: '/reports'   },
    { label: 'Help',      icon: HelpCircle,route: '/help'      },
];

const procurementItems = [
    { label: 'Vendors',         icon: Users,    route: '/procurement/vendors',       badge: null },
    { label: 'Categories',         icon: Users,    route: '/procurement/categories',       badge: null },
    { label: 'Items',           icon: Tag,      route: '/procurement/items',         badge: null },
    { label: 'Requisitions',    icon: FileText, route: '/procurement/requisitions',  badge: 3    },
    { label: 'Purchase Orders', icon: Package,  route: '/procurement/orders',        badge: null },
    { label: 'Goods Receipt',   icon: Truck,    route: '/procurement/grn',           badge: null },
];

const assetsItems = [
    { label: 'Register',      icon: Boxes,       route: '/assets/register',      badge: null },
    { label: 'Categories',    icon: Folder,      route: '/assets/categories',    badge: null },
    { label: 'Transfers',     icon: MoveRight,   route: '/assets/transfers',     badge: null },
    { label: 'Maintenance',   icon: Wrench,      route: '/assets/maintenance',   badge: 2    },
    { label: 'Depreciation',  icon: TrendingDown,route: '/assets/depreciation',  badge: null },
    { label: 'Verification',  icon: CheckSquare, route: '/assets/verification',  badge: null },
];

// Settings sub-items — add more here as you build them
const settingsItems = [
    { label: 'Departments', icon: Building2,  route: '/settings/departments' },
    { label: 'Branches',    icon: GitBranch,  route: '/settings/branches'    },
    // { label: 'Users',    icon: Users,       route: '/settings/users'       },
    // { label: 'Modules',  icon: Package,     route: '/settings/modules'     },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <Toast position="top-right" />

        <!-- ═══════════════════════════════════════════════════════════════
             SIDEBAR
             ═══════════════════════════════════════════════════════════════ -->
        <aside
            :class="['bg-white border-r border-gray-200 transition-all duration-300 shadow-sm flex-shrink-0',
                     sidebarCollapsed ? 'w-[72px]' : 'w-64']"
        >
            <div class="flex flex-col h-screen sticky top-0">

                <!-- Logo + collapse toggle -->
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-2">
                    <div v-if="!sidebarCollapsed" class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700
                                    flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {{ schoolInitials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ schoolName }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ schoolSubdomain }}</p>
                        </div>
                    </div>
                    <button @click="toggleSidebar" class="text-gray-400 hover:text-gray-600 transition flex-shrink-0">
                        <X       :size="20" v-if="!sidebarCollapsed" />
                        <MenuIcon :size="20" v-else />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-3 overflow-y-auto space-y-0.5">

                    <!-- Top-level items (Dashboard, Reports, Help) -->
                    <Link
                        v-for="item in topMenuItems"
                        :key="item.route"
                        :href="item.route"
                        :title="sidebarCollapsed ? item.label : undefined"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group',
                                 isActive(item.route)
                                    ? 'bg-blue-50 text-blue-700 font-medium'
                                    : 'text-gray-700 hover:bg-gray-50']"
                    >
                        <component :is="item.icon" :size="19" class="flex-shrink-0"
                            :class="isActive(item.route) ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'" />
                        <span v-if="!sidebarCollapsed">{{ item.label }}</span>
                    </Link>

                    <!-- ── PROCUREMENT ─────────────────────── -->
                    <div class="pt-3">
                        <p v-if="!sidebarCollapsed" class="px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">
                            Modules
                        </p>

                        <!-- Procurement toggle -->
                        <button
                            @click="toggleProcurement"
                            :title="sidebarCollapsed ? 'Procurement' : undefined"
                            :class="['w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all',
                                     isActive('/procurement')
                                        ? 'bg-blue-50 text-blue-700'
                                        : 'text-gray-700 hover:bg-gray-50']"
                        >
                            <ShoppingCart :size="19" class="flex-shrink-0"
                                :class="isActive('/procurement') ? 'text-blue-600' : 'text-gray-400'" />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left">Procurement</span>
                            <ChevronDown v-if="!sidebarCollapsed" :size="15" class="text-gray-400 transition-transform"
                                :class="{ 'rotate-180': procurementExpanded }" />
                        </button>

                        <div v-show="!sidebarCollapsed && procurementExpanded" class="ml-7 mt-0.5 space-y-0.5">
                            <Link
                                v-for="sub in procurementItems" :key="sub.route" :href="sub.route"
                                :class="['flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                         isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50']"
                            >
                                <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                <span class="flex-1">{{ sub.label }}</span>
                                <Badge v-if="sub.badge" :value="sub.badge" severity="danger" />
                            </Link>
                        </div>

                        <!-- ── ASSETS ──────────────────────── -->
                        <button
                            @click="toggleAssets"
                            :title="sidebarCollapsed ? 'Assets' : undefined"
                            :class="['w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all mt-0.5',
                                     isActive('/assets')
                                        ? 'bg-blue-50 text-blue-700'
                                        : 'text-gray-700 hover:bg-gray-50']"
                        >
                            <Box :size="19" class="flex-shrink-0"
                                :class="isActive('/assets') ? 'text-blue-600' : 'text-gray-400'" />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left">Assets</span>
                            <ChevronDown v-if="!sidebarCollapsed" :size="15" class="text-gray-400 transition-transform"
                                :class="{ 'rotate-180': assetsExpanded }" />
                        </button>

                        <div v-show="!sidebarCollapsed && assetsExpanded" class="ml-7 mt-0.5 space-y-0.5">
                            <Link
                                v-for="sub in assetsItems" :key="sub.route" :href="sub.route"
                                :class="['flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                         isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50']"
                            >
                                <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                <span class="flex-1">{{ sub.label }}</span>
                                <Badge v-if="sub.badge" :value="sub.badge" severity="danger" />
                            </Link>
                        </div>

                        <!-- ── SETTINGS ─────────────────────── -->
                        <button
                            @click="toggleSettings"
                            :title="sidebarCollapsed ? 'Settings' : undefined"
                            :class="['w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all mt-0.5',
                                     isActive('/settings')
                                        ? 'bg-blue-50 text-blue-700'
                                        : 'text-gray-700 hover:bg-gray-50']"
                        >
                            <Settings :size="19" class="flex-shrink-0"
                                :class="isActive('/settings') ? 'text-blue-600' : 'text-gray-400'" />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left">Settings</span>
                            <ChevronDown v-if="!sidebarCollapsed" :size="15" class="text-gray-400 transition-transform"
                                :class="{ 'rotate-180': settingsExpanded }" />
                        </button>

                        <div v-show="!sidebarCollapsed && settingsExpanded" class="ml-7 mt-0.5 space-y-0.5">
                            <Link
                                v-for="sub in settingsItems" :key="sub.route" :href="sub.route"
                                :class="['flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                         isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50']"
                            >
                                <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                <span>{{ sub.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User section (bottom) -->
                <div class="p-3 border-t border-gray-100">
                    <div v-if="!sidebarCollapsed" class="flex items-center gap-3">
                        <Avatar :label="userInitials" shape="circle" size="normal"
                            class="!bg-blue-600 !text-white flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ userRole }}</p>
                        </div>
                        <button @click="router.post('/logout')" title="Logout"
                            class="text-gray-400 hover:text-red-500 transition">
                            <LogOut :size="17" />
                        </button>
                    </div>
                    <div v-else class="flex justify-center">
                        <Avatar :label="userInitials" shape="circle" size="small"
                            class="!bg-blue-600 !text-white" />
                    </div>
                </div>

            </div>
        </aside>

        <!-- ═══════════════════════════════════════════════════════════════
             MAIN AREA
             ═══════════════════════════════════════════════════════════════ -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Top navbar -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 flex-shrink-0 gap-4">

                <!-- Breadcrumb -->
                <Breadcrumb :model="breadcrumbItems" class="text-sm p-0 bg-transparent border-none flex-1 min-w-0">
                    <template #item="{ item }">
                        <Link v-if="item.route" :href="item.route" class="text-blue-600 hover:text-blue-700 text-sm">
                            {{ item.label }}
                        </Link>
                        <span v-else class="text-gray-500 text-sm">{{ item.label }}</span>
                    </template>
                    <template #separator>
                        <span class="text-gray-300 mx-1">/</span>
                    </template>
                </Breadcrumb>

                <!-- Right side actions -->
                <div class="flex items-center gap-3 flex-shrink-0">

                    <!-- Branch selector -->
                    <Select
                        v-model="selectedBranch"
                        :options="branches.length ? branches : [{ name: 'All Branches', id: null }]"
                        optionLabel="name"
                        placeholder="Select Branch"
                        class="w-44 text-sm"
                    >
                        <template #value="{ value }">
                            <div class="flex items-center gap-2">
                                <Building :size="15" class="text-gray-400" />
                                <span>{{ value?.name ?? 'All Branches' }}</span>
                            </div>
                        </template>
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <Building :size="14" class="text-gray-400" />
                                <span>{{ option.name }}</span>
                            </div>
                        </template>
                    </Select>

                    <!-- New PR shortcut -->
                    <Button
                        label="New PR"
                        icon="pi pi-plus"
                        size="small"
                        @click="router.visit('/procurement/requisitions/create')"
                    />

                    <!-- Profile dropdown -->
                    <button
                        @click="toggleProfileMenu"
                        class="flex items-center gap-2 hover:bg-gray-50 rounded-lg px-2 py-1.5 transition"
                    >
                        <Avatar :label="userInitials" shape="circle" size="normal"
                            class="!bg-blue-600 !text-white" />
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ userName }}</p>
                            <p class="text-xs text-gray-400 leading-tight">{{ userRole }}</p>
                        </div>
                        <ChevronDown :size="15" class="text-gray-400" />
                    </button>
                    <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />

                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-6 overflow-auto">
                <slot />
            </main>

        </div>
    </div>
</template>