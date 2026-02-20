<script setup>
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast }   from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout  from '@/Layouts/TenantLayout.vue';
import Button        from 'primevue/button';
import Tag           from 'primevue/tag';
import DataTable     from 'primevue/datatable';
import Column        from 'primevue/column';
import Divider       from 'primevue/divider';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast         from 'primevue/toast';
import {
    ArrowLeft, Package, Send, Truck, Pencil,
    Trash2, Building2, Users, CalendarDays,
    FileText, CheckCircle2, XCircle, Clock,
    DollarSign, ExternalLink, AlertTriangle,
} from 'lucide-vue-next';

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    purchaseOrder: { type: Object, required: true },
});

const toast   = useToast();
const confirm = useConfirm();
const page    = usePage();

// Flash — use onMounted so Toast service is ready
onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({ severity: 'success', summary: 'Success', detail: page.props.flash.success, life: 4000 });
    }
    if (page.props.flash?.error) {
        toast.add({ severity: 'error', summary: 'Error', detail: page.props.flash.error, life: 6000 });
    }
});

// ── Helpers ────────────────────────────────────────────────────────────────────
const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatAmount = (v) =>
    '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });

const statusConfig = {
    draft:        { severity: 'secondary', label: 'Draft',        icon: FileText     },
    sent:         { severity: 'info',      label: 'Sent',         icon: Send         },
    acknowledged: { severity: 'warning',   label: 'Acknowledged', icon: Clock        },
    partial:      { severity: 'warning',   label: 'Partial',      icon: Truck        },
    received:     { severity: 'success',   label: 'Received',     icon: CheckCircle2 },
    closed:       { severity: 'success',   label: 'Closed',       icon: CheckCircle2 },
    cancelled:    { severity: 'danger',    label: 'Cancelled',    icon: XCircle      },
};

const getStatus = (s) => statusConfig[s] ?? { severity: 'info', label: s, icon: FileText };

// ── Send to vendor ─────────────────────────────────────────────────────────────
const sending = ref(false);

const sendToVendor = () => {
    confirm.require({
        message: `Send PO ${props.purchaseOrder.po_number} to ${props.purchaseOrder.vendor?.name}?`,
        header:  'Send Purchase Order',
        icon:    'pi pi-send',
        accept: () => {
            sending.value = true;
            router.post(route('tenant.purchase-orders.send', props.purchaseOrder.id), {}, {
                onSuccess: () => {
                    sending.value = false;
                    toast.add({ severity: 'success', summary: 'Sent', detail: 'PO sent to vendor', life: 3000 });
                },
                onError: (e) => {
                    sending.value = false;
                    const msg = Object.values(e)[0] ?? 'Failed to send';
                    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
                },
            });
        },
    });
};

// ── Cancel PO ─────────────────────────────────────────────────────────────────
const cancelling = ref(false);

const cancelPO = () => {
    confirm.require({
        message:     'Cancel this purchase order? This cannot be undone.',
        header:      'Cancel Purchase Order',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            cancelling.value = true;
            router.post(route('tenant.purchase-orders.cancel', props.purchaseOrder.id), {}, {
                onSuccess: () => {
                    cancelling.value = false;
                    toast.add({ severity: 'info', summary: 'Cancelled', detail: 'PO cancelled', life: 3000 });
                },
                onFinish: () => { cancelling.value = false; },
            });
        },
    });
};

// ── Delete draft ──────────────────────────────────────────────────────────────
const deletePO = () => {
    confirm.require({
        message:     `Delete draft ${props.purchaseOrder.po_number}? This cannot be undone.`,
        header:      'Delete Purchase Order',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.purchase-orders.destroy', props.purchaseOrder.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Deleted', detail: 'PO deleted', life: 3000 });
                },
            });
        },
    });
};

const po = props.purchaseOrder;
const isDraft     = po.status === 'draft';
const isSent      = po.status === 'sent' || po.status === 'partial';
const isCancelled = po.status === 'cancelled';
const canReceive  = ['sent', 'partial', 'acknowledged'].includes(po.status);
</script>

