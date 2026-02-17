<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Layout
import TenantLayout from '@/Layouts/TenantLayout.vue';

// PrimeVue
import Toast         from 'primevue/toast';
import Card          from 'primevue/card';
import DataTable     from 'primevue/datatable';
import Column        from 'primevue/column';
import Button        from 'primevue/button';
import InputText     from 'primevue/inputtext';
import Dropdown      from 'primevue/dropdown';
import Tag           from 'primevue/tag';
import Paginator     from 'primevue/paginator';
import ConfirmDialog from 'primevue/confirmdialog';
import Skeleton      from 'primevue/skeleton';
import Menu          from 'primevue/menu';
import Divider       from 'primevue/divider';

// Lucide icons
import {
    Plus,
    Search,
    Filter,
    FileText,
    Eye,
    Pencil,
    Trash2,
    MoreVertical,
    CheckCircle2,
    Clock,
    XCircle,
    AlertCircle,
    ShoppingCart,
    RefreshCw,
    Download,
    ChevronDown,
    TrendingUp,
    Package,
    CalendarDays,
    Building2,
} from 'lucide-vue-next';

// ─────────────────────────────────────────────
// Props  (passed from the controller via Inertia)
// ─────────────────────────────────────────────
const props = defineProps({
    requisitions: {
        type: Object,
        default: () => ({ data: [], links: [], meta: { total: 0, per_page: 15, current_page: 1 } }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', priority: '', department_id: '' }),
    },
    departments: { type: Array, default: () => [] },
    branches:    { type: Array, default: () => [] },
    /** Summary counters  { total, draft, submitted, approved, rejected } */
    summary:     { type: Object, default: () => ({}) },
    canCreate:   { type: Boolean, default: true },
});

// ─────────────────────────────────────────────
// Composables
// ─────────────────────────────────────────────
const toast   = useToast();
const confirm = useConfirm();

// ─────────────────────────────────────────────
// Filter state  (local, applied on search)
// ─────────────────────────────────────────────
const search       = ref(props.filters.search       ?? '');
const statusFilter = ref(props.filters.status       ?? '');
const priorityFilter = ref(props.filters.priority   ?? '');
const departmentFilter = ref(props.filters.department_id ?? '');
const loading      = ref(false);

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Draft',        value: 'draft'     },
    { label: 'Submitted',    value: 'submitted' },
    { label: 'Approved',     value: 'approved'  },
    { label: 'Rejected',     value: 'rejected'  },
    { label: 'Cancelled',    value: 'cancelled' },
];

const priorityOptions = [
    { label: 'All Priorities', value: '' },
    { label: 'Low',    value: 'low'    },
    { label: 'Medium', value: 'medium' },
    { label: 'High',   value: 'high'   },
    { label: 'Urgent', value: 'urgent' },
];

const departmentOptions = computed(() => [
    { name: 'All Departments', id: '' },
    ...props.departments,
]);

// ─────────────────────────────────────────────
// Apply filters  (debounced on search input)
// ─────────────────────────────────────────────
let searchTimeout = null;

