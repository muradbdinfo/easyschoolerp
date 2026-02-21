<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import TenantLayout   from '@/Layouts/TenantLayout.vue';
import Toast          from 'primevue/toast';
import ConfirmDialog  from 'primevue/confirmdialog';
import Card           from 'primevue/card';
import DataTable      from 'primevue/datatable';
import Column         from 'primevue/column';
import Button         from 'primevue/button';
import Dialog         from 'primevue/dialog';
import InputText      from 'primevue/inputtext';
import InputNumber    from 'primevue/inputnumber';
import Select         from 'primevue/select';
import ToggleSwitch   from 'primevue/toggleswitch';
import Tag            from 'primevue/tag';
import Message        from 'primevue/message';

import { ShieldCheck, Plus, Pencil, Trash2, RotateCcw, Copy } from 'lucide-vue-next';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    policies:          { type: Array,   default: () => [] },
    hasTenantPolicies: { type: Boolean, default: false     },
    availableRoles:    { type: Array,   default: () => []  },
});

const toast   = useToast();
const confirm = useConfirm();

// ── Dialog state ───────────────────────────────────────────────────────────
const showDialog = ref(false);
const editingId  = ref(null);

const form = useForm({
    name:       '',
    level:      1,
    min_amount: 0,
    max_amount: null,
    role_name:  '',
    is_active:  true,
    sort_order: 0,
});

const levelOptions = [
    { label: 'Level 1 (First Approver)',  value: 1 },
    { label: 'Level 2 (Second Approver)', value: 2 },
    { label: 'Level 3 (Final Approver)',  value: 3 },
];

const roleOptions = computed(() =>
    props.availableRoles.map(r => ({ label: r, value: r }))
);

// ── Open dialog ────────────────────────────────────────────────────────────
const openCreate = () => {
    editingId.value = null;
    form.reset();
    showDialog.value = true;
};

const openEdit = (row) => {
    editingId.value  = row.id;
    form.name        = row.name;
    form.level       = row.level;
    form.min_amount  = parseFloat(row.min_amount);
    form.max_amount  = row.max_amount ? parseFloat(row.max_amount) : null;
    form.role_name   = row.role_name;
    form.is_active   = row.is_active;
    form.sort_order  = row.sort_order;
    showDialog.value = true;
};

// ── Save ───────────────────────────────────────────────────────────────────
const save = () => {
    if (editingId.value) {
        form.put(route('tenant.settings.approval-policies.update', editingId.value), {
            onSuccess: () => { showDialog.value = false; toast.add({ severity: 'success', summary: 'Saved', detail: 'Policy updated.', life: 3000 }); },
            onError:   () => toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save.', life: 3000 }),
        });
    } else {
        form.post(route('tenant.settings.approval-policies.store'), {
            onSuccess: () => { showDialog.value = false; toast.add({ severity: 'success', summary: 'Created', detail: 'Policy created.', life: 3000 }); },
            onError:   () => toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create.', life: 3000 }),
        });
    }
};

// ── Delete ─────────────────────────────────────────────────────────────────
const deletePolicy = (row) => {
    confirm.require({
        message:     `Delete "${row.name}"?`,
        header:      'Delete Policy',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.settings.approval-policies.destroy', row.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', life: 3000 }),
            });
        },
    });
};

// ── Copy defaults / Reset ──────────────────────────────────────────────────
const copyDefaults = () => {
    router.post(route('tenant.settings.approval-policies.copy-defaults'), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Copied', detail: 'Global defaults copied. Customise them now.', life: 4000 }),
    });
};

const resetToDefaults = () => {
    confirm.require({
        message:     'This will delete all your custom policies and revert to global defaults.',
        header:      'Reset to Defaults',
        icon:        'pi pi-exclamation-triangle',
        acceptClass: 'p-button-warning',
        accept: () => {
            router.post(route('tenant.settings.approval-policies.reset'), {}, {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Reset', detail: 'Now using global defaults.', life: 3000 }),
            });
        },
    });
};

// ── Helpers ────────────────────────────────────────────────────────────────
const formatAmount = (v) => v == null ? '∞' : 'BDT ' + Number(v).toLocaleString();
const levelSeverity = { 1: 'info', 2: 'warning', 3: 'danger' };
</script>

