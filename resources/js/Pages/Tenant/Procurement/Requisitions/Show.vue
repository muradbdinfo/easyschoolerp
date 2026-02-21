<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
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

import {
    ArrowLeft, Building2, GitBranch, CalendarDays,
    FileText, Package, User, Clock,
    CheckCircle2, XCircle, Pencil, Trash2, Download, Send,
} from 'lucide-vue-next';

// ── Props ─────────────────────────────────────────────────────────────────
const props = defineProps({
    requisition: { type: Object,  required: true },
    canApprove:  { type: Boolean, default: false },
});

const toast   = useToast();
const confirm = useConfirm();

// ── Status config — all 5 levels with real role labels ───────────────────
const statusConfig = {
    draft:            { severity: 'secondary', label: 'Draft'                      },
    submitted:        { severity: 'info',      label: 'Submitted'                  },
    pending_level_1:  { severity: 'warning',   label: 'Pending: PO Staff'          },
    pending_level_2:  { severity: 'warning',   label: 'Pending: PO Officer'        },
    pending_level_3:  { severity: 'warning',   label: 'Pending: Admin Officer'     },
    pending_level_4:  { severity: 'warning',   label: 'Pending: Director Admin'    },
    pending_level_5:  { severity: 'warning',   label: 'Pending: MD / DMD'          },
    approved:         { severity: 'success',   label: 'Approved'                   },
    rejected:         { severity: 'danger',    label: 'Rejected'                   },
    cancelled:        { severity: 'secondary', label: 'Cancelled'                  },
    closed:           { severity: 'secondary', label: 'Closed (PO Created)'        },
};
const getStatus = (s) => statusConfig[s] ?? { severity: 'secondary', label: s ?? '—' };

const priorityConfig = {
    low:    { class: 'bg-gray-100 text-gray-600',         label: 'Low'    },
    medium: { class: 'bg-blue-100 text-blue-700',         label: 'Medium' },
    high:   { class: 'bg-orange-100 text-orange-700',     label: 'High'   },
    urgent: { class: 'bg-red-100 text-red-700 font-bold', label: 'Urgent' },
};
const getPriority = (p) => priorityConfig[p] ?? { class: 'bg-gray-100 text-gray-600', label: p ?? '—' };

// ── Helpers ───────────────────────────────────────────────────────────────
const formatDate   = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const formatAmount = (v) => Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Use DB value — already calculated correctly in controller
const totalAmount = computed(() => Number(props.requisition.total_amount ?? 0));

const isEditable = computed(() => props.requisition.status === 'draft');

// ── Approval timeline ─────────────────────────────────────────────────────
//
// Each step definition:  [ levelN_approver_id key, approved_at key, pending_status, label ]
const STEPS = [
    { n: null,  approvedAt: 'created_at',       pendingStatus: null,              label: 'Created by Requester',        approverKey: 'user'            },
    { n: 1,     approvedAt: 'level_1_approved_at', pendingStatus: 'pending_level_1', label: 'Department Head Check',           approverKey: 'level1Approver'  },
    { n: 2,     approvedAt: 'level_2_approved_at', pendingStatus: 'pending_level_2', label: 'PO Officer Review',        approverKey: 'level2Approver'  },
    { n: 3,     approvedAt: 'level_3_approved_at', pendingStatus: 'pending_level_3', label: 'Admin Officer Validation', approverKey: 'level3Approver'  },
    { n: 4,     approvedAt: 'level_4_approved_at', pendingStatus: 'pending_level_4', label: 'Director Admin Approval',  approverKey: 'level4Approver'  },
    { n: 5,     approvedAt: 'level_5_approved_at', pendingStatus: 'pending_level_5', label: 'MD / DMD Final Approval',  approverKey: 'level5Approver'  },
];

const timeline = computed(() => {
    const r      = props.requisition;
    const status = r.status;
    const result = [];

    STEPS.forEach((step) => {
        // "Created" row — always show
        if (step.n === null) {
            result.push({
                label:  step.label,
                date:   formatDate(r.created_at),
                by:     r.user?.name ?? '—',
                status: 'done',
            });
            return;
        }

        const approvedAt = r[step.approvedAt];
        const approver   = r[step.approverKey];
        const levelStatus = r[`level_${step.n}_status`]; // 'pending'|'approved'|'rejected'|null

        // Only show a level if it was assigned (approver exists) or is currently active
        const isAssigned = !!r[`level_${step.n}_approver_id`];
        const isActive   = status === step.pendingStatus;
        if (!isAssigned && !isActive) return;

        let rowStatus;
        if (levelStatus === 'approved')      rowStatus = 'done';
        else if (levelStatus === 'rejected') rowStatus = 'rejected';
        else if (isActive)                   rowStatus = 'active';
        else                                 rowStatus = 'pending';

        result.push({
            label:  step.label,
            date:   approvedAt ? formatDate(approvedAt) : (isActive ? 'Awaiting action' : 'Pending'),
            by:     approver?.name ?? '—',
            status: rowStatus,
        });
    });

    // Final row
    if (status === 'approved') {
        result.push({
            label:  'Fully Approved',
            date:   formatDate(r.final_approved_at),
            by:     r.finalApprover?.name ?? '—',
            status: 'done',
        });
    }
    if (status === 'rejected') {
        result.push({
            label:  'Rejected',
            date:   formatDate(r.rejected_at),
            by:     r.rejectedBy?.name ?? '—',
            status: 'rejected',
        });
    }

    return result;
});