<template>
    <TenantLayout :title="po.po_number">
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="p-6 space-y-6">

            <!-- ── Top bar ─────────────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text rounded
                        @click="router.visit(route('tenant.purchase-orders.index'))"
                    />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ po.po_number }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Created by
                            <span class="font-medium text-gray-700">{{ po.creator?.name ?? '—' }}</span>
                            · {{ formatDate(po.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <Tag
                        :value="getStatus(po.status).label"
                        :severity="getStatus(po.status).severity"
                        class="text-sm px-3"
                    />

                    <!-- Edit (draft only) -->
                    <Button
                        v-if="isDraft"
                        label="Edit"
                        icon="pi pi-pencil"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="router.visit(route('tenant.purchase-orders.edit', po.id))"
                    />

                    <!-- Send to vendor (draft only, needs vendor email) -->
                    <Button
                        v-if="isDraft"
                        label="Send to Vendor"
                        severity="info"
                        size="small"
                        :loading="sending"
                        @click="sendToVendor"
                    >
                        <template #icon><Send :size="14" class="mr-1.5" /></template>
                    </Button>

                    <!-- Receive Goods -->
                    <Button
                        v-if="canReceive"
                        label="Receive Goods"
                        severity="success"
                        size="small"
                        @click="router.visit(route('tenant.grn.create', { po_id: po.id }))"
                    >
                        <template #icon><Truck :size="14" class="mr-1.5" /></template>
                    </Button>

                    <!-- Cancel -->
                    <Button
                        v-if="!isCancelled && !['received','closed'].includes(po.status)"
                        icon="pi pi-times"
                        severity="danger"
                        outlined
                        size="small"
                        v-tooltip.top="'Cancel PO'"
                        :loading="cancelling"
                        @click="cancelPO"
                    />

                    <!-- Delete (draft only) -->
                    <Button
                        v-if="isDraft"
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        size="small"
                        v-tooltip.top="'Delete draft'"
                        @click="deletePO"
                    />
                </div>
            </div>

            <!-- ── Cancelled warning ───────────────────────────────────────── -->
            <div v-if="isCancelled"
                class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3 text-red-700">
                <AlertTriangle :size="18" class="flex-shrink-0" />
                <p class="text-sm font-medium">This purchase order has been cancelled.</p>
            </div>

            <!-- ── Sent info ───────────────────────────────────────────────── -->
            <div v-if="po.sent_at"
                class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3 text-blue-700">
                <Send :size="18" class="flex-shrink-0" />
                <p class="text-sm">
                    Sent to vendor on <strong>{{ formatDate(po.sent_at) }}</strong>
                    <span v-if="po.sender"> by {{ po.sender.name }}</span>.
                    Awaiting acknowledgement.
                </p>
            </div>

            <!-- ── Main grid ───────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT 2/3 -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Order details card -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <Package :size="17" class="text-blue-600" /> Order Details
                        </h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Vendor</p>
                                <p class="font-semibold text-gray-800">{{ po.vendor?.name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ po.vendor?.code }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Branch</p>
                                <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                    <Building2 :size="13" class="text-gray-400" />
                                    {{ po.branch?.name ?? '—' }}
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">PO Date</p>
                                <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                    <CalendarDays :size="13" class="text-gray-400" />
                                    {{ formatDate(po.po_date) }}
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Expected Delivery</p>
                                <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                    <Truck :size="13" class="text-gray-400" />
                                    {{ formatDate(po.expected_delivery_date) }}
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Payment Terms</p>
                                <p class="font-semibold text-gray-800">{{ po.payment_terms ?? '—' }}</p>
                            </div>
                            <div v-if="po.requisition" class="bg-blue-50 rounded-lg p-3">
                                <p class="text-xs text-blue-400 font-semibold uppercase tracking-wide mb-1">From PR</p>
                                <button
                                    class="font-semibold text-blue-700 hover:underline flex items-center gap-1 text-sm"
                                    @click="router.visit(route('tenant.requisitions.show', po.requisition.id))"
                                >
                                    <FileText :size="13" />
                                    {{ po.requisition.pr_number }}
                                    <ExternalLink :size="11" />
                                </button>
                            </div>
                        </div>

                        <!-- Delivery address -->
                        <div v-if="po.delivery_address" class="mt-4 bg-gray-50 rounded-lg p-3 text-sm">
                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Delivery Address</p>
                            <p class="text-gray-700">{{ po.delivery_address }}</p>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <Package :size="17" class="text-blue-600" />
                            <h2 class="text-base font-semibold text-gray-800">Items</h2>
                            <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5 ml-1">
                                {{ po.items?.length ?? 0 }} items
                            </span>
                        </div>
                        <DataTable :value="po.items ?? []" class="text-sm" tableStyle="min-width: 500px">
                            <Column field="item_name" header="Item">
                                <template #body="{ data }">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ data.item_name }}</p>
                                        <p v-if="data.specifications" class="text-xs text-gray-400 mt-0.5">{{ data.specifications }}</p>
                                    </div>
                                </template>
                            </Column>
                            <Column field="unit" header="Unit" style="width:80px" />
                            <Column field="quantity" header="Ordered" style="width:90px; text-align:right">
                                <template #body="{ data }">
                                    <span class="font-medium">{{ Number(data.quantity) }}</span>
                                </template>
                            </Column>
                            <Column field="received_quantity" header="Received" style="width:90px; text-align:right">
                                <template #body="{ data }">
                                    <span :class="Number(data.received_quantity) > 0 ? 'text-green-600 font-medium' : 'text-gray-400'">
                                        {{ Number(data.received_quantity ?? 0) }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="unit_price" header="Unit Price" style="width:110px; text-align:right">
                                <template #body="{ data }">
                                    {{ formatAmount(data.unit_price) }}
                                </template>
                            </Column>
                            <Column header="Total" style="width:120px; text-align:right">
                                <template #body="{ data }">
                                    <span class="font-semibold text-gray-800">
                                        {{ formatAmount(Number(data.quantity) * Number(data.unit_price)) }}
                                    </span>
                                </template>
                            </Column>
                            <template #empty>
                                <div class="text-center py-8 text-gray-400 text-sm">No items</div>
                            </template>
                        </DataTable>
                    </div>

                    <!-- Terms & Notes -->
                    <div v-if="po.terms_conditions || po.notes"
                        class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <div v-if="po.terms_conditions">
                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Terms & Conditions</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ po.terms_conditions }}</p>
                        </div>
                        <div v-if="po.notes">
                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Internal Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ po.notes }}</p>
                        </div>
                    </div>

                </div>

                <!-- RIGHT 1/3 -->
                <div class="space-y-6">

                    <!-- Financial summary -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <DollarSign :size="17" class="text-blue-600" /> Financial Summary
                        </h2>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <dt>Subtotal</dt>
                                <dd class="font-medium">{{ formatAmount(po.subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <dt>VAT ({{ po.vat_percentage }}%)</dt>
                                <dd class="font-medium">{{ formatAmount(po.vat_amount) }}</dd>
                            </div>
                            <div v-if="Number(po.freight_charges) > 0" class="flex justify-between text-gray-600">
                                <dt>Freight</dt>
                                <dd class="font-medium">{{ formatAmount(po.freight_charges) }}</dd>
                            </div>
                            <div v-if="Number(po.discount_amount) > 0" class="flex justify-between text-green-600">
                                <dt>Discount</dt>
                                <dd class="font-medium">− {{ formatAmount(po.discount_amount) }}</dd>
                            </div>
                            <Divider class="my-1" />
                            <div class="flex justify-between font-bold text-base">
                                <dt class="text-gray-800">Grand Total</dt>
                                <dd class="text-blue-700">{{ formatAmount(po.total_amount) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Vendor info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <Users :size="17" class="text-blue-600" /> Vendor Details
                        </h2>
                        <dl class="space-y-2 text-sm">
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Name</dt>
                                <dd class="font-semibold text-gray-800 mt-0.5">{{ po.vendor?.name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Code</dt>
                                <dd class="text-gray-600 mt-0.5">{{ po.vendor?.code ?? '—' }}</dd>
                            </div>
                            <div v-if="po.vendor?.email">
                                <dt class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Email</dt>
                                <dd class="text-gray-600 mt-0.5 break-all">{{ po.vendor.email }}</dd>
                            </div>
                            <div v-if="po.vendor?.phone">
                                <dt class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Phone</dt>
                                <dd class="text-gray-600 mt-0.5">{{ po.vendor.phone }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Status timeline -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <Clock :size="17" class="text-blue-600" /> Timeline
                        </h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <CheckCircle2 :size="14" class="text-green-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Created</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(po.created_at) }} by {{ po.creator?.name ?? '—' }}</p>
                                </div>
                            </div>
                            <div v-if="po.sent_at" class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <Send :size="14" class="text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Sent to Vendor</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(po.sent_at) }}</p>
                                </div>
                            </div>
                            <div v-if="!po.sent_at && isDraft" class="flex items-start gap-3 opacity-40">
                                <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <Send :size="14" class="text-gray-400" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-500">Pending: Send to Vendor</p>
                                </div>
                            </div>
                            <div v-if="canReceive || po.status === 'received'" class="flex items-start gap-3">
                                <div :class="['w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0',
                                    po.status === 'received' ? 'bg-green-100' : 'bg-orange-50']">
                                    <Truck :size="14" :class="po.status === 'received' ? 'text-green-600' : 'text-orange-400'" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">
                                        {{ po.status === 'received' ? 'Goods Received' : 'Awaiting Delivery' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </TenantLayout>
</template>