const applyFilters = (page = 1) => {
    loading.value = true;
    router.get(
        route('tenant.requisitions.index'),
        {
            search:        search.value       || undefined,
            status:        statusFilter.value || undefined,
            priority:      priorityFilter.value || undefined,
            department_id: departmentFilter.value || undefined,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => { loading.value = false; },
        }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch([statusFilter, priorityFilter, departmentFilter], () => applyFilters());

const resetFilters = () => {
    search.value         = '';
    statusFilter.value   = '';
    priorityFilter.value = '';
    departmentFilter.value = '';
};

const onPageChange = (e) => applyFilters(e.page + 1);

// ─────────────────────────────────────────────
// Status helpers
// ─────────────────────────────────────────────
const statusConfig = {
    draft:     { severity: 'secondary', icon: Clock,         label: 'Draft'     },
    submitted: { severity: 'info',      icon: AlertCircle,   label: 'Submitted' },
    approved:  { severity: 'success',   icon: CheckCircle2,  label: 'Approved'  },
    rejected:  { severity: 'danger',    icon: XCircle,       label: 'Rejected'  },
    cancelled: { severity: 'warning',   icon: XCircle,       label: 'Cancelled' },
};

const getStatusConfig = (status) =>
    statusConfig[status] ?? { severity: 'secondary', icon: Clock, label: status };

const priorityConfig = {
    low:    { severity: 'secondary', class: 'bg-gray-100 text-gray-600'   },
    medium: { severity: 'info',      class: 'bg-blue-100 text-blue-700'   },
    high:   { severity: 'warning',   class: 'bg-orange-100 text-orange-700' },
    urgent: { severity: 'danger',    class: 'bg-red-100 text-red-700'     },
};

const getPriorityClass = (priority) =>
    priorityConfig[priority]?.class ?? 'bg-gray-100 text-gray-600';

// ─────────────────────────────────────────────
// Row actions menu
// ─────────────────────────────────────────────
const actionMenu    = ref();
const actionTarget  = ref(null);

const openActionMenu = (event, row) => {
    actionTarget.value = row;
    actionMenu.value.toggle(event);
};

const actionMenuItems = computed(() => {
    const row = actionTarget.value;
    if (!row) return [];

    const items = [
        {
            label:   'View',
            icon:    'pi pi-eye',
            command: () => router.visit(route('tenant.requisitions.show', row.id)),
        },
    ];

    if (['draft'].includes(row.status)) {
        items.push({
            label:   'Edit',
            icon:    'pi pi-pencil',
            command: () => router.visit(route('tenant.requisitions.edit', row.id)),
        });
    }

    if (['draft'].includes(row.status)) {
        items.push({ separator: true });
        items.push({
            label:   'Delete',
            icon:    'pi pi-trash',
            class:   'text-red-600',
            command: () => confirmDelete(row),
        });
    }

    return items;
});

// ─────────────────────────────────────────────
// Delete
// ─────────────────────────────────────────────
const confirmDelete = (row) => {
    confirm.require({
        message: `Delete requisition "${row.pr_number}"? This cannot be undone.`,
        header:  'Delete Requisition',
        icon:    'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.requisitions.destroy', row.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Requisition deleted.', life: 3000 }),
                onError:   () => toast.add({ severity: 'error',   summary: 'Error',   detail: 'Failed to delete.',   life: 3000 }),
            });
        },
    });
};