const dotClass = (s) => ({
    done:     'bg-green-100 text-green-600 border-2 border-green-400',
    active:   'bg-blue-100 text-blue-600 border-2 border-blue-500 animate-pulse',
    rejected: 'bg-red-100 text-red-600 border-2 border-red-400',
    pending:  'bg-gray-100 text-gray-400 border-2 border-gray-200',
}[s] ?? 'bg-gray-100 border-2 border-gray-200');

const dotIcon = (s) => ({
    done:     'pi pi-check',
    active:   'pi pi-clock',
    rejected: 'pi pi-times',
    pending:  'pi pi-minus',
}[s] ?? 'pi pi-minus');

// ── Approve / Reject ──────────────────────────────────────────────────────
const approvalNote = ref('');
const isProcessing = ref(false);

const approveRequisition = () => {
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
                    onSuccess: () => { isProcessing.value = false; },
                    onError:   () => { isProcessing.value = false;
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to approve', life: 3000 });
                    },
                }
            );
        },
    });
};

const rejectRequisition = () => {
    if (!approvalNote.value?.trim()) {
        toast.add({ severity: 'warn', summary: 'Required', detail: 'Please provide a rejection reason', life: 3000 });
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
                    onSuccess: () => { isProcessing.value = false; },
                    onError:   () => { isProcessing.value = false;
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to reject', life: 3000 });
                    },
                }
            );
        },
    });
};

// ── Delete draft ──────────────────────────────────────────────────────────
const deleteDraft = () => {
    confirm.require({
        message:     `Permanently delete "${props.requisition.pr_number}"?`,
        header:      'Delete Requisition',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('tenant.requisitions.destroy', props.requisition.id)),
    });
};
</script>

