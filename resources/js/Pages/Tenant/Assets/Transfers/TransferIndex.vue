<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Panel from 'primevue/panel';
import ConfirmDialog from 'primevue/confirmdialog';
import { MoveRight, Plus, Eye, Search, CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
    transfers: Object,
    branches:  Array,
    filters:   Object,
});

const toast  = useToast();
const confirm= useConfirm();

// Filters
const search   = ref(props.filters.search ?? '');
const status   = ref(props.filters.status ?? null);
const branchId = ref(props.filters.branch_id ?? null);

const statusOptions = [
    { label: 'Pending',   value: 'pending' },
    { label: 'Approved',  value: 'approved' },
    { label: 'Completed', value: 'completed' },
    { label: 'Rejected',  value: 'rejected' },
];

function applyFilters() {
    router.get(route('tenant.assets.transfers.index'), {
        search:    search.value || undefined,
        status:    status.value || undefined,
        branch_id: branchId.value || undefined,
    }, { preserveState: true, replace: true });
}

// Rejection dialog
const rejectDialog = ref(false);
const rejectingId  = ref(null);
const rejectForm   = useForm({ rejection_reason: '' });

function openReject(transfer) {
    rejectingId.value = transfer.id;
    rejectForm.rejection_reason = '';
    rejectDialog.value = true;
}

function confirmApprove(transfer) {
    confirm.require({
        message: `Approve transfer for ${transfer.asset?.asset_tag}?`,
        header: 'Confirm Approval',
        accept: () => {
            useForm({}).post(route('tenant.assets.transfers.approve', transfer.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Approved', life: 3000 }),
            });
        },
    });
}

function submitReject() {
    rejectForm.post(route('tenant.assets.transfers.reject', rejectingId.value), {
        onSuccess: () => {
            rejectDialog.value = false;
            toast.add({ severity: 'warn', summary: 'Rejected', life: 3000 });
        },
    });
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-BD') : '—';
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Transfers' }]">
        <ConfirmDialog />

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asset Transfers</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Track and manage asset movements</p>
                </div>
                <Link :href="route('tenant.assets.transfers.create')">
                    <Button severity="primary">
                        <Plus :size="16" class="mr-1.5" /> New Transfer
                    </Button>
                </Link>
            </div>

            <Panel :collapsed="false" toggleable>
                <template #header><div class="flex items-center gap-2"><Search :size="16" /><span class="font-medium">Search & Filter</span></div></template>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <InputText v-model="search" placeholder="Transfer # or asset tag..." class="w-full" @keydown.enter="applyFilters" />
                    <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Statuses" class="w-full" showClear />
                    <Select v-model="branchId" :options="branches" optionLabel="name" optionValue="id" placeholder="All Branches" class="w-full" showClear />
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <Button @click="() => { search=''; status=null; branchId=null; applyFilters(); }" severity="secondary" label="Clear" outlined size="small" />
                    <Button @click="applyFilters" severity="primary" label="Apply" size="small" />
                </div>
            </Panel>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="transfers.data" class="p-datatable-sm" stripedRows>
                    <Column field="transfer_number" header="Transfer #" style="width: 120px">
                        <template #body="{ data }"><span class="font-mono text-xs">{{ data.transfer_number }}</span></template>
                    </Column>
                    <Column header="Asset" style="min-width: 160px">
                        <template #body="{ data }">
                            <p class="font-medium text-sm">{{ data.asset?.name }}</p>
                            <p class="text-xs text-blue-600 font-mono">{{ data.asset?.asset_tag }}</p>
                        </template>
                    </Column>
                    <Column header="From → To" style="min-width: 200px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-600">{{ data.from_branch?.name ?? '?' }}</span>
                                <MoveRight :size="14" class="text-gray-400" />
                                <span class="text-gray-900 font-medium">{{ data.to_branch?.name ?? '?' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                                <span>{{ data.from_custodian?.name ?? '—' }}</span>
                                <MoveRight :size="10" />
                                <span>{{ data.to_custodian?.name ?? '—' }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="transfer_date" header="Date" style="width: 100px">
                        <template #body="{ data }"><span class="text-sm">{{ formatDate(data.transfer_date) }}</span></template>
                    </Column>
                    <Column header="Status" style="width: 110px">
                        <template #body="{ data }"><Tag :severity="data.status_badge.severity" :value="data.status_badge.label" /></template>
                    </Column>
                    <Column header="Actions" style="width: 130px">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('tenant.assets.transfers.show', data.id)">
                                    <Button severity="secondary" size="small" text rounded title="View"><Eye :size="14" /></Button>
                                </Link>
                                <Button v-if="data.status === 'pending'" severity="success" size="small" text rounded
                                    title="Approve" @click="confirmApprove(data)">
                                    <CheckCircle :size="14" />
                                </Button>
                                <Button v-if="data.status === 'pending'" severity="danger" size="small" text rounded
                                    title="Reject" @click="openReject(data)">
                                    <XCircle :size="14" />
                                </Button>
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-10 text-gray-500">
                            <MoveRight :size="36" class="mx-auto text-gray-300 mb-2" />
                            <p>No transfers found.</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Reject Dialog -->
        <Dialog v-model:visible="rejectDialog" modal header="Reject Transfer" style="width: 420px">
            <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection *</label>
                <Textarea v-model="rejectForm.rejection_reason" rows="3" class="w-full" placeholder="Provide a reason..." />
                <p v-if="rejectForm.errors.rejection_reason" class="text-red-500 text-xs mt-1">{{ rejectForm.errors.rejection_reason }}</p>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="rejectDialog = false" />
                <Button label="Reject Transfer" severity="danger" @click="submitReject" :loading="rejectForm.processing" />
            </template>
        </Dialog>
    </TenantLayout>
</template>