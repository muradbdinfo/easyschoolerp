<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Panel from 'primevue/panel';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import { Wrench, Plus, Eye, Edit, Search, CheckCircle, AlertCircle, Clock } from 'lucide-vue-next';

const props = defineProps({
    maintenances: Object,
    stats:        Object,
    filters:      Object,
});

const toast = useToast();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? null);
const type   = ref(props.filters.type ?? null);

const statusOptions = [
    { label: 'Scheduled',   value: 'scheduled' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Completed',   value: 'completed' },
    { label: 'Cancelled',   value: 'cancelled' },
];
const typeOptions = [
    { label: 'Routine',     value: 'routine' },
    { label: 'Repair',      value: 'repair' },
    { label: 'Servicing',   value: 'servicing' },
    { label: 'Calibration', value: 'calibration' },
    { label: 'Upgrade',     value: 'upgrade' },
];

function applyFilters() {
    router.get(route('tenant.assets.maintenance.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        type:   type.value || undefined,
    }, { preserveState: true, replace: true });
}

// Quick Complete Dialog
const completeDialog = ref(false);
const completingId   = ref(null);
const completeForm   = useForm({
    completed_date:  new Date(),
    work_performed:  '',
    actual_cost:     null,
    condition_after: 'good',
});
const conditionOptions = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good',      value: 'good' },
    { label: 'Fair',      value: 'fair' },
    { label: 'Poor',      value: 'poor' },
];

function openComplete(m) {
    completingId.value = m.id;
    completeForm.reset();
    completeForm.completed_date = new Date();
    completeDialog.value = true;
}

function submitComplete() {
    completeForm.post(route('tenant.assets.maintenance.complete', completingId.value), {
        onSuccess: () => {
            completeDialog.value = false;
            toast.add({ severity: 'success', summary: 'Done', detail: 'Maintenance completed.', life: 3000 });
        },
    });
}

const formatDate     = (d) => d ? new Date(d).toLocaleDateString('en-BD') : '—';
const formatCurrency = (v) => '৳' + Number(v ?? 0).toLocaleString();
const isOverdue      = (m) => m.status === 'scheduled' && new Date(m.scheduled_date) < new Date();
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Maintenance' }]">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asset Maintenance</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Schedule and track maintenance</p>
                </div>
                <Link :href="route('tenant.assets.maintenance.create')">
                    <Button severity="primary"><Plus :size="16" class="mr-1.5" /> Schedule Maintenance</Button>
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Scheduled</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.scheduled }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">In Progress</p>
                    <p class="text-2xl font-bold text-amber-500 mt-1">{{ stats.in_progress }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-4 border border-red-100 shadow-sm">
                    <p class="text-xs text-red-500 font-medium">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.overdue }}</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-4 border border-amber-100 shadow-sm">
                    <p class="text-xs text-amber-600 font-medium">Due in 7 Days</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.due_soon }}</p>
                </div>
            </div>

            <!-- Filters -->
            <Panel :collapsed="false" toggleable>
                <template #header><div class="flex items-center gap-2"><Search :size="16" /><span class="font-medium">Filters</span></div></template>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <InputText v-model="search" placeholder="Maintenance # or asset tag..." class="w-full" @keydown.enter="applyFilters" />
                    <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All Statuses" class="w-full" showClear />
                    <Select v-model="type"   :options="typeOptions"   optionLabel="label" optionValue="value" placeholder="All Types"    class="w-full" showClear />
                </div>
                <div class="flex justify-end gap-2 mt-3">
                    <Button @click="() => { search=''; status=null; type=null; applyFilters(); }" severity="secondary" label="Clear" outlined size="small" />
                    <Button @click="applyFilters" severity="primary" label="Apply" size="small" />
                </div>
            </Panel>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="maintenances.data" class="p-datatable-sm" stripedRows>
                    <Column field="maintenance_number" header="#" style="width: 120px">
                        <template #body="{ data }">
                            <span class="font-mono text-xs" :class="{ 'text-red-600': isOverdue(data) }">{{ data.maintenance_number }}</span>
                        </template>
                    </Column>
                    <Column header="Asset" style="min-width: 180px">
                        <template #body="{ data }">
                            <p class="font-medium text-sm">{{ data.asset?.name }}</p>
                            <p class="text-xs text-blue-600 font-mono">{{ data.asset?.asset_tag }}</p>
                            <p class="text-xs text-gray-400">{{ data.asset?.branch?.name }}</p>
                        </template>
                    </Column>
                    <Column header="Type" style="width: 140px">
                        <template #body="{ data }"><span class="text-sm">{{ data.type_label }}</span></template>
                    </Column>
                    <Column header="Scheduled" style="width: 110px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5">
                                <AlertCircle v-if="isOverdue(data)" :size="13" class="text-red-500" />
                                <Clock v-else :size="13" class="text-gray-400" />
                                <span class="text-sm" :class="{ 'text-red-600 font-medium': isOverdue(data) }">
                                    {{ formatDate(data.scheduled_date) }}
                                </span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Vendor" style="width: 130px">
                        <template #body="{ data }"><span class="text-sm text-gray-600">{{ data.vendor?.name ?? '—' }}</span></template>
                    </Column>
                    <Column header="Est. Cost" style="width: 110px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.estimated_cost ? formatCurrency(data.estimated_cost) : '—' }}</span>
                        </template>
                    </Column>
                    <Column header="Status" style="width: 120px">
                        <template #body="{ data }"><Tag :severity="data.status_badge.severity" :value="data.status_badge.label" /></template>
                    </Column>
                    <Column header="Actions" style="width: 120px">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('tenant.assets.maintenance.show', data.id)">
                                    <Button severity="secondary" size="small" text rounded title="View"><Eye :size="14" /></Button>
                                </Link>
                                <Link :href="route('tenant.assets.maintenance.edit', data.id)">
                                    <Button severity="secondary" size="small" text rounded title="Edit"><Edit :size="14" /></Button>
                                </Link>
                                <Button v-if="['scheduled','in_progress'].includes(data.status)"
                                    severity="success" size="small" text rounded title="Mark Complete"
                                    @click="openComplete(data)">
                                    <CheckCircle :size="14" />
                                </Button>
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-10 text-gray-500">
                            <Wrench :size="36" class="mx-auto text-gray-300 mb-2" />
                            <p>No maintenance records found.</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Quick Complete Dialog -->
        <Dialog v-model:visible="completeDialog" modal header="Mark Maintenance Complete" style="width: 460px">
            <div class="space-y-4 pt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Completion Date *</label>
                    <DatePicker v-model="completeForm.completed_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work Performed *</label>
                    <Textarea v-model="completeForm.work_performed" rows="3" class="w-full" placeholder="Describe what was done..." :invalid="!!completeForm.errors.work_performed" />
                    <p v-if="completeForm.errors.work_performed" class="text-red-500 text-xs mt-1">{{ completeForm.errors.work_performed }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actual Cost (৳)</label>
                        <InputNumber v-model="completeForm.actual_cost" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condition After</label>
                        <Select v-model="completeForm.condition_after" :options="conditionOptions"
                            optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="completeDialog = false" />
                <Button label="Mark Complete" severity="success" @click="submitComplete" :loading="completeForm.processing" />
            </template>
        </Dialog>
    </TenantLayout>
</template>