<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Button  from 'primevue/button';
import Tag     from 'primevue/tag';
import Avatar  from 'primevue/avatar';
import {
    ShoppingCart, Box, Clock, Wrench,
    TrendingUp, TrendingDown, FileText, FilePlus,
    Package, Truck, BarChart3, ArrowRight,
    CheckCircle, XCircle, AlertCircle, Circle,
    DollarSign, AlertTriangle, Calendar, Users,
    ChevronRight,
} from 'lucide-vue-next';

// ── Props from controller ──────────────────────────────────────────────────
const props = defineProps({
    stats:               { type: Object, default: () => ({}) },
    recent_prs:          { type: Array,  default: () => [] },
    pending_queue:       { type: Array,  default: () => [] },
    monthly_trend:       { type: Array,  default: () => [] },
    asset_status_dist:   { type: Object, default: () => ({}) },
    pr_status_dist:      { type: Object, default: () => ({}) },
    upcoming_maintenance:{ type: Array,  default: () => [] },
    recent_grns:         { type: Array,  default: () => [] },
    top_vendors:         { type: Array,  default: () => [] },
});

const page     = usePage();
const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'there');

// ── Greeting ──────────────────────────────────────────────────────────────
const hour     = new Date().getHours();
const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';

// ── Helpers ───────────────────────────────────────────────────────────────
const fmt = (n) => new Intl.NumberFormat('en-BD', { minimumFractionDigits: 0 }).format(n ?? 0);
const fmtCurrency = (n) => '৳ ' + fmt(n);

function prSeverity(status) {
    const map = {
        draft: 'secondary', submitted: 'info',
        pending_level_1: 'warn', pending_level_2: 'warn', pending_level_3: 'warn',
        approved: 'success', rejected: 'danger', cancelled: 'secondary',
    };
    return map[status] ?? 'info';
}

function priorityClass(p) {
    return { urgent: 'text-red-600', high: 'text-orange-500', medium: 'text-yellow-600', low: 'text-gray-400' }[p] ?? 'text-gray-400';
}

// ── Sparkline (inline SVG bar chart for monthly trend) ────────────────────
const sparkMax = computed(() => Math.max(...props.monthly_trend.map(m => m.value), 1));

function barHeight(val) {
    return Math.max(4, Math.round((val / sparkMax.value) * 52));
}

// ── Mini donut for PR status ──────────────────────────────────────────────
const donutSlices = computed(() => {
    const d = props.pr_status_dist ?? {};
    const data = [
        { label: 'Approved', value: +d.approved || 0, color: '#22c55e' },
        { label: 'Pending',  value: +d.pending  || 0, color: '#f59e0b' },
        { label: 'Draft',    value: +d.draft    || 0, color: '#94a3b8' },
        { label: 'Rejected', value: +d.rejected || 0, color: '#ef4444' },
    ];
    const total = data.reduce((s, d) => s + d.value, 0) || 1;
    let offset = 0;
    return data.map(d => {
        const pct = d.value / total;
        const dash = pct * 100;
        const gap  = 100 - dash;
        const slice = { ...d, pct, dash, gap, offset };
        offset += dash;
        return slice;
    });
});
const donutTotal = computed(() =>
    (+props.pr_status_dist?.approved || 0) +
    (+props.pr_status_dist?.pending  || 0) +
    (+props.pr_status_dist?.draft    || 0) +
    (+props.pr_status_dist?.rejected || 0)
);

// ── Asset status dist ─────────────────────────────────────────────────────
const assetBars = computed(() => {
    const d = props.asset_status_dist ?? {};
    const items = [
        { label: 'Active',      value: d.active ?? 0,           color: 'bg-emerald-500' },
        { label: 'Maintenance', value: d.under_maintenance ?? 0, color: 'bg-amber-400' },
        { label: 'Disposed',    value: d.disposed ?? 0,          color: 'bg-gray-300' },
        { label: 'Lost',        value: d.lost ?? 0,              color: 'bg-red-400' },
    ];
    const max = Math.max(...items.map(i => i.value), 1);
    return items.map(i => ({ ...i, pct: Math.round((i.value / max) * 100) }));
});