<template>
    <TenantLayout title="Approval Policies"
        :breadcrumb-items="[{ label: 'Settings' }, { label: 'Approval Policies' }]"
    >
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                        <ShieldCheck :size="20" class="text-blue-600" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Approval Policies</h1>
                        <p class="text-sm text-gray-500">Configure who approves requisitions at each amount level.</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <!-- If using global defaults: offer to copy & customise -->
                    <Button v-if="!hasTenantPolicies"
                        label="Customise for my school" icon="pi pi-copy"
                        severity="secondary" outlined size="small" @click="copyDefaults" />

                    <!-- If using custom: offer reset -->
                    <Button v-else
                        label="Reset to defaults" severity="secondary" outlined size="small"
                        @click="resetToDefaults">
                        <template #icon><RotateCcw :size="14" class="mr-1" /></template>
                    </Button>

                    <Button v-if="hasTenantPolicies"
                        label="Add Policy" size="small" @click="openCreate">
                        <template #icon><Plus :size="14" class="mr-1" /></template>
                    </Button>
                </div>
            </div>

            <!-- Info banner if using global defaults -->
            <Message v-if="!hasTenantPolicies" severity="info" :closable="false">
                You are using the <strong>global default policies</strong>.
                Click "Customise for my school" to create your own approval chain without affecting other schools.
            </Message>

            <!-- Policy table -->
            <Card>
                <template #content>
                    <DataTable :value="policies" dataKey="id" rowHover class="text-sm">
                        <template #empty>
                            <div class="text-center py-12 text-gray-400">
                                <ShieldCheck :size="44" class="mx-auto mb-3 opacity-30" />
                                <p class="font-semibold text-gray-500">No policies configured</p>
                                <Button label="Add first policy" size="small" class="mt-4" @click="openCreate" />
                            </div>
                        </template>

                        <Column header="Level" style="width:8%">
                            <template #body="{ data }">
                                <Tag :value="`Level ${data.level}`"
                                    :severity="levelSeverity[data.level]" class="text-xs" />
                            </template>
                        </Column>

                        <Column field="name" header="Policy Name" style="width:25%" />

                        <Column header="Amount Range" style="width:22%">
                            <template #body="{ data }">
                                <span class="font-mono text-sm text-gray-700">
                                    {{ formatAmount(data.min_amount) }}
                                    &nbsp;→&nbsp;
                                    {{ formatAmount(data.max_amount) }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Approver Role" style="width:18%">
                            <template #body="{ data }">
                                <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-700
                                             text-xs font-semibold px-2.5 py-1 rounded-full capitalize">
                                    {{ data.role_name.replace('_', ' ') }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Status" style="width:10%">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                                    :severity="data.is_active ? 'success' : 'secondary'" class="text-xs" />
                            </template>
                        </Column>

                        <Column style="width:8%" v-if="hasTenantPolicies">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button icon="pi pi-pencil" text rounded size="small"
                                        severity="secondary" @click="openEdit(data)" />
                                    <Button text rounded size="small" severity="danger"
                                        @click="deletePolicy(data)">
                                        <template #icon><Trash2 :size="14" /></template>
                                    </Button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Help box -->
            <Card>
                <template #content>
                    <div class="text-sm text-gray-600 space-y-2">
                        <p class="font-semibold text-gray-800">How approval policies work</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>When a PR is submitted, the system matches its total amount to the <strong>Amount Range</strong>.</li>
                            <li>It finds the first user with the matching <strong>Approver Role</strong> in your school.</li>
                            <li>Multiple levels run in sequence — Level 1 approves first, then Level 2, then Level 3.</li>
                            <li>If no user has the required role, the system falls back to the department head.</li>
                            <li>Custom policies override global defaults for your school only.</li>
                        </ul>
                    </div>
                </template>
            </Card>

        </div>

        <!-- Add / Edit Dialog -->
        <Dialog v-model:visible="showDialog"
            :header="editingId ? 'Edit Policy' : 'Add Approval Policy'"
            modal :style="{ width: '460px' }"
        >
            <div class="space-y-4 pt-2">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Policy Name</label>
                    <InputText v-model="form.name" class="w-full"
                        placeholder="e.g. Department Head Approval" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approval Level</label>
                    <Select v-model="form.level" :options="levelOptions"
                        optionLabel="label" optionValue="value" class="w-full" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Amount (BDT)</label>
                        <InputNumber v-model="form.min_amount" class="w-full" :min="0" :useGrouping="true" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Amount (BDT)</label>
                        <InputNumber v-model="form.max_amount" class="w-full" :min="0" :useGrouping="true"
                            placeholder="Leave blank = no limit" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approver Role</label>
                    <Select v-model="form.role_name"
                        :options="roleOptions.length ? roleOptions : [{label: form.role_name, value: form.role_name}]"
                        optionLabel="label" optionValue="value"
                        editable class="w-full"
                        placeholder="Select or type a role" />
                    <p class="text-xs text-gray-400 mt-1">
                        Roles come from your active users. You can also type a custom role name.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <ToggleSwitch v-model="form.is_active" />
                    <label class="text-sm text-gray-700">Active</label>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showDialog = false" />
                <Button :label="editingId ? 'Update' : 'Create'"
                    :loading="form.processing" @click="save" />
            </template>
        </Dialog>

    </TenantLayout>
</template>