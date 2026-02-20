<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable   from 'primevue/datatable';
import Column      from 'primevue/column';
import Button      from 'primevue/button';
import Tag         from 'primevue/tag';
import Select      from 'primevue/select';
import InputText   from 'primevue/inputtext';
import Toast       from 'primevue/toast';
import {
    Package, Search, Plus, Eye, Send,
    Building2, CalendarDays, DollarSign,
    FileText, Truck, CheckCircle2, XCircle, Clock,
} from 'lucide-vue-next';

// ── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    purchaseOrders: { type: Object, default: () => ({ data: [] }) },
    filters:        { type: Object, default: () => ({}) },
    vendors:        { type: Array,  default: () => [] },
    branches:       { type: Array,  default: () => [] },
    stats:          { type: Object, default: () => ({}) },
});

const toast  = useToast();
const page   = usePage();

// Flash messages
if (page.props.flash?.success) {
    toast.add({ severity: 'success', summary: 'Success', detail: page.props.flash.success, life: 3000 });
}

// ── Filters ──────────────────────────────────────────────────────────────────
const search   = ref(props.filters.search    ?? '');
const status   = ref(props.filters.status    ?? '');
const vendorId = ref(props.filters.vendor_id ?? '');
const loading  = ref(false);

const statusOptions = [
    { label: 'All Statuses',  value: '' },
    { label: 'Draft',         value: 'draft' },
    { label: 'Sent',          value: 'sent' },
    { label: 'Partial',       value: 'partial' },
    { label: 'Received',      value: 'received' },
    { label: 'Closed',        value: 'closed' },
    { label: 'Cancelled',     value: 'cancelled' },
];

const applyFilters = () => {
    loading.value = true;
    router.get(route('tenant.purchase-orders.index'), {
        search:    search.value   || undefined,
        status:    status.value   || undefined,
        vendor_id: vendorId.value || undefined,
    }, {
        preserveState: true,
        onFinish: () => { loading.value = false; },
    });
};

