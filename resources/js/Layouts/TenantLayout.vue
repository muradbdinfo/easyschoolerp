<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast      from 'primevue/toast';
import Avatar     from 'primevue/avatar';
import Badge      from 'primevue/badge';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown   from 'primevue/dropdown';
import Button     from 'primevue/button';
import Menu       from 'primevue/menu';
import {
    Menu        as MenuIcon,
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
    Bell,
    Search,
} from 'lucide-vue-next';

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    /** Array of { label, route? } objects for the top breadcrumb */
    breadcrumbItems: { type: Array, default: () => [] },

    /** Optional page title shown in the header */
    title: { type: String, default: '' },
});

// ─── Page / auth ──────────────────────────────────────────────────────────────
const page = usePage();

const user         = computed(() => page.props.auth?.user ?? {});
const userName     = computed(() => user.value.name  ?? 'User');
const userRole     = computed(() => {
    const r = user.value.role ?? 'staff';
    return r.charAt(0).toUpperCase() + r.slice(1);
});
const userInitials = computed(() => {
    const parts = userName.value.trim().split(/\s+/);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : userName.value.substring(0, 2).toUpperCase();
});

const schoolName      = computed(() => page.props.school?.name      ?? 'My School');
const schoolSubdomain = computed(() => page.props.school?.subdomain  ?? window.location.hostname.split('.')[0]);
const schoolInitials  = computed(() => {
    const w = schoolName.value.trim().split(/\s+/);
    return w.length >= 2 ? (w[0][0] + w[1][0]).toUpperCase() : schoolName.value.substring(0, 2).toUpperCase();
});

// ─── Notifications ────────────────────────────────────────────────────────────
const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

// ─── Sidebar state ────────────────────────────────────────────────────────────
const sidebarCollapsed    = ref(false);
const mobileOpen          = ref(false);
const procurementExpanded = ref(false);
const assetsExpanded      = ref(false);
const settingsExpanded    = ref(false);

const toggleSidebar     = () => { sidebarCollapsed.value    = !sidebarCollapsed.value; };
const toggleMobile      = () => { mobileOpen.value          = !mobileOpen.value; };
const toggleProcurement = () => { procurementExpanded.value = !procurementExpanded.value; };
const toggleAssets      = () => { assetsExpanded.value      = !assetsExpanded.value; };
const toggleSettings    = () => { settingsExpanded.value    = !settingsExpanded.value; };

// Auto-expand section that matches the current URL
const currentUrl = page.url;
if (currentUrl.startsWith('/procurement')) procurementExpanded.value = true;
if (currentUrl.startsWith('/assets'))      assetsExpanded.value      = true;
if (currentUrl.startsWith('/settings'))    settingsExpanded.value    = true;

/** Returns true when the current page URL starts with the given path */
const isActive = (path) => page.url.startsWith(path);

// Close mobile sidebar on navigation
watch(() => page.url, () => { mobileOpen.value = false; });

// ─── Branch selector ──────────────────────────────────────────────────────────
const selectedBranch = ref(null);
const branches       = computed(() => page.props.branches ?? []);

// ─── Profile menu ─────────────────────────────────────────────────────────────
const profileMenu = ref();
const toggleProfileMenu  = (e) => profileMenu.value.toggle(e);
const profileMenuItems   = [
    { label: 'My Profile',      icon: 'pi pi-user',     command: () => router.visit('/profile') },
    { label: 'My Requisitions', icon: 'pi pi-file',     command: () => router.visit('/procurement/requisitions?filter=mine') },
    { separator: true },
    { label: 'Logout',          icon: 'pi pi-sign-out', command: () => router.post('/logout') },
];

// ─── Navigation definitions ───────────────────────────────────────────────────
const topMenuItems = [
    { label: 'Dashboard', icon: Home,      route: '/dashboard' },
    { label: 'Reports',   icon: BarChart3, route: '/reports'   },
    { label: 'Help',      icon: HelpCircle,route: '/help'      },
];

