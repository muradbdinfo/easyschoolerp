<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import ToggleButton from 'primevue/togglebutton';
import SelectButton from 'primevue/selectbutton';
import { CheckSquare, AlertTriangle, Search } from 'lucide-vue-next';

const props = defineProps({
    cycle: Object,
    items: Object,
    filters: Object,
    summary: Object,
});

const toast = useToast();

// Filter
const filterOpts = [
    { label: 'All',       value: '' },
    { label: 'Pending',   value: 'pending' },
    { label: 'Verified',  value: 'verified' },
    { label: 'Discrepancy', value: 'discrepancy' },
];
const activeFilter = ref(props.filters?.filter || '');
const searchQ = ref(props.filters?.search || '');

const applyFilter = () => {
    router.get(route('tenant.assets.verification.show', props.cycle.id), {
        filter: activeFilter.value,
        search: searchQ.value,
    }, { preserveState: true });
};

// Verify dialog
const showVerifyDialog = ref(false);
const selectedItem = ref(null);
const verifyForm = ref({});
const verifying = ref(false);

const conditionOpts = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good',      value: 'good' },
    { label: 'Fair',      value: 'fair' },
    { label: 'Poor',      value: 'poor' },
];
const severityOpts = [
    { label: 'Low',    value: 'low' },
    { label: 'Medium', value: 'medium' },
    { label: 'High',   value: 'high' },
];

const openVerify = (item) => {
    selectedItem.value = item;
    verifyForm.value = {
        is_present:         true,
        location_correct:   true,
        custodian_correct:  true,
        condition:          'good',
        actual_location:    '',
        actual_custodian:   '',
        discrepancy_details: '',
        severity:           null,
    };
    showVerifyDialog.value = true;
};

const hasDiscrepancy = () => {
    const f = verifyForm.value;
    return !f.is_present || !f.location_correct || !f.custodian_correct
        || f.condition === 'fair' || f.condition === 'poor';
};

const submitVerify = () => {
    verifying.value = true;
    router.post(route('tenant.assets.verification.verify', {
        cycle: props.cycle.id,
        item:  selectedItem.value.id,
    }), verifyForm.value, {
        onSuccess: () => {
            showVerifyDialog.value = false;
            toast.add({ severity: 'success', summary: 'Verified', detail: 'Asset verification saved', life: 3000 });
        },
        onFinish: () => { verifying.value = false; },
    });
};

// Resolve discrepancy
const resolveForm = ref({ resolution_status: 'resolved', resolution_notes: '' });
const showResolveDialog = ref(false);
const resolveItem = ref(null);

const openResolve = (item) => {
    resolveItem.value = item;
    resolveForm.value = { resolution_status: 'resolved', resolution_notes: '' };
    showResolveDialog.value = true;
};