const resetFilters = () => {
    search.value   = '';
    status.value   = '';
    vendorId.value = '';
    applyFilters();
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatAmount = (v) =>
    '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });

const statusConfig = {
    draft:        { severity: 'secondary', icon: FileText    },
    sent:         { severity: 'info',      icon: Send        },
    acknowledged: { severity: 'warning',   icon: Clock       },
    partial:      { severity: 'warning',   icon: Truck       },
    received:     { severity: 'success',   icon: CheckCircle2},
    closed:       { severity: 'success',   icon: CheckCircle2},
    cancelled:    { severity: 'danger',    icon: XCircle     },
};

const getSeverity = (s) => statusConfig[s]?.severity ?? 'info';

// ── Pagination ───────────────────────────────────────────────────────────────
const goToPage = (p) => {
    loading.value = true;
    router.get(route('tenant.purchase-orders.index'), {
        page:      p,
        search:    search.value   || undefined,
        status:    status.value   || undefined,
        vendor_id: vendorId.value || undefined,
    }, { preserveState: true, onFinish: () => { loading.value = false; } });
};
</script>

<template>
    <TenantLayout title="Purchase Orders">
        <Toast position="top-right" />

        <div class="p-6 space-y-6">

            <!-- ── Stat cards ───────────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <Package :size="20" class="text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total</p>
                        <p class="text-xl font-bold text-gray-900">{{ stats.total ?? 0 }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                        <FileText :size="20" class="text-gray-500" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Draft</p>
                        <p class="text-xl font-bold text-gray-700">{{ stats.draft ?? 0 }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center">
                        <Send :size="20" class="text-sky-500" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Sent</p>
                        <p class="text-xl font-bold text-sky-600">{{ stats.sent ?? 0 }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                        <CheckCircle2 :size="20" class="text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Received</p>
                        <p class="text-xl font-bold text-green-600">{{ stats.received ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Toolbar ──────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">

                    <!-- Search -->
                    <div class="relative flex-1 min-w-0">
                        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                        <InputText
                            v-model="search"
                            placeholder="Search PO number or vendor..."
                            class="w-full pl-9 text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Status filter -->
                    <Select
                        v-model="status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="All Statuses"
                        class="w-44 text-sm"
                        @change="applyFilters"
                    />

                    <!-- Vendor filter -->
                    <Select
                        v-model="vendorId"
                        :options="[{ name: 'All Vendors', id: '' }, ...vendors]"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="All Vendors"
                        class="w-48 text-sm"
                        @change="applyFilters"
                    />

                    <Button
                        label="Reset"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="resetFilters"
                    />

                    <!-- Create PO -->
                    <Button
                        label="New PO"
                        size="small"
                        @click="router.visit(route('tenant.purchase-orders.create'))"
                    >
                        <template #icon>
                            <Plus :size="16" class="mr-1" />
                        </template>
                    </Button>

                </div>
            </div>

            <!-- ── Table ────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <DataTable
                    :value="purchaseOrders.data"
                    :loading="loading"
                    stripedRows
                    class="text-sm"
                    tableStyle="min-width: 800px"
                >
                    <!-- PO Number -->
                    <Column field="po_number" header="PO Number" style="width: 160px">
                        <template #body="{ data }">
                            <button
                                class="font-mono font-semibold text-blue-600 hover:text-blue-800 hover:underline text-sm"
                                @click="router.visit(route('tenant.purchase-orders.show', data.id))"
                            >
                                {{ data.po_number }}
                            </button>
                        </template>
                    </Column>

                    <!-- Date -->
                    <Column field="po_date" header="Date" style="width: 110px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <CalendarDays :size="13" class="text-gray-400" />
                                {{ formatDate(data.po_date) }}
                            </div>
                        </template>
                    </Column>

                    <!-- Vendor -->
                    <Column field="vendor.name" header="Vendor">
                        <template #body="{ data }">
                            <div>
                                <p class="font-medium text-gray-800">{{ data.vendor?.name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ data.vendor?.code }}</p>
                            </div>
                        </template>
                    </Column>

                    <!-- Branch -->
                    <Column field="branch.name" header="Branch" style="width: 130px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5 text-gray-600 text-sm">
                                <Building2 :size="13" class="text-gray-400" />
                                {{ data.branch?.name ?? '—' }}
                            </div>
                        </template>
                    </Column>

                    <!-- Items count -->
                    <Column header="Items" style="width: 70px; text-align: center">
                        <template #body="{ data }">
                            <span class="text-center block text-gray-600 font-medium">
                                {{ data.items?.length ?? 0 }}
                            </span>
                        </template>
                    </Column>

                    <!-- Amount -->
                    <Column field="total_amount" header="Amount" style="width: 130px">
                        <template #body="{ data }">
                            <span class="font-semibold text-gray-800">{{ formatAmount(data.total_amount) }}</span>
                        </template>
                    </Column>

                    <!-- Status -->
                    <Column field="status" header="Status" style="width: 120px">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status_badge?.label ?? data.status"
                                :severity="getSeverity(data.status)"
                                class="capitalize text-xs"
                            />
                        </template>
                    </Column>

                    <!-- Actions -->
                    <Column header="" style="width: 80px">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-eye"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                v-tooltip.top="'View'"
                                @click="router.visit(route('tenant.purchase-orders.show', data.id))"
                            />
                        </template>
                    </Column>

                    <!-- Empty state -->
                    <template #empty>
                        <div class="text-center py-16 text-gray-400">
                            <Package :size="40" class="mx-auto mb-3 opacity-30" />
                            <p class="font-medium text-gray-500">No purchase orders yet</p>
                            <p class="text-sm mt-1">Create a PO from an approved requisition</p>
                            <Button
                                label="Create Purchase Order"
                                size="small"
                                class="mt-4"
                                @click="router.visit(route('tenant.purchase-orders.create'))"
                            />
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div v-if="purchaseOrders.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
                    <span>
                        Showing {{ purchaseOrders.from }}–{{ purchaseOrders.to }} of {{ purchaseOrders.total }}
                    </span>
                    <div class="flex gap-1">
                        <Button
                            v-for="p in purchaseOrders.last_page"
                            :key="p"
                            :label="String(p)"
                            size="small"
                            :severity="p === purchaseOrders.current_page ? 'primary' : 'secondary'"
                            text
                            @click="goToPage(p)"
                        />
                    </div>
                </div>
            </div>

        </div>
    </TenantLayout>
</template>