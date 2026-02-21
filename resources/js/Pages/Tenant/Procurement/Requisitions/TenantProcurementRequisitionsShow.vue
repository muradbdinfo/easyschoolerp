<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import TenantLayout  from '@/Layouts/TenantLayout.vue';
import Card          from 'primevue/card';
import Button        from 'primevue/button';
import Tag           from 'primevue/tag';
import Divider       from 'primevue/divider';
import DataTable     from 'primevue/datatable';
import Column        from 'primevue/column';
import Textarea      from 'primevue/textarea';
import ConfirmDialog from 'primevue/confirmdialog';
import Timeline      from 'primevue/timeline';

import {
    ArrowLeft,
    Building2,
    GitBranch,
    CalendarDays,
    AlertTriangle,
    FileText,
    Package,
    User,
    Clock,
    CheckCircle2,
    XCircle,
    Pencil,
    Trash2,
    Download,
    Send,
    ThumbsUp,
    ThumbsDown,
} from 'lucide-vue-next';

// ─────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────
const props = defineProps({
    requisition: { type: Object,  required: true },
    canApprove:  { type: Boolean, default: false },
});

const toast   = useToast();
const confirm = useConfirm();

// ─────────────────────────────────────────────
// Status & priority display helpers
// ─────────────────────────────────────────────
const statusConfig = {
    draft:           { severity: 'secondary', label: 'Draft',             icon: Pencil        },
    submitted:       { severity: 'info',      label: 'Submitted',         icon: Send          },
    pending_level_1: { severity: 'warning',   label: 'Pending Dept Head', icon: Clock         },
    pending_level_2: { severity: 'warning',   label: 'Pending VP',        icon: Clock         },
    pending_level_3: { severity: 'warning',   label: 'Pending Board',     icon: Clock         },
    approved:        { severity: 'success',   label: 'Approved',          icon: CheckCircle2  },
    rejected:        { severity: 'danger',    label: 'Rejected',          icon: XCircle       },
    cancelled:       { severity: 'secondary', label: 'Cancelled',         icon: XCircle       },
};

const getStatus = (s) => statusConfig[s] ?? { severity: 'secondary', label: s ?? '—', icon: FileText };

const priorityConfig = {
    low:    { class: 'bg-gray-100 text-gray-600',     label: 'Low'    },
    medium: { class: 'bg-blue-100 text-blue-700',     label: 'Medium' },
    high:   { class: 'bg-orange-100 text-orange-700', label: 'High'   },
    urgent: { class: 'bg-red-100 text-red-700 font-bold', label: 'Urgent' },
};
const getPriority = (p) => priorityConfig[p] ?? { class: 'bg-gray-100 text-gray-600', label: p ?? '—' };

// ─────────────────────────────────────────────
// Computed helpers
// ─────────────────────────────────────────────
const totalAmount = computed(() =>
    (props.requisition.items ?? []).reduce(
        (sum, i) => sum + Number(i.quantity) * Number(i.estimated_unit_price), 0
    )
);

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatAmount = (v) =>
    Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const isEditable = computed(() => props.requisition.status === 'draft');
const isDraft    = computed(() => props.requisition.status === 'draft');

// ─────────────────────────────────────────────
// Approval timeline data
// ─────────────────────────────────────────────
const timeline = computed(() => {
    const r   = props.requisition;
    const evt = [];

    evt.push({
        label:    'Created',
        date:     formatDate(r.created_at),
        by:       r.user?.name ?? '—',
        status:   'done',
        icon:     'pi pi-file',
    });

    if (r.level_1_approved_at || r.status === 'pending_level_1') {
        evt.push({
            label:  'Dept Head Review',
            date:   r.level_1_approved_at ? formatDate(r.level_1_approved_at) : 'Pending',
            by:     r.level1Approver?.name ?? '—',
            status: r.level_1_approved_at ? 'done' : (r.status === 'pending_level_1' ? 'active' : 'pending'),
            icon:   r.level_1_approved_at ? 'pi pi-check' : 'pi pi-clock',
        });
    }
    if (r.level_2_approved_at || r.status === 'pending_level_2') {
        evt.push({
            label:  'VP Review',
            date:   r.level_2_approved_at ? formatDate(r.level_2_approved_at) : 'Pending',
            by:     r.level2Approver?.name ?? '—',
            status: r.level_2_approved_at ? 'done' : (r.status === 'pending_level_2' ? 'active' : 'pending'),
            icon:   r.level_2_approved_at ? 'pi pi-check' : 'pi pi-clock',
        });
    }
    if (r.level_3_approved_at || r.status === 'pending_level_3') {
        evt.push({
            label:  'Board Review',
            date:   r.level_3_approved_at ? formatDate(r.level_3_approved_at) : 'Pending',
            by:     r.level3Approver?.name ?? '—',
            status: r.level_3_approved_at ? 'done' : (r.status === 'pending_level_3' ? 'active' : 'pending'),
            icon:   r.level_3_approved_at ? 'pi pi-check' : 'pi pi-clock',
        });
    }
    if (r.status === 'approved') {
        evt.push({
            label:  'Approved',
            date:   formatDate(r.final_approved_at),
            by:     r.finalApprover?.name ?? '—',
            status: 'done',
            icon:   'pi pi-verified',
        });
    }
    if (r.status === 'rejected') {
        evt.push({
            label:  'Rejected',
            date:   formatDate(r.rejected_at),
            by:     r.rejectedBy?.name ?? '—',
            status: 'rejected',
            icon:   'pi pi-times',
        });
    }

    return evt;
});