<template>
    <TenantLayout :title="`PR — ${requisition.pr_number}`">
        <div class="p-6 space-y-6">

            <!-- Top bar -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded
                        @click="router.visit(route('tenant.requisitions.index'))" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ requisition.pr_number }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Created by <span class="font-medium text-gray-700">{{ requisition.user?.name ?? '—' }}</span>
                            &nbsp;·&nbsp; {{ formatDate(requisition.created_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                    <Tag :value="getStatus(requisition.status).label"
                         :severity="getStatus(requisition.status).severity"
                         class="text-sm px-3 py-1" />
                    <Button v-if="isEditable" label="Edit" icon="pi pi-pencil"
                        severity="secondary" outlined size="small"
                        @click="router.visit(route('tenant.requisitions.edit', requisition.id))" />
                    <Button v-if="isEditable" icon="pi pi-trash" severity="danger"
                        outlined size="small" @click="deleteDraft" v-tooltip.top="'Delete draft'" />
                </div>
            </div>

            <!-- Main grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT: details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic info -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <FileText :size="18" class="text-blue-600" />
                                Requisition Details
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

                    <!-- Items -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <Package :size="18" class="text-blue-600" />
                                Items ({{ (requisition.items ?? []).length }})
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="requisition.items ?? []" size="small" class="text-sm">
                                <template #empty>
                                    <div class="text-center py-8 text-gray-400">No items.</div>
                                </template>
                                <Column field="item_code" header="Code"  style="width:12%" />
                                <Column field="item_name" header="Item"  style="width:30%" />
                                <Column field="unit"      header="Unit"  style="width:8%"  />
                                <Column header="Qty" style="width:10%">
                                    <template #body="{ data }">{{ Number(data.quantity).toFixed(2) }}</template>
                                </Column>
                                <Column header="Unit Price" style="width:15%">
                                    <template #body="{ data }">{{ formatAmount(data.estimated_unit_price) }}</template>
                                </Column>
                                <Column header="Total (BDT)" style="width:15%" class="font-semibold">
                                    <template #body="{ data }">
                                        {{ formatAmount(data.estimated_total ?? (Number(data.quantity) * Number(data.estimated_unit_price))) }}
                                    </template>
                                </Column>
                            </DataTable>

                            <div class="flex justify-end mt-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-3 text-right">
                                    <p class="text-xs text-gray-500 mb-1">Grand Total</p>
                                    <p class="text-2xl font-bold text-blue-700">{{ formatAmount(totalAmount) }} BDT</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Justification -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <FileText :size="18" class="text-blue-600" />
                                Justification
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

                    <!-- Attachments -->
                    <Card v-if="(requisition.attachments ?? []).length > 0">
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <i class="pi pi-paperclip text-blue-600" />
                                Attachments ({{ requisition.attachments.length }})
                            </div>
                        </template>
                        <template #content>
                            <ul class="space-y-2">
                                <li v-for="(att, idx) in requisition.attachments" :key="idx"
                                    class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 text-sm"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="pi pi-file text-gray-400 flex-shrink-0" />
                                        <span class="truncate text-gray-700">{{ att.name }}</span>
                                        <span class="text-gray-400 text-xs">({{ (att.size / 1024).toFixed(1) }} KB)</span>
                                    </div>
                                    <a :href="`/storage/${att.path}`" target="_blank"
                                        class="flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-medium ml-3">
                                        <Download :size="13" /> Download
                                    </a>
                                </li>
                            </ul>
                        </template>
                    </Card>

                    <!-- Approval action (approvers only) -->
                    <Card v-if="canApprove" class="border-2 border-blue-200 bg-blue-50/30">
                        <template #title>
                            <div class="flex items-center gap-2 text-base text-blue-700">
                                <CheckCircle2 :size="18" />
                                Your Approval Action
                            </div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <!-- Show current step context -->
                                <div class="bg-white rounded-lg border border-blue-100 p-3 text-sm">
                                    <p class="text-gray-500 text-xs mb-1">Current Step</p>
                                    <p class="font-semibold text-blue-800">
                                        {{ getStatus(requisition.status).label }}
                                    </p>
                                    <p class="text-gray-500 text-xs mt-1">
                                        Total Amount:
                                        <span class="font-bold text-gray-800">{{ formatAmount(totalAmount) }} BDT</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="font-semibold text-sm block mb-1">
                                        Notes / Remarks
                                        <span class="text-gray-400 font-normal">(required for rejection)</span>
                                    </label>
                                    <Textarea v-model="approvalNote" :rows="3"
                                        placeholder="Add a note for the requester…"
                                        class="w-full" autoResize />
                                </div>
                                <div class="flex gap-3">
                                    <Button label="Approve" icon="pi pi-check" severity="success"
                                        class="flex-1" :loading="isProcessing"
                                        @click="approveRequisition" />
                                    <Button label="Reject" icon="pi pi-times" severity="danger"
                                        outlined class="flex-1" :loading="isProcessing"
                                        @click="rejectRequisition" />
                                </div>
                            </div>
                        </template>
                    </Card>

                </div>

                <!-- RIGHT: timeline + meta -->
                <div class="space-y-6">

                    <!-- Approval Progress -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <Clock :size="18" class="text-blue-600" />
                                Approval Progress
                            </div>
                        </template>
                        <template #content>
                            <div v-if="timeline.length === 0" class="text-center text-gray-400 py-6 text-sm">
                                Not yet submitted.
                            </div>
                            <div v-else>
                                <div v-for="(evt, idx) in timeline" :key="idx" class="flex gap-3 mb-5 last:mb-0">
                                    <div class="flex flex-col items-center">
                                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs', dotClass(evt.status)]">
                                            <i :class="dotIcon(evt.status)" />
                                        </div>
                                        <div v-if="idx < timeline.length - 1" class="w-px flex-1 bg-gray-200 mt-1 min-h-4" />
                                    </div>
                                    <div class="pb-1 pt-0.5">
                                        <p class="font-semibold text-sm text-gray-800">{{ evt.label }}</p>
                                        <p class="text-xs text-gray-500">{{ evt.date }}</p>
                                        <p v-if="evt.by && evt.by !== '—'" class="text-xs text-gray-400">by {{ evt.by }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection reason -->
                            <div v-if="requisition.status === 'rejected' && requisition.rejection_reason"
                                class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm">
                                <p class="font-semibold text-red-700 mb-1">Rejection Reason</p>
                                <p class="text-red-600 text-xs">{{ requisition.rejection_reason }}</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Meta -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2 text-base">
                                <User :size="18" class="text-blue-600" />
                                Details
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
                                    <dt class="text-gray-500">PR Date</dt>
                                    <dd class="text-gray-700">{{ formatDate(requisition.pr_date) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Required By</dt>
                                    <dd class="text-gray-700">{{ formatDate(requisition.required_by_date) }}</dd>
                                </div>
                                <Divider class="my-1" />
                                <div class="flex justify-between items-center">
                                    <dt class="text-gray-500">Status</dt>
                                    <dd>
                                        <Tag :value="getStatus(requisition.status).label"
                                             :severity="getStatus(requisition.status).severity"
                                             class="text-xs" />
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