const submitResolve = () => {
    router.post(route('tenant.assets.verification.resolve', resolveItem.value.id), resolveForm.value, {
        onSuccess: () => {
            showResolveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Discrepancy updated', life: 3000 });
        },
    });
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Verification', url: route('tenant.assets.verification.index') },
        { label: cycle.name },
    ]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <CheckSquare class="w-7 h-7 text-blue-600" />
                        {{ cycle.name }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ cycle.start_date }} – {{ cycle.end_date }}
                        <Tag :value="cycle.status_badge.label" :severity="cycle.status_badge.severity" class="ml-2" />
                    </p>
                </div>
                <Button v-if="cycle.status === 'in_progress'"
                    label="Mark Complete" icon="pi pi-check-circle" severity="success"
                    @click="router.post(route('tenant.assets.verification.complete', cycle.id))" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-gray-800">{{ summary.total }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Assets</p>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-green-700">{{ summary.verified }}</p>
                    <p class="text-xs text-green-600 mt-1">Verified</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-yellow-700">{{ summary.pending }}</p>
                    <p class="text-xs text-yellow-600 mt-1">Pending</p>
                </div>
                <div class="bg-red-50 border border-red-100 rounded-xl p-4 text-center">
                    <p class="text-3xl font-bold text-red-700">{{ summary.discrepancy }}</p>
                    <p class="text-xs text-red-600 mt-1">Discrepancies</p>
                </div>
            </div>

            <!-- Progress -->
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Verification Progress</span>
                    <span class="font-semibold">{{ cycle.progress_percent }}%</span>
                </div>
                <ProgressBar :value="cycle.progress_percent" style="height: 10px" />
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-3 flex-wrap">
                <SelectButton v-model="activeFilter" :options="filterOpts" optionLabel="label" optionValue="value"
                    @change="applyFilter" />
                <div class="relative ml-auto">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <InputText v-model="searchQ" placeholder="Search by tag or name..."
                        class="pl-9 w-64" @keyup.enter="applyFilter" />
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable :value="items.data" class="p-datatable-sm" stripedRows>
                    <template #empty>
                        <div class="text-center py-12 text-gray-400">
                            <CheckSquare class="w-10 h-10 mx-auto mb-2 opacity-30" />
                            <p>No assets found</p>
                        </div>
                    </template>

                    <Column header="Asset" style="min-width: 220px">
                        <template #body="{ data }">
                            <p class="font-mono text-sm font-semibold text-blue-700">{{ data.asset_tag }}</p>
                            <p class="text-xs text-gray-600">{{ data.asset_name }}</p>
                        </template>
                    </Column>

                    <Column header="Category / Branch" style="min-width: 160px">
                        <template #body="{ data }">
                            <p class="text-sm text-gray-700">{{ data.category }}</p>
                            <p class="text-xs text-gray-400">{{ data.branch }}</p>
                        </template>
                    </Column>

                    <Column header="Expected Location">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-600">{{ data.location || '-' }}</span>
                        </template>
                    </Column>

                    <Column header="Status" class="text-center">
                        <template #body="{ data }">
                            <div v-if="data.verified_at">
                                <Tag v-if="data.has_discrepancy" value="Discrepancy" severity="danger" />
                                <Tag v-else value="Verified OK" severity="success" />
                                <p class="text-xs text-gray-400 mt-1">{{ data.verified_at }}</p>
                            </div>
                            <Tag v-else value="Pending" severity="warning" />
                        </template>
                    </Column>

                    <Column header="Actions" class="text-center" style="min-width: 120px">
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-center">
                                <Button v-if="!data.verified_at && cycle.status !== 'completed'"
                                    label="Verify" icon="pi pi-check" size="small" severity="primary"
                                    @click="openVerify(data)" />
                                <Button v-if="data.has_discrepancy && cycle.status !== 'completed'"
                                    icon="pi pi-flag" size="small" severity="warning" text
                                    title="Resolve Discrepancy" @click="openResolve(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="items.last_page > 1" class="flex justify-center py-4 border-t">
                    <div class="flex gap-2">
                        <a v-for="link in items.links" :key="link.label"
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-sm rounded border"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 hover:bg-gray-50'" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Verify Dialog -->
        <Dialog v-model:visible="showVerifyDialog" :header="`Verify: ${selectedItem?.asset_tag}`"
            modal :style="{ width: '520px' }">
            <div class="space-y-4 py-2" v-if="selectedItem">
                <p class="text-sm text-gray-600 font-medium">{{ selectedItem.asset_name }}</p>

                <!-- Yes/No checks -->
                <div class="grid grid-cols-1 gap-3">
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <label class="text-sm font-medium text-gray-700">Asset is physically present?</label>
                        <ToggleButton v-model="verifyForm.is_present"
                            onLabel="Yes" offLabel="No" onIcon="pi pi-check" offIcon="pi pi-times" />
                    </div>
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <label class="text-sm font-medium text-gray-700">Location is correct?</label>
                        <ToggleButton v-model="verifyForm.location_correct"
                            onLabel="Yes" offLabel="No" onIcon="pi pi-check" offIcon="pi pi-times" />
                    </div>
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <label class="text-sm font-medium text-gray-700">Custodian is correct?</label>
                        <ToggleButton v-model="verifyForm.custodian_correct"
                            onLabel="Yes" offLabel="No" onIcon="pi pi-check" offIcon="pi pi-times" />
                    </div>
                </div>

                <!-- Condition -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                    <SelectButton v-model="verifyForm.condition" :options="conditionOpts"
                        optionLabel="label" optionValue="value" class="w-full" />
                </div>

                <!-- Discrepancy section (shows when needed) -->
                <div v-if="hasDiscrepancy()" class="border border-red-100 bg-red-50 rounded-lg p-4 space-y-3">
                    <p class="text-sm font-semibold text-red-700 flex items-center gap-1">
                        <AlertTriangle class="w-4 h-4" /> Discrepancy Detected
                    </p>
                    <div v-if="!verifyForm.location_correct">
                        <label class="block text-xs text-gray-600 mb-1">Actual Location</label>
                        <InputText v-model="verifyForm.actual_location" class="w-full text-sm" placeholder="Where is it?" />
                    </div>
                    <div v-if="!verifyForm.custodian_correct">
                        <label class="block text-xs text-gray-600 mb-1">Actual Custodian</label>
                        <InputText v-model="verifyForm.actual_custodian" class="w-full text-sm" placeholder="Who has it?" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Discrepancy Details</label>
                        <Textarea v-model="verifyForm.discrepancy_details" class="w-full text-sm" rows="2" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-2">Severity</label>
                        <SelectButton v-model="verifyForm.severity" :options="severityOpts"
                            optionLabel="label" optionValue="value" />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showVerifyDialog = false" />
                <Button label="Save Verification" icon="pi pi-check" severity="primary"
                    :loading="verifying" @click="submitVerify" />
            </template>
        </Dialog>

        <!-- Resolve Dialog -->
        <Dialog v-model:visible="showResolveDialog" header="Resolve Discrepancy" modal :style="{ width: '420px' }">
            <div class="space-y-4 py-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Status</label>
                    <Select v-model="resolveForm.resolution_status" class="w-full"
                        :options="[{ label: 'Under Investigation', value: 'investigating' }, { label: 'Resolved', value: 'resolved' }]"
                        optionLabel="label" optionValue="value" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes <span class="text-red-500">*</span></label>
                    <Textarea v-model="resolveForm.resolution_notes" class="w-full" rows="3"
                        placeholder="Describe what was done to resolve this..." />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showResolveDialog = false" />
                <Button label="Update" icon="pi pi-save" severity="primary" @click="submitResolve" />
            </template>
        </Dialog>
    </TenantLayout>
</template>