// ─────────────────────────────────────────────
// Misc helpers
// ─────────────────────────────────────────────
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatAmount = (amount) =>
    Number(amount ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const hasActiveFilters = computed(() =>
    !!search.value || !!statusFilter.value || !!priorityFilter.value || !!departmentFilter.value
);

// ─────────────────────────────────────────────
// Summary cards config
// ─────────────────────────────────────────────
const summaryCards = computed(() => [
    {
        label: 'Total PRs',
        value: props.summary.total     ?? props.requisitions.meta?.total ?? 0,
        icon:  FileText,
        color: 'blue',
    },
    {
        label: 'Pending Approval',
        value: props.summary.submitted ?? 0,
        icon:  Clock,
        color: 'yellow',
    },
    {
        label: 'Approved',
        value: props.summary.approved  ?? 0,
        icon:  CheckCircle2,
        color: 'green',
    },
    {
        label: 'Drafts',
        value: props.summary.draft     ?? 0,
        icon:  Pencil,
        color: 'gray',
    },
]);

const colorMap = {
    blue:   'bg-blue-50 text-blue-600 border-blue-100',
    yellow: 'bg-yellow-50 text-yellow-600 border-yellow-100',
    green:  'bg-green-50 text-green-600 border-green-100',
    gray:   'bg-gray-50 text-gray-500 border-gray-200',
};

const colorIcon = {
    blue:   'bg-blue-100 text-blue-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    green:  'bg-green-100 text-green-600',
    gray:   'bg-gray-100 text-gray-500',
};
</script>

<template>
    <TenantLayout
        title="Purchase Requisitions"
        :breadcrumb-items="[
            { label: 'Procurement', route: '/procurement' },
            { label: 'Requisitions' },
        ]"
    >
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="space-y-6">

            <!-- ══════════════════════════════════════
                 Page header
                 ══════════════════════════════════════ -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Purchase Requisitions</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage and track all purchase requests.</p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <Button
                        label="Export"
                        icon="pi pi-download"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="router.visit(route('tenant.requisitions.index', { export: 'csv', ...filters }))"
                    />
                    <Button
                        v-if="canCreate"
                        label="New Requisition"
                        icon="pi pi-plus"
                        size="small"
                        @click="router.visit(route('tenant.requisitions.create'))"
                    />
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 Summary cards
                 ══════════════════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    :class="['rounded-xl border p-4 flex items-center gap-4 transition hover:shadow-sm', colorMap[card.color]]"
                >
                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', colorIcon[card.color]]">
                        <component :is="card.icon" :size="20" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold leading-tight">{{ card.value }}</p>
                        <p class="text-xs font-medium opacity-80">{{ card.label }}</p>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 Filters
                 ══════════════════════════════════════ -->
            <Card>
                <template #content>
                    <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-end">

                        <!-- Search -->
                        <div class="flex-1 min-w-0 w-full lg:w-auto">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Search</label>
                            <div class="p-input-icon-left w-full">
                                <i class="pi pi-search" style="font-size:.85rem" />
                                <InputText
                                    v-model="search"
                                    placeholder="PR number, purpose, department…"
                                    class="w-full pl-8"
                                />
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="w-full lg:w-44">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Status</label>
                            <Dropdown
                                v-model="statusFilter"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>

                        <!-- Priority -->
                        <div class="w-full lg:w-44">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Priority</label>
                            <Dropdown
                                v-model="priorityFilter"
                                :options="priorityOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>

                        <!-- Department -->
                        <div class="w-full lg:w-52">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Department</label>
                            <Dropdown
                                v-model="departmentFilter"
                                :options="departmentOptions"
                                optionLabel="name"
                                optionValue="id"
                                class="w-full"
                                filter
                            />
                        </div>

                        <!-- Reset -->
                        <div class="flex-shrink-0 pt-5">
                            <Button
                                v-if="hasActiveFilters"
                                icon="pi pi-times"
                                label="Reset"
                                severity="secondary"
                                outlined
                                @click="resetFilters"
                            />
                        </div>

                    </div>
                </template>
            </Card>

            <!-- ══════════════════════════════════════
                 Table
                 ══════════════════════════════════════ -->
            <Card>
                <template #content>

                    <!-- Loading skeleton -->
                    <div v-if="loading" class="space-y-3">
                        <Skeleton v-for="n in 8" :key="n" height="2.8rem" border-radius="8px" />
                    </div>

                    <!-- Data table -->
                    <DataTable
                        v-else
                        :value="requisitions.data"
                        :rows="requisitions.meta?.per_page ?? 15"
                        dataKey="id"
                        rowHover
                        class="text-sm"
                        :pt="{
                            thead: { class: 'bg-gray-50' },
                        }"
                    >
                        <!-- Empty state -->
                        <template #empty>
                            <div class="text-center py-16 text-gray-400">
                                <ShoppingCart :size="52" class="mx-auto mb-4 opacity-30" />
                                <p class="text-base font-semibold text-gray-500 mb-1">No requisitions found</p>
                                <p class="text-sm mb-4">
                                    {{ hasActiveFilters ? 'Try adjusting your filters.' : 'Create your first purchase requisition.' }}
                                </p>
                                <Button
                                    v-if="!hasActiveFilters && canCreate"
                                    label="New Requisition"
                                    icon="pi pi-plus"
                                    size="small"
                                    @click="router.visit(route('tenant.requisitions.create'))"
                                />
                                <Button
                                    v-else-if="hasActiveFilters"
                                    label="Reset Filters"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    @click="resetFilters"
                                />
                            </div>
                        </template>

                        <!-- PR Number -->
                        <Column header="PR Number" style="width: 13%">
                            <template #body="{ data }">
                                <button
                                    class="text-blue-600 font-semibold hover:text-blue-800 hover:underline transition text-left"
                                    @click="router.visit(route('tenant.requisitions.show', data.id))"
                                >
                                    {{ data.pr_number }}
                                </button>
                            </template>
                        </Column>

                        <!-- Department -->
                        <Column header="Department" style="width: 16%">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5 text-gray-700">
                                    <Building2 :size="14" class="text-gray-400 flex-shrink-0" />
                                    <span class="truncate">{{ data.department?.name ?? '—' }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Purpose -->
                        <Column header="Purpose" style="width: 22%">
                            <template #body="{ data }">
                                <p class="truncate max-w-xs text-gray-700" :title="data.purpose">
                                    {{ data.purpose ?? '—' }}
                                </p>
                            </template>
                        </Column>

                        <!-- Required By -->
                        <Column header="Required By" style="width: 12%">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1.5 text-gray-600 text-xs">
                                    <CalendarDays :size="13" class="text-gray-400" />
                                    {{ formatDate(data.required_by_date) }}
                                </div>
                            </template>
                        </Column>

                        <!-- Priority -->
                        <Column header="Priority" style="width: 10%">
                            <template #body="{ data }">
                                <span
                                    :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold capitalize', getPriorityClass(data.priority)]"
                                >
                                    {{ data.priority ?? '—' }}
                                </span>
                            </template>
                        </Column>

                        <!-- Total Amount -->
                        <Column header="Total (BDT)" style="width: 12%">
                            <template #body="{ data }">
                                <span class="font-semibold text-gray-800">
                                    {{ formatAmount(data.total_amount) }}
                                </span>
                            </template>
                        </Column>

                        <!-- Status -->
                        <Column header="Status" style="width: 11%">
                            <template #body="{ data }">
                                <Tag
                                    :value="getStatusConfig(data.status).label"
                                    :severity="getStatusConfig(data.status).severity"
                                    class="capitalize text-xs"
                                />
                            </template>
                        </Column>

                        <!-- Created By -->
                        <Column header="Created By" style="width: 12%">
                            <template #body="{ data }">
                                <span class="text-gray-600 text-xs">{{ data.created_by?.name ?? '—' }}</span>
                            </template>
                        </Column>

                        <!-- Actions -->
                        <Column style="width: 5%" frozen alignFrozen="right">
                            <template #body="{ data }">
                                <Button
                                    icon="pi pi-ellipsis-v"
                                    severity="secondary"
                                    text
                                    rounded
                                    size="small"
                                    @click="openActionMenu($event, data)"
                                    aria-haspopup="true"
                                />
                            </template>
                        </Column>

                    </DataTable>

                    <!-- Pagination -->
                    <div
                        v-if="!loading && (requisitions.meta?.total ?? 0) > (requisitions.meta?.per_page ?? 15)"
                        class="mt-4 flex items-center justify-between gap-4 flex-wrap"
                    >
                        <p class="text-sm text-gray-500">
                            Showing
                            {{ ((requisitions.meta.current_page - 1) * requisitions.meta.per_page) + 1 }}–{{ Math.min(requisitions.meta.current_page * requisitions.meta.per_page, requisitions.meta.total) }}
                            of {{ requisitions.meta.total }} results
                        </p>
                        <Paginator
                            :rows="requisitions.meta.per_page"
                            :totalRecords="requisitions.meta.total"
                            :first="(requisitions.meta.current_page - 1) * requisitions.meta.per_page"
                            :rowsPerPageOptions="[10, 15, 25, 50]"
                            @page="onPageChange"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                            class="border-none p-0"
                        />
                    </div>

                </template>
            </Card>

        </div>

        <!-- Row action context menu -->
        <Menu ref="actionMenu" :model="actionMenuItems" :popup="true" />

    </TenantLayout>
</template>