// ─────────────────────────────────────────────
// Approve / Reject
// ─────────────────────────────────────────────
const approvalNote  = ref('');
const isProcessing  = ref(false);

const approveRequisition = () => {
    if (isProcessing.value) return;
    confirm.require({
        message: 'Approve this purchase requisition?',
        header:  'Confirm Approval',
        icon:    'pi pi-check-circle',
        accept: () => {
            isProcessing.value = true;
            router.post(
                route('tenant.requisitions.approve', props.requisition.id),
                { notes: approvalNote.value },
                {
                    onSuccess: () => {
                        isProcessing.value = false;
                        toast.add({ severity: 'success', summary: 'Approved', detail: 'Requisition approved successfully', life: 3000 });
                    },
                    onError: () => {
                        isProcessing.value = false;
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to approve', life: 3000 });
                    },
                }
            );
        },
        reject: () => { isProcessing.value = false; },
    });
};

const rejectRequisition = () => {
    if (isProcessing.value) return;
    if (!approvalNote.value?.trim()) {
        toast.add({ severity: 'warn', summary: 'Note Required', detail: 'Please provide a reason for rejection', life: 3000 });
        return;
    }
    confirm.require({
        message:     'Reject this purchase requisition?',
        header:      'Confirm Rejection',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            isProcessing.value = true;
            router.post(
                route('tenant.requisitions.reject', props.requisition.id),
                { notes: approvalNote.value },
                {
                    onSuccess: () => {
                        isProcessing.value = false;
                        toast.add({ severity: 'info', summary: 'Rejected', detail: 'Requisition has been rejected', life: 3000 });
                    },
                    onError: () => {
                        isProcessing.value = false;
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to reject', life: 3000 });
                    },
                }
            );
        },
        reject: () => { isProcessing.value = false; },
    });
};

// ─────────────────────────────────────────────
// Delete draft
// ─────────────────────────────────────────────
const deleteDraft = () => {
    confirm.require({
        message:     `Permanently delete "${props.requisition.pr_number}"? This cannot be undone.`,
        header:      'Delete Requisition',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.requisitions.destroy', props.requisition.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Requisition deleted', life: 3000 }),
                onError:   () => toast.add({ severity: 'error',   summary: 'Error',   detail: 'Failed to delete',   life: 3000 }),
            });
        },
    });
};
</script>

