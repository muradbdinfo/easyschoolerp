<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ConfirmDialog from 'primevue/confirmdialog';
import { Plus, Edit, Trash2, Folder, FolderOpen } from 'lucide-vue-next';

const props = defineProps({
    categories: Object,
    filters:    Object,
});

const toast   = useToast();
const confirm = useConfirm();

// ── Dialog ─────────────────────────────────────────────────
const dialogVisible = ref(false);
const editingId     = ref(null);

const form = useForm({
    name:                   '',
    description:            '',
    parent_id:              null,
    depreciation_method:    'slm',
    depreciation_rate:      10,
    useful_life_years:      5,
    residual_value_percent: 10,
    status:                 true,
});

const depreciationMethods = [
    { label: 'Straight Line (SLM)', value: 'slm' },
    { label: 'Written Down Value (WDV)', value: 'wdv' },
    { label: 'No Depreciation', value: 'none' },
];

const parentOptions = computed(() =>
    props.categories.data
        .filter(c => !editingId.value || c.id !== editingId.value)
        .map(c => ({ label: c.name, value: c.id }))
);

function openCreate() {
    editingId.value = null;
    form.reset();
    form.status = true;
    dialogVisible.value = true;
}

function openEdit(cat) {
    editingId.value = cat.id;
    form.name                   = cat.name;
    form.description            = cat.description;
    form.parent_id              = cat.parent_id;
    form.depreciation_method    = cat.depreciation_method;
    form.depreciation_rate      = parseFloat(cat.depreciation_rate);
    form.useful_life_years      = cat.useful_life_years;
    form.residual_value_percent = parseFloat(cat.residual_value_percent);
    form.status                 = cat.status;
    dialogVisible.value = true;
}

function submit() {
    const url = editingId.value
        ? route('tenant.assets.categories.update', editingId.value)
        : route('tenant.assets.categories.store');
    const method = editingId.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            dialogVisible.value = false;
            toast.add({ severity: 'success', summary: 'Saved', detail: 'Category saved successfully.', life: 3000 });
        },
    });
}

function deleteCategory(cat) {
    confirm.require({
        message: `Delete "${cat.name}"? This cannot be undone.`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        rejectProps: { label: 'Cancel', severity: 'secondary' },
        acceptProps:  { label: 'Delete', severity: 'danger' },
        accept: () => {
            useForm({}).delete(route('tenant.assets.categories.destroy', cat.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Category deleted.', life: 3000 }),
                onError:   (e) => toast.add({ severity: 'error',   summary: 'Error',   detail: e.message ?? 'Cannot delete.', life: 4000 }),
            });
        },
    });
}

const methodBadge = (m) => ({ slm: 'info', wdv: 'warning', none: 'secondary' }[m] ?? 'info');
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Categories' }]">
        <ConfirmDialog />

        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Asset Categories</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage categories and depreciation settings</p>
                </div>
                <Button @click="openCreate" severity="primary">
                    <Plus :size="16" class="mr-1.5" />
                    New Category
                </Button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <DataTable
                    :value="categories.data"
                    :rows="20"
                    class="p-datatable-sm"
                    stripedRows
                >
                    <Column field="code" header="Code" style="width: 120px">
                        <template #body="{ data }">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ data.code }}</span>
                        </template>
                    </Column>

                    <Column field="name" header="Category Name">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <component :is="data.parent_id ? Folder : FolderOpen" :size="16" class="text-amber-500" />
                                <div>
                                    <p class="font-medium text-gray-900">{{ data.name }}</p>
                                    <p v-if="data.parent" class="text-xs text-gray-500">Parent: {{ data.parent.name }}</p>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Depreciation" style="width: 200px">
                        <template #body="{ data }">
                            <Tag :severity="methodBadge(data.depreciation_method)" :value="data.depreciation_method_label" />
                        </template>
                    </Column>

                    <Column field="depreciation_rate" header="Rate" style="width: 90px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.depreciation_rate }}%</span>
                        </template>
                    </Column>

                    <Column field="useful_life_years" header="Life" style="width: 80px">
                        <template #body="{ data }">
                            <span class="text-sm">{{ data.useful_life_years }}y</span>
                        </template>
                    </Column>

                    <Column field="assets_count" header="Assets" style="width: 90px">
                        <template #body="{ data }">
                            <span class="font-semibold text-blue-600">{{ data.assets_count }}</span>
                        </template>
                    </Column>

                    <Column header="Status" style="width: 90px">
                        <template #body="{ data }">
                            <Tag :severity="data.status ? 'success' : 'secondary'" :value="data.status ? 'Active' : 'Inactive'" />
                        </template>
                    </Column>

                    <Column header="Actions" style="width: 100px">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button @click="openEdit(data)" severity="secondary" size="small" text rounded>
                                    <Edit :size="14" />
                                </Button>
                                <Button @click="deleteCategory(data)" severity="danger" size="small" text rounded>
                                    <Trash2 :size="14" />
                                </Button>
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="text-center py-12 text-gray-500">
                            <Folder :size="40" class="mx-auto text-gray-300 mb-3" />
                            <p>No categories yet. Create your first one.</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog v-model:visible="dialogVisible" modal :header="editingId ? 'Edit Category' : 'New Category'" style="width: 520px">
            <div class="space-y-4 pt-2">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                        <InputText v-model="form.name" class="w-full" :invalid="!!form.errors.name" placeholder="e.g. Computers & IT" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
                        <Select v-model="form.parent_id" :options="parentOptions" optionLabel="label" optionValue="value"
                            placeholder="None (top level)" class="w-full" showClear />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Depreciation Method *</label>
                        <Select v-model="form.depreciation_method" :options="depreciationMethods"
                            optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Annual Rate (%) *</label>
                        <InputNumber v-model="form.depreciation_rate" :min="0" :max="100" :maxFractionDigits="2"
                            suffix="%" class="w-full" :disabled="form.depreciation_method === 'none'" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Useful Life (Years) *</label>
                        <InputNumber v-model="form.useful_life_years" :min="1" :max="100" class="w-full"
                            :disabled="form.depreciation_method === 'none'" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Residual Value (%)</label>
                        <InputNumber v-model="form.residual_value_percent" :min="0" :max="100" :maxFractionDigits="2"
                            suffix="%" class="w-full" :disabled="form.depreciation_method === 'none'" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <ToggleSwitch v-model="form.status" />
                        <span class="text-sm text-gray-700">Active</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <Textarea v-model="form.description" rows="2" class="w-full" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
                <Button :label="editingId ? 'Update' : 'Create'" @click="submit" :loading="form.processing" />
            </template>
        </Dialog>
    </TenantLayout>
</template>