const procurementItems = [
    { label: 'Vendors',         icon: Users,    route: '/procurement/vendors',      badge: null },
    { label: 'Items',           icon: Tag,      route: '/procurement/items',        badge: null },
    { label: 'Requisitions',    icon: FileText, route: '/procurement/requisitions', badge: null },
    { label: 'Purchase Orders', icon: Package,  route: '/procurement/orders',       badge: null },
    { label: 'Goods Receipt',   icon: Truck,    route: '/procurement/grn',          badge: null },
];

const assetsItems = [
    { label: 'Register',     icon: Boxes,       route: '/assets/register',    badge: null },
    { label: 'Categories',   icon: Folder,      route: '/assets/categories',  badge: null },
    { label: 'Transfers',    icon: MoveRight,   route: '/assets/transfers',   badge: null },
    { label: 'Maintenance',  icon: Wrench,      route: '/assets/maintenance', badge: null },
    { label: 'Depreciation', icon: TrendingDown,route: '/assets/depreciation',badge: null },
    { label: 'Verification', icon: CheckSquare, route: '/assets/verification',badge: null },
];

const settingsItems = [
    { label: 'Departments', icon: Building2, route: '/settings/departments' },
    { label: 'Branches',    icon: GitBranch, route: '/settings/branches'   },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <Toast position="top-right" />

        <!-- ══════════════════════════════════════════════════════════════
             MOBILE OVERLAY
             ══════════════════════════════════════════════════════════════ -->
        <transition name="fade">
            <div
                v-if="mobileOpen"
                class="fixed inset-0 bg-black/40 z-20 lg:hidden"
                @click="mobileOpen = false"
            />
        </transition>

        <!-- ══════════════════════════════════════════════════════════════
             SIDEBAR
             ══════════════════════════════════════════════════════════════ -->
        <aside
            :class="[
                'fixed lg:relative inset-y-0 left-0 z-30',
                'bg-white border-r border-gray-200 shadow-sm',
                'flex flex-col transition-all duration-300',
                // Desktop width
                sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
                // Mobile: slide in/out
                mobileOpen ? 'w-72 translate-x-0' : 'w-72 -translate-x-full lg:translate-x-0',
            ]"
        >
            <div class="flex flex-col h-screen">

                <!-- ── Logo + collapse toggle ── -->
                <div class="h-16 px-4 border-b border-gray-100 flex items-center justify-between gap-2 flex-shrink-0">
                    <div v-if="!sidebarCollapsed || mobileOpen" class="flex items-center gap-3 min-w-0">
                        <div
                            class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700
                                   flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                        >
                            {{ schoolInitials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ schoolName }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ schoolSubdomain }}</p>
                        </div>
                    </div>

                    <!-- Collapsed: just show initials block -->
                    <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700
                                       flex items-center justify-center text-white font-bold text-sm">
                        {{ schoolInitials }}
                    </div>

                    <!-- Toggle button (desktop only) -->
                    <button
                        @click="toggleSidebar"
                        class="hidden lg:flex text-gray-400 hover:text-gray-600 transition flex-shrink-0"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    >
                        <X        :size="20" v-if="!sidebarCollapsed" />
                        <MenuIcon :size="20" v-else />
                    </button>

                    <!-- Close button (mobile only) -->
                    <button
                        @click="mobileOpen = false"
                        class="lg:hidden text-gray-400 hover:text-gray-600 transition flex-shrink-0"
                    >
                        <X :size="20" />
                    </button>
                </div>

                <!-- ── Navigation ── -->
                <nav class="flex-1 p-3 overflow-y-auto space-y-0.5">

                    <!-- Top-level items -->
                    <Link
                        v-for="item in topMenuItems"
                        :key="item.route"
                        :href="item.route"
                        :title="sidebarCollapsed ? item.label : undefined"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group',
                            isActive(item.route)
                                ? 'bg-blue-50 text-blue-700 font-medium'
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :size="19"
                            class="flex-shrink-0"
                            :class="isActive(item.route) ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'"
                        />
                        <span v-if="!sidebarCollapsed">{{ item.label }}</span>
                    </Link>

                    <!-- ── MODULES section ── -->
                    <div class="pt-3">
                        <p
                            v-if="!sidebarCollapsed"
                            class="px-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1"
                        >
                            Modules
                        </p>

                        <!-- PROCUREMENT -->
                        <button
                            @click="toggleProcurement"
                            :title="sidebarCollapsed ? 'Procurement' : undefined"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all',
                                isActive('/procurement')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            <ShoppingCart
                                :size="19"
                                class="flex-shrink-0"
                                :class="isActive('/procurement') ? 'text-blue-600' : 'text-gray-400'"
                            />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left text-sm">Procurement</span>
                            <ChevronDown
                                v-if="!sidebarCollapsed"
                                :size="15"
                                class="text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': procurementExpanded }"
                            />
                        </button>

                        <transition name="slide">
                            <div v-show="!sidebarCollapsed && procurementExpanded" class="ml-7 mt-0.5 space-y-0.5">
                                <Link
                                    v-for="sub in procurementItems"
                                    :key="sub.route"
                                    :href="sub.route"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                        isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                    <span class="flex-1">{{ sub.label }}</span>
                                    <Badge v-if="sub.badge" :value="sub.badge" severity="danger" />
                                </Link>
                            </div>
                        </transition>

                        <!-- ASSETS -->
                        <button
                            @click="toggleAssets"
                            :title="sidebarCollapsed ? 'Assets' : undefined"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all mt-0.5',
                                isActive('/assets')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            <Box
                                :size="19"
                                class="flex-shrink-0"
                                :class="isActive('/assets') ? 'text-blue-600' : 'text-gray-400'"
                            />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left text-sm">Assets</span>
                            <ChevronDown
                                v-if="!sidebarCollapsed"
                                :size="15"
                                class="text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': assetsExpanded }"
                            />
                        </button>

                        <transition name="slide">
                            <div v-show="!sidebarCollapsed && assetsExpanded" class="ml-7 mt-0.5 space-y-0.5">
                                <Link
                                    v-for="sub in assetsItems"
                                    :key="sub.route"
                                    :href="sub.route"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                        isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                    <span class="flex-1">{{ sub.label }}</span>
                                    <Badge v-if="sub.badge" :value="sub.badge" severity="danger" />
                                </Link>
                            </div>
                        </transition>

                        <!-- SETTINGS -->
                        <button
                            @click="toggleSettings"
                            :title="sidebarCollapsed ? 'Settings' : undefined"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all mt-0.5',
                                isActive('/settings')
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            <Settings
                                :size="19"
                                class="flex-shrink-0"
                                :class="isActive('/settings') ? 'text-blue-600' : 'text-gray-400'"
                            />
                            <span v-if="!sidebarCollapsed" class="flex-1 text-left text-sm">Settings</span>
                            <ChevronDown
                                v-if="!sidebarCollapsed"
                                :size="15"
                                class="text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': settingsExpanded }"
                            />
                        </button>

                        <transition name="slide">
                            <div v-show="!sidebarCollapsed && settingsExpanded" class="ml-7 mt-0.5 space-y-0.5">
                                <Link
                                    v-for="sub in settingsItems"
                                    :key="sub.route"
                                    :href="sub.route"
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all',
                                        isActive(sub.route)
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    <component :is="sub.icon" :size="15" class="flex-shrink-0" />
                                    <span>{{ sub.label }}</span>
                                </Link>
                            </div>
                        </transition>
                    </div>
                </nav>

                <!-- ── User section (bottom) ── -->
                <div class="p-3 border-t border-gray-100 flex-shrink-0">
                    <div v-if="!sidebarCollapsed" class="flex items-center gap-3">
                        <Avatar
                            :label="userInitials"
                            shape="circle"
                            size="normal"
                            class="!bg-blue-600 !text-white flex-shrink-0"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ userRole }}</p>
                        </div>
                        <button
                            @click="router.post('/logout')"
                            title="Logout"
                            class="text-gray-400 hover:text-red-500 transition"
                        >
                            <LogOut :size="17" />
                        </button>
                    </div>
                    <div v-else class="flex justify-center">
                        <Avatar
                            :label="userInitials"
                            shape="circle"
                            size="small"
                            class="!bg-blue-600 !text-white"
                        />
                    </div>
                </div>

            </div>
        </aside>

        <!-- ══════════════════════════════════════════════════════════════
             MAIN AREA
             ══════════════════════════════════════════════════════════════ -->
        <div class="flex-1 flex flex-col min-w-0 lg:ml-0">

            <!-- ── Top Navbar ── -->
            <header
                class="bg-white border-b border-gray-200 h-16 flex items-center
                       justify-between px-4 lg:px-6 flex-shrink-0 gap-4 sticky top-0 z-10"
            >
                <!-- Left: mobile menu + breadcrumb -->
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <!-- Mobile hamburger -->
                    <button
                        @click="toggleMobile"
                        class="lg:hidden text-gray-500 hover:text-gray-700 transition flex-shrink-0"
                    >
                        <MenuIcon :size="22" />
                    </button>

                    <!-- Breadcrumb -->
                    <Breadcrumb
                        :model="breadcrumbItems"
                        class="text-sm p-0 bg-transparent border-none flex-1 min-w-0 hidden sm:block"
                    >
                        <template #item="{ item }">
                            <Link
                                v-if="item.route"
                                :href="item.route"
                                class="text-blue-600 hover:text-blue-700 text-sm"
                            >
                                {{ item.label }}
                            </Link>
                            <span v-else class="text-gray-500 text-sm">{{ item.label }}</span>
                        </template>
                        <template #separator>
                            <span class="text-gray-300 mx-1">/</span>
                        </template>
                    </Breadcrumb>

                    <!-- Mobile: page title fallback -->
                    <span v-if="title" class="sm:hidden text-sm font-semibold text-gray-800 truncate">
                        {{ title }}
                    </span>
                </div>

                <!-- Right: actions -->
                <div class="flex items-center gap-2 lg:gap-3 flex-shrink-0">

                    <!-- Branch selector -->
                    <Dropdown
                        v-model="selectedBranch"
                        :options="branches.length ? branches : [{ name: 'All Branches', id: null }]"
                        optionLabel="name"
                        placeholder="All Branches"
                        class="hidden md:flex w-44 text-sm"
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
                    </Dropdown>

                    <!-- New PR shortcut -->
                    <Button
                        label="New PR"
                        icon="pi pi-plus"
                        size="small"
                        class="hidden sm:flex"
                        @click="router.visit('/procurement/requisitions/create')"
                    />

                    <!-- Notifications -->
                    <Link
                        href="/notifications"
                        class="relative text-gray-500 hover:text-gray-700 transition p-2 rounded-lg hover:bg-gray-50"
                        title="Notifications"
                    >
                        <Bell :size="20" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute top-1 right-1 min-w-[18px] h-[18px] flex items-center justify-center
                                   bg-red-500 text-white text-[10px] font-bold rounded-full px-1 leading-none"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </Link>

                    <!-- Profile dropdown -->
                    <button
                        @click="toggleProfileMenu"
                        class="flex items-center gap-2 hover:bg-gray-50 rounded-lg px-2 py-1.5 transition"
                    >
                        <Avatar
                            :label="userInitials"
                            shape="circle"
                            size="normal"
                            class="!bg-blue-600 !text-white"
                        />
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ userName }}</p>
                            <p class="text-xs text-gray-400 leading-tight">{{ userRole }}</p>
                        </div>
                        <ChevronDown :size="15" class="text-gray-400" />
                    </button>
                    <Menu ref="profileMenu" :model="profileMenuItems" :popup="true" />

                </div>
            </header>

            <!-- ── Page content ── -->
            <main class="flex-1 p-4 lg:p-6 overflow-auto">
                <!-- Optional page heading slot -->
                <div v-if="title" class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                </div>

                <slot />
            </main>

        </div>
    </div>
</template>

<style scoped>
/* Sidebar slide transition for sub-menus */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.2s ease;
    overflow: hidden;
}
.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}
.slide-enter-to,
.slide-leave-from {
    opacity: 1;
    max-height: 400px;
    transform: translateY(0);
}

/* Mobile overlay fade */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>