// ── Polling for unread count (every 45 s) ─────────────────────────────────
const unreadCount = ref(page.props.auth?.user?.unread_notification_count ?? 0);
let pollTimer = null;

async function pollNotifications() {
    try {
        const res = await fetch('/notifications/unread', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
            const data = await res.json();
            unreadCount.value = data.unread_count ?? 0;
        }
    } catch { /* silent */ }
}

onMounted(() => {
    pollTimer = setInterval(pollNotifications, 45_000);
});

onUnmounted(() => clearInterval(pollTimer));

// ── Breadcrumbs ───────────────────────────────────────────────────────────
const breadcrumbs = [{ label: 'Dashboard' }];
</script>

<template>
    <TenantLayout :breadcrumb-items="breadcrumbs">

        <!-- ═══ GREETING ROW ════════════════════════════════════════════════ -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ greeting }}, {{ userName }} 👋
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ new Date().toLocaleDateString('en-BD', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    label="New PR"
                    size="small"
                    @click="router.visit('/procurement/requisitions/create')"
                >
                    <template #icon><FilePlus :size="15" class="mr-1" /></template>
                </Button>
                <Button
                    label="Register Asset"
                    severity="secondary"
                    size="small"
                    outlined
                    @click="router.visit('/assets/create')"
                >
                    <template #icon><Box :size="15" class="mr-1" /></template>
                </Button>
            </div>
        </div>

        <!-- ═══ KPI CARDS ════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <!-- Pending Approvals -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 cursor-pointer hover:shadow-md hover:border-orange-200 transition-all group"
                @click="router.visit('/procurement/requisitions?filter=pending_my_approval')"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
                        <Clock :size="20" class="text-orange-500" />
                    </div>
                    <ChevronRight :size="16" class="text-gray-300 group-hover:text-orange-400 transition mt-0.5" />
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ stats.pending_approvals ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">Pending Approvals</p>
                <p v-if="(stats.pending_approvals ?? 0) > 0" class="text-xs text-orange-500 mt-1 font-medium">
                    Action required
                </p>
                <p v-else class="text-xs text-green-500 mt-1 font-medium">All clear ✓</p>
            </div>

            <!-- My PRs -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 cursor-pointer hover:shadow-md hover:border-blue-200 transition-all group"
                @click="router.visit('/procurement/requisitions?filter=mine')"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <FileText :size="20" class="text-blue-500" />
                    </div>
                    <ChevronRight :size="16" class="text-gray-300 group-hover:text-blue-400 transition mt-0.5" />
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ stats.my_prs?.total ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">My Requisitions</p>
                <div class="flex gap-2 mt-1">
                    <span class="text-xs text-amber-500">{{ stats.my_prs?.pending ?? 0 }} pending</span>
                    <span class="text-xs text-gray-300">·</span>
                    <span class="text-xs text-green-500">{{ stats.my_prs?.approved ?? 0 }} approved</span>
                </div>
            </div>

            <!-- My Assets -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 cursor-pointer hover:shadow-md hover:border-emerald-200 transition-all group"
                @click="router.visit('/assets?custodian=me')"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <Box :size="20" class="text-emerald-500" />
                    </div>
                    <ChevronRight :size="16" class="text-gray-300 group-hover:text-emerald-400 transition mt-0.5" />
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ stats.my_assets ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">My Assets</p>
                <p class="text-xs text-gray-400 mt-1">{{ fmtCurrency(stats.total_asset_value) }} total NBV</p>
            </div>

            <!-- Maintenance Due -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 cursor-pointer hover:shadow-md transition-all group"
                :class="(stats.maintenance_due ?? 0) > 0 ? 'hover:border-red-200' : 'hover:border-gray-200'"
                @click="router.visit('/assets/maintenance')"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                         :class="(stats.maintenance_due ?? 0) > 0 ? 'bg-red-50' : 'bg-gray-50'">
                        <Wrench :size="20" :class="(stats.maintenance_due ?? 0) > 0 ? 'text-red-500' : 'text-gray-400'" />
                    </div>
                    <ChevronRight :size="16" class="text-gray-300 transition mt-0.5"
                                  :class="(stats.maintenance_due ?? 0) > 0 ? 'group-hover:text-red-400' : 'group-hover:text-gray-400'" />
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ stats.maintenance_due ?? 0 }}</p>
                <p class="text-sm text-gray-500 mt-1">Maintenance Due</p>
                <p class="text-xs mt-1"
                   :class="(stats.maintenance_due ?? 0) > 0 ? 'text-red-500 font-medium' : 'text-gray-400'">
                    {{ (stats.maintenance_due ?? 0) > 0 ? 'Within 7 days' : 'Nothing due soon' }}
                </p>
            </div>

        </div>

        <!-- ═══ MIDDLE ROW ════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

            <!-- Monthly Spend Sparkline -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Monthly Spend</p>
                        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ fmtCurrency(stats.monthly_spend) }}</p>
                        <p class="text-xs text-gray-400">{{ new Date().toLocaleString('default', {month:'long'}) }}</p>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                        <DollarSign :size="18" class="text-purple-500" />
                    </div>
                </div>

                <!-- Sparkline bars -->
                <div v-if="monthly_trend.length" class="flex items-end gap-1 h-16 mt-2">
                    <div
                        v-for="(m, i) in monthly_trend" :key="i"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <div
                            class="w-full rounded-t transition-all"
                            :class="i === monthly_trend.length - 1 ? 'bg-blue-500' : 'bg-blue-100'"
                            :style="{ height: barHeight(m.value) + 'px' }"
                        />
                        <span class="text-[9px] text-gray-400">{{ m.label }}</span>
                    </div>
                </div>
                <div v-else class="h-16 flex items-center justify-center">
                    <p class="text-xs text-gray-400">No PO data yet</p>
                </div>
            </div>

            <!-- PR Status Donut -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">PR Status</p>
                    <Link href="/procurement/requisitions" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-0.5">
                        View all <ChevronRight :size="13" />
                    </Link>
                </div>
                <div class="flex items-center gap-4">
                    <!-- SVG donut -->
                    <div class="relative flex-shrink-0">
                        <svg width="80" height="80" viewBox="0 0 36 36" class="-rotate-90">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3.8" />
                            <circle
                                v-for="(s, i) in donutSlices" :key="i"
                                cx="18" cy="18" r="15.9"
                                fill="none"
                                :stroke="s.color"
                                stroke-width="3.8"
                                :stroke-dasharray="`${s.dash} ${s.gap}`"
                                :stroke-dashoffset="-s.offset"
                                stroke-linecap="butt"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-bold text-gray-800">{{ donutTotal }}</span>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="flex flex-col gap-2 flex-1">
                        <div v-for="s in donutSlices" :key="s.label" class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: s.color }" />
                                <span class="text-gray-600">{{ s.label }}</span>
                            </div>
                            <span class="font-semibold text-gray-800">{{ s.value }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Status Bars -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Assets by Status</p>
                    <Link href="/assets" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-0.5">
                        View all <ChevronRight :size="13" />
                    </Link>
                </div>
                <div class="space-y-3">
                    <div v-for="bar in assetBars" :key="bar.label">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-600">{{ bar.label }}</span>
                            <span class="font-semibold text-gray-800">{{ bar.value }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="bar.color"
                                :style="{ width: bar.pct + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-50 flex justify-between text-xs">
                    <span class="text-gray-500">Total registered</span>
                    <span class="font-bold text-gray-800">{{ stats.total_assets ?? 0 }}</span>
                </div>
            </div>

        </div>

        <!-- ═══ BOTTOM ROW ════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Recent PRs (spans 2 cols) -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                    <div class="flex items-center gap-2">
                        <FileText :size="17" class="text-gray-400" />
                        <p class="font-semibold text-gray-800 text-sm">Recent Requisitions</p>
                    </div>
                    <Link href="/procurement/requisitions" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-0.5">
                        View all <ChevronRight :size="13" />
                    </Link>
                </div>

                <!-- Table -->
                <div v-if="recent_prs.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-50">
                                <th class="px-5 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">PR #</th>
                                <th class="px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Requester</th>
                                <th class="px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wide hidden md:table-cell">Dept</th>
                                <th class="px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wide text-right">Amount</th>
                                <th class="px-5 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="pr in recent_prs" :key="pr.id"
                                class="border-b border-gray-50 hover:bg-gray-50 cursor-pointer transition-colors"
                                @click="router.visit('/procurement/requisitions/' + pr.id)"
                            >
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-blue-600">{{ pr.pr_number }}</span>
                                        <span v-if="pr.is_mine" class="text-[9px] bg-blue-50 text-blue-500 px-1.5 py-0.5 rounded font-medium">Mine</span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ pr.date }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            :label="pr.requester?.charAt(0) ?? '?'"
                                            size="small" shape="circle"
                                            class="!w-6 !h-6 !text-[10px] !bg-blue-100 !text-blue-700 flex-shrink-0"
                                        />
                                        <span class="text-gray-700 text-xs truncate max-w-[90px]">{{ pr.requester }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 hidden md:table-cell">
                                    <span class="text-xs text-gray-500">{{ pr.department ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="text-sm font-semibold text-gray-800">{{ fmtCurrency(pr.total) }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <Tag
                                        :value="pr.status_badge?.label ?? pr.status"
                                        :severity="pr.status_badge?.severity ?? 'info'"
                                        class="text-[10px]"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center py-12 px-5 text-center">
                    <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                        <FileText :size="26" class="text-gray-300" />
                    </div>
                    <p class="text-sm font-medium text-gray-500">No requisitions yet</p>
                    <p class="text-xs text-gray-400 mt-1 mb-4">Create your first purchase requisition to get started</p>
                    <Button
                        label="Create Requisition"
                        size="small"
                        @click="router.visit('/procurement/requisitions/create')"
                    />
                </div>
            </div>

            <!-- Right column: Pending Queue + Maintenance -->
            <div class="flex flex-col gap-4">

                <!-- Pending approval queue -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm flex-1">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                        <div class="flex items-center gap-2">
                            <Clock :size="16" class="text-orange-400" />
                            <p class="font-semibold text-gray-800 text-sm">Needs Your Approval</p>
                        </div>
                        <span v-if="stats.pending_approvals > 0"
                              class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-semibold">
                            {{ stats.pending_approvals }}
                        </span>
                    </div>

                    <div v-if="pending_queue.length" class="divide-y divide-gray-50">
                        <div
                            v-for="pr in pending_queue" :key="pr.id"
                            class="px-5 py-3 hover:bg-orange-50 cursor-pointer transition-colors"
                            @click="router.visit('/procurement/requisitions/' + pr.id)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-mono font-semibold text-blue-600">{{ pr.pr_number }}</p>
                                    <p class="text-xs text-gray-700 truncate">{{ pr.requester }}</p>
                                    <p class="text-[10px] text-gray-400">{{ pr.department }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-bold text-gray-800">{{ fmtCurrency(pr.total) }}</p>
                                    <span
                                        class="text-[9px] font-semibold uppercase"
                                        :class="priorityClass(pr.priority)"
                                    >{{ pr.priority }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="stats.pending_approvals > pending_queue.length" class="px-5 py-2.5">
                            <Link href="/procurement/requisitions?filter=pending_my_approval"
                                  class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-1">
                                +{{ stats.pending_approvals - pending_queue.length }} more waiting
                                <ChevronRight :size="12" />
                            </Link>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center py-8 text-center px-4">
                        <CheckCircle :size="28" class="text-green-400 mb-2" />
                        <p class="text-xs text-gray-500">No approvals waiting for you</p>
                    </div>
                </div>

                <!-- Upcoming maintenance -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                        <div class="flex items-center gap-2">
                            <Wrench :size="16" class="text-gray-400" />
                            <p class="font-semibold text-gray-800 text-sm">Upcoming Maintenance</p>
                        </div>
                        <Link href="/assets/maintenance" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-0.5">
                            All <ChevronRight :size="13" />
                        </Link>
                    </div>

                    <div v-if="upcoming_maintenance.length" class="divide-y divide-gray-50">
                        <div
                            v-for="m in upcoming_maintenance" :key="m.id"
                            class="px-5 py-3 flex items-start gap-3"
                        >
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="m.is_overdue ? 'bg-red-50' : 'bg-amber-50'"
                            >
                                <AlertTriangle :size="15" :class="m.is_overdue ? 'text-red-500' : 'text-amber-500'" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ m.asset_name }}</p>
                                <p class="text-[10px] text-gray-400">{{ m.asset_tag }} · {{ m.branch }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-[10px]" :class="m.is_overdue ? 'text-red-500 font-semibold' : 'text-gray-500'">
                                        {{ m.is_overdue ? 'Overdue' : m.days_away === 0 ? 'Today' : `In ${m.days_away}d` }}
                                    </span>
                                    <span class="text-gray-200">·</span>
                                    <span class="text-[10px] text-gray-400 capitalize">{{ m.type?.replace('_',' ') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center py-6 text-center px-4">
                        <CheckCircle :size="24" class="text-green-400 mb-1.5" />
                        <p class="text-xs text-gray-500">Nothing due in 14 days</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ═══ BOTTOM STRIP: Top Vendors + Recent GRNs ══════════════════════ -->
        <div v-if="top_vendors.length || recent_grns.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">

            <!-- Top vendors this month -->
            <div v-if="top_vendors.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Users :size="16" class="text-gray-400" />
                        <p class="text-sm font-semibold text-gray-800">Top Vendors This Month</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div v-for="(v, i) in top_vendors" :key="v.name" class="flex items-center gap-3">
                        <span class="text-xs font-bold text-gray-300 w-4 flex-shrink-0">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-700 truncate">{{ v.name }}</p>
                            <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                <div
                                    class="h-full bg-blue-400 rounded-full"
                                    :style="{ width: Math.round((v.total / top_vendors[0].total) * 100) + '%' }"
                                />
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 flex-shrink-0">{{ fmtCurrency(v.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent GRNs -->
            <div v-if="recent_grns.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Truck :size="16" class="text-gray-400" />
                        <p class="text-sm font-semibold text-gray-800">Recent Goods Received</p>
                    </div>
                    <Link href="/procurement/grn" class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-0.5">
                        View all <ChevronRight :size="13" />
                    </Link>
                </div>
                <div class="space-y-2.5">
                    <div
                        v-for="g in recent_grns" :key="g.id"
                        class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                        @click="router.visit('/procurement/grn/' + g.id)"
                    >
                        <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <Truck :size="14" class="text-green-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800">{{ g.grn_number }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ g.vendor }} · {{ g.branch }}</p>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0">{{ g.date }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ QUICK ACTIONS (always visible at bottom) ═════════════════════ -->
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button
                v-for="action in [
                    { label: 'New Requisition', icon: FilePlus,  route: '/procurement/requisitions/create', color: 'blue' },
                    { label: 'Receive Goods',   icon: Truck,     route: '/procurement/grn/create',          color: 'green' },
                    { label: 'Register Asset',  icon: Box,       route: '/assets/create',                   color: 'purple' },
                    { label: 'View Reports',    icon: BarChart3, route: '/reports/procurement',             color: 'gray' },
                ]" :key="action.label"
                class="flex items-center gap-3 bg-white border border-gray-100 rounded-xl px-4 py-3.5 hover:shadow-md transition-all text-left group cursor-pointer"
                @click="router.visit(action.route)"
            >
                <div
                    class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                    :class="{
                        'bg-blue-50 group-hover:bg-blue-100':   action.color === 'blue',
                        'bg-green-50 group-hover:bg-green-100': action.color === 'green',
                        'bg-purple-50 group-hover:bg-purple-100': action.color === 'purple',
                        'bg-gray-50 group-hover:bg-gray-100':   action.color === 'gray',
                    }"
                >
                    <component
                        :is="action.icon"
                        :size="17"
                        :class="{
                            'text-blue-500':   action.color === 'blue',
                            'text-green-500':  action.color === 'green',
                            'text-purple-500': action.color === 'purple',
                            'text-gray-500':   action.color === 'gray',
                        }"
                    />
                </div>
                <span class="text-sm font-medium text-gray-700">{{ action.label }}</span>
            </button>
        </div>

    </TenantLayout>
</template>