<template>
    <TenantLayout :title="`PR — ${requisition.pr_number}`">
        <div class="p-6 space-y-6">

            <!-- ── Top bar ─────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        severity="secondary"
                        text
                        rounded
                        @click="router.visit(route('tenant.requisitions.index'))"
                    />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ requisition.pr_number }}
                        </h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Created by <span class="font-medium text-gray-700">{{ requisition.user?.name ?? '—' }}</span>
                            &nbsp;·&nbsp; {{ formatDate(requisition.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Status + action buttons -->
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
                    <Tag
                        :value="getStatus(requisition.status).label"
                        :severity="getStatus(requisition.status).severity"
                        class="text-sm px-3 py-1"
                    />
                    <Button
                        v-if="isEditable"
                        label="Edit"
                        icon="pi pi-pencil"
                        severity="secondary"
                        outlined
                        size="small"
                        @click="router.visit(route('tenant.requisitions.edit', requisition.id))"
                    />
                    <Button
                        v-if="isEditable"
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        size="small"
                        @click="deleteDraft"
                        v-tooltip.top="'Delete draft'"
                    />
                </div>
            </div>

            <!-- ── Main grid: left (details) + right (timeline) ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT — 2/3 width -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic information card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <FileText :size="18" class="text-blue-600" />
                                <span>Requisition Details</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">

                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide mb-1">Department</p>
                                    <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                        <Building2 :size="14" class="text-gray-400" />
                                        {{ requisition.department?.name ?? '—' }}
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide mb-1">Branch</p>
                                    <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                        <GitBranch :size="14" class="text-gray-400" />
                                        {{ requisition.branch?.name ?? '—' }}
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide mb-1">Required By</p>
                                    <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                        <CalendarDays :size="14" class="text-gray-400" />
                                        {{ formatDate(requisition.required_by_date) }}
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide mb-1">Priority</p>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold capitalize', getPriority(requisition.priority).class]">
                                        {{ getPriority(requisition.priority).label }}
                                    </span>
                                </div>

                            </div>
                        </template>
                    </Card>

                    <!-- Items card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <Package :size="18" class="text-blue-600" />
                                <span>Items ({{ (requisition.items ?? []).length }})</span>
                            </div>
                        </template>
                        <template #content>
                            <DataTable
                                :value="requisition.items ?? []"
                                size="small"
                                class="text-sm"
                                :pt="{ thead: { class: 'bg-gray-50' } }"
                            >
                                <template #empty>
                                    <div class="text-center py-8 text-gray-400">
                                        <Package :size="36" class="mx-auto mb-2 opacity-30" />
                                        <p>No items on this requisition.</p>
                                    </div>
                                </template>

                                <Column field="item_code" header="Code"  style="width:12%" />
                                <Column field="item_name" header="Item"  style="width:28%" />
                                <Column field="unit"      header="Unit"  style="width:8%"  />

                                <Column header="Qty" style="width:10%" class="text-right">
                                    <template #body="{ data }">
                                        {{ Number(data.quantity).toFixed(2) }}
                                    </template>
                                </Column>

                                <Column header="Unit Price" style="width:15%" class="text-right">
                                    <template #body="{ data }">
                                        {{ formatAmount(data.estimated_unit_price) }}
                                    </template>
                                </Column>

                                <Column header="Total (BDT)" style="width:15%" class="text-right font-semibold">
                                    <template #body="{ data }">
                                        {{ formatAmount(Number(data.quantity) * Number(data.estimated_unit_price)) }}
                                    </template>
                                </Column>

                                <Column header="Specs" style="width:12%">
                                    <template #body="{ data }">
                                        <span class="text-gray-500 text-xs truncate">{{ data.specifications || '—' }}</span>
                                    </template>
                                </Column>
                            </DataTable>

                            <!-- Grand total row -->
                            <div class="flex justify-end mt-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-3 text-right">
                                    <p class="text-xs text-gray-500 mb-1">Grand Total</p>
                                    <p class="text-2xl font-bold text-blue-700">{{ formatAmount(totalAmount) }} BDT</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Justification card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <FileTextIcon :size="18" class="text-blue-600" />
                                <span>Justification</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <p class="font-semibold text-gray-500 mb-1">Purpose</p>
                                    <p class="whitespace-pre-wrap text-gray-800 bg-gray-50 rounded-lg p-3 leading-relaxed">
                                        {{ requisition.purpose || '—' }}
                                    </p>
                                </div>

                                <div v-if="requisition.justification">
                                    <p class="font-semibold text-gray-500 mb-1">Additional Details</p>
                                    <p class="whitespace-pre-wrap text-gray-800 bg-gray-50 rounded-lg p-3 leading-relaxed">
                                        {{ requisition.justification }}
                                    </p>
                                </div>

                                <div v-if="requisition.notes">
                                    <p class="font-semibold text-gray-500 mb-1">Internal Notes</p>
                                    <p class="whitespace-pre-wrap text-gray-700 bg-yellow-50 border border-yellow-100 rounded-lg p-3 text-xs">
                                        {{ requisition.notes }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Attachments card -->
                    <Card v-if="(requisition.attachments ?? []).length > 0">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-paperclip text-blue-600" />
                                <span>Attachments ({{ requisition.attachments.length }})</span>
                            </div>
                        </template>
                        <template #content>
                            <ul class="space-y-2">
                                <li v-for="(att, idx) in requisition.attachments" :key="idx"
                                    class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 text-sm hover:bg-gray-100 transition"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="pi pi-file text-gray-400 flex-shrink-0" />
                                        <span class="truncate text-gray-700">{{ att.name }}</span>
                                        <span class="text-gray-400 text-xs flex-shrink-0">({{ (att.size / 1024).toFixed(1) }} KB)</span>
                                    </div>
                                    <a
                                        :href="`/storage/${att.path}`"
                                        target="_blank"
                                        class="flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium flex-shrink-0 ml-3"
                                    >
                                        <Download :size="13" /> Download
                                    </a>
                                </li>
                            </ul>
                        </template>
                    </Card>

                    <!-- ── Approval action card (shown only to approvers) ── -->
                    <Card v-if="canApprove" class="border-2 border-blue-200 bg-blue-50/30">
                        <template #title>
                            <div class="flex items-center gap-2 text-base text-blue-700">
                                <CheckCircle2 :size="18" />
                                <span>Approval Action</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <label class="font-semibold text-sm block mb-1">
                                        Notes / Remarks
                                        <span class="text-gray-400 font-normal">(required for rejection)</span>
                                    </label>
                                    <Textarea
                                        v-model="approvalNote"
                                        :rows="3"
                                        placeholder="Add a note for the requester…"
                                        class="w-full"
                                        autoResize
                                    />
                                </div>
                                <div class="flex gap-3">
                                    <Button
                                        label="Approve"
                                        icon="pi pi-check"
                                        severity="success"
                                        class="flex-1"
                                        :loading="isProcessing"
                                        :disabled="isProcessing"
                                        @click="approveRequisition"
                                    />
                                    <Button
                                        label="Reject"
                                        icon="pi pi-times"
                                        severity="danger"
                                        outlined
                                        class="flex-1"
                                        :loading="isProcessing"
                                        :disabled="isProcessing"
                                        @click="rejectRequisition"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>

                </div>

                <!-- RIGHT — 1/3 width: Approval timeline + meta -->
                <div class="space-y-6">

                    <!-- Approval progress -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <Clock :size="18" class="text-blue-600" />
                                <span>Approval Progress</span>
                            </div>
                        </template>
                        <template #content>
                            <div v-if="timeline.length === 0" class="text-center text-gray-400 py-6 text-sm">
                                Not yet submitted for approval.
                            </div>
                            <div v-else class="relative">
                                <div v-for="(evt, idx) in timeline" :key="idx" class="flex gap-3 mb-5 last:mb-0">
                                    <!-- dot + line -->
                                    <div class="flex flex-col items-center">
                                        <div :class="[
                                            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs',
                                            evt.status === 'done'     ? 'bg-green-100 text-green-600 border-2 border-green-400' :
                                            evt.status === 'active'   ? 'bg-blue-100 text-blue-600 border-2 border-blue-400 animate-pulse' :
                                            evt.status === 'rejected' ? 'bg-red-100 text-red-600 border-2 border-red-400' :
                                                                         'bg-gray-100 text-gray-400 border-2 border-gray-200'
                                        ]">
                                            <i :class="evt.icon" />
                                        </div>
                                        <div v-if="idx < timeline.length - 1" class="w-px flex-1 bg-gray-200 mt-1" />
                                    </div>
                                    <!-- content -->
                                    <div class="pb-1 pt-1">
                                        <p class="font-semibold text-sm text-gray-800">{{ evt.label }}</p>
                                        <p class="text-xs text-gray-500">{{ evt.date }}</p>
                                        <p v-if="evt.by && evt.by !== '—'" class="text-xs text-gray-400">by {{ evt.by }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection reason -->
                            <div v-if="requisition.status === 'rejected' && requisition.rejection_reason"
                                class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm"
                            >
                                <p class="font-semibold text-red-700 mb-1">Rejection Reason</p>
                                <p class="text-red-600 text-xs">{{ requisition.rejection_reason }}</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Meta info card -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <User :size="18" class="text-blue-600" />
                                <span>Details</span>
                            </div>
                        </template>
                        <template #content>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">PR Number</dt>
                                    <dd class="font-semibold text-gray-800">{{ requisition.pr_number }}</dd>
                                </div>
                                <Divider class="my-1" />
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Created By</dt>
                                    <dd class="font-medium text-gray-700">{{ requisition.user?.name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Created On</dt>
                                    <dd class="text-gray-700">{{ formatDate(requisition.created_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-700">{{ formatDate(requisition.updated_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">PR Date</dt>
                                    <dd class="text-gray-700">{{ formatDate(requisition.pr_date) }}</dd>
                                </div>
                                <Divider class="my-1" />
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Status</dt>
                                    <dd>
                                        <Tag
                                            :value="getStatus(requisition.status).label"
                                            :severity="getStatus(requisition.status).severity"
                                            class="text-xs"
                                        />
                                    </dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Priority</dt>
                                    <dd>
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold capitalize', getPriority(requisition.priority).class]">
                                            {{ getPriority(requisition.priority).label }}
                                        </span>
                                    </dd>
                                </div>
                                <Divider class="my-1" />
                                <div class="flex justify-between font-bold">
                                    <dt class="text-gray-700">Total Amount</dt>
                                    <dd class="text-blue-700 text-base">{{ formatAmount(totalAmount) }} BDT</dd>
                                </div>
                            </dl>
                        </template>
                    </Card>

                </div>
            </div>

        </div>

        <ConfirmDialog />

    </TenantLayout>
</template>