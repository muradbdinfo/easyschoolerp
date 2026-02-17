<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue components
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Steps from 'primevue/steps';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import AutoComplete from 'primevue/autocomplete';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import DatePicker from 'primevue/datepicker';

// Lucide icons
import {
    CalendarDays as CalendarIcon,
    Package,
    FileText as FileTextIcon,
    Eye,
    Upload,
} from 'lucide-vue-next';

// ─────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────
const props = defineProps({
    departments: { type: Array, default: () => [] },
    branches:    { type: Array, default: () => [] },
    errors:      { type: Object, default: () => ({}) },  // <-- Add errors prop
});

// ─────────────────────────────────────────────
// Composables
// ─────────────────────────────────────────────
const toast   = useToast();
const confirm = useConfirm();

// ─────────────────────────────────────────────
// Steps config
// ─────────────────────────────────────────────
const activeStep = ref(0);
const steps = [
    { label: 'Basic Information' },
    { label: 'Add Items'         },
    { label: 'Justification'     },
    { label: 'Review & Submit'   },
];

// ─────────────────────────────────────────────
// Main form state
// ─────────────────────────────────────────────
const form = reactive({
    department_id:    null,
    branch_id:        null,
    required_by_date: null,
    priority:         'medium',
    purpose:          '',
    justification:    '',
    notes:            '',
    items:            [],
    attachments:      [],
    status:           'draft',
});

const priorityOptions = [
    { label: 'Low',    value: 'low'    },
    { label: 'Medium', value: 'medium' },
    { label: 'High',   value: 'high'   },
    { label: 'Urgent', value: 'urgent' },
];

// ─────────────────────────────────────────────
// Item search state
// ─────────────────────────────────────────────
const itemSearchResults = ref([]);
const selectedItem      = ref(null);
const itemQuantity      = ref(1);
const itemPrice         = ref(0);
const itemSpecs         = ref('');
const isSearching       = ref(false);

// ─────────────────────────────────────────────
// Auto-save state
// ─────────────────────────────────────────────
const autoSaveInterval = ref(null);
const lastSavedAt      = ref(null);
const draftPRId        = ref(null);
const isSubmitting     = ref(false);

// ─────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────
const totalAmount = computed(() =>
    form.items.reduce((sum, item) => sum + (item.quantity * item.estimated_unit_price), 0)
);

const purposeCharCount = computed(() => form.purpose?.length || 0);

const isStep1Valid = computed(() =>
    !!form.department_id && !!form.branch_id && !!form.required_by_date && !!form.priority
);
const isStep2Valid = computed(() => form.items.length > 0);
const isStep3Valid = computed(() => form.purpose?.trim()?.length >= 20);

const hasUnsavedChanges = computed(() =>
    !!form.department_id || !!form.branch_id || form.items.length > 0
);

// ─────────────────────────────────────────────
// Item helpers
// ─────────────────────────────────────────────
const searchItems = async (event) => {
    if (!event.query || event.query.length < 2) { 
        itemSearchResults.value = []; 
        return; 
    }

    isSearching.value = true;
    const url = route('tenant.requisitions.search.items');
    console.log('[searchItems] query:', event.query, '| url:', url);

    try {
        const { data } = await axios.get(url, { params: { query: event.query } });
        console.log('[searchItems] response:', data);
        
        // Handle error/debug responses
        if (data._debug) {
            console.error('[searchItems] Server debug:', data._debug);
            itemSearchResults.value = [];
            toast.add({ 
                severity: 'error', 
                summary: 'Search Error', 
                detail: 'Item search failed: ' + data._debug, 
                life: 5000 
            });
            return;
        }
        
        // Ensure data is an array
        itemSearchResults.value = Array.isArray(data) ? data : [];
        
    } catch (err) {
        console.error('[searchItems] ERROR:', err?.response?.status, err?.response?.data ?? err.message);
        itemSearchResults.value = [];
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: `Failed to search items (${err?.response?.status ?? 'network'})`, 
            life: 4000 
        });
    } finally {
        isSearching.value = false;
    }
};

const addItem = () => {
    if (!selectedItem.value) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please select an item first', life: 3000 });
        return;
    }
    if (!itemQuantity.value || itemQuantity.value <= 0) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Quantity must be greater than 0', life: 3000 });
        return;
    }
    if (form.items.find(i => i.item_id === selectedItem.value.id)) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'This item is already in the list', life: 3000 });
        return;
    }

    const price = itemPrice.value || selectedItem.value.unit_price || 0;
    form.items.push({
        item_id:              selectedItem.value.id,
        item_code:            selectedItem.value.code,
        item_name:            selectedItem.value.name,
        item_description:     selectedItem.value.description || '',
        unit:                 selectedItem.value.unit,
        quantity:             itemQuantity.value,
        estimated_unit_price: price,
        specifications:       itemSpecs.value || '',
        estimated_total:      itemQuantity.value * price,
    });

    // Reset inputs
    selectedItem.value = null;
    itemQuantity.value = 1;
    itemPrice.value    = 0;
    itemSpecs.value    = '';
    itemSearchResults.value = [];
    toast.add({ severity: 'success', summary: 'Added', detail: 'Item added to list', life: 2000 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
    toast.add({ severity: 'info', summary: 'Removed', detail: 'Item removed', life: 2000 });
};

const updateItemTotal = (item) => {
    item.estimated_total = (item.quantity || 0) * (item.estimated_unit_price || 0);
};

// ─────────────────────────────────────────────
// File upload
// ─────────────────────────────────────────────
const onFileSelect = (event) => { 
    form.attachments = event.files || []; 
};

const onFileRemove = (event) => {
    const idx = form.attachments.indexOf(event.file);
    if (idx > -1) form.attachments.splice(idx, 1);
};

// ─────────────────────────────────────────────
// Navigation
// ─────────────────────────────────────────────
const nextStep = () => {
    const validations = [isStep1Valid, isStep2Valid, isStep3Valid];
    const messages    = [
        'Please fill all required fields (Department, Branch, Date, Priority)',
        'Please add at least one item',
        'Purpose must be at least 20 characters',
    ];
    if (activeStep.value < 3 && !validations[activeStep.value].value) {
        toast.add({ severity: 'warn', summary: 'Incomplete', detail: messages[activeStep.value], life: 3000 });
        return;
    }
    if (activeStep.value < steps.length - 1) activeStep.value++;
};

const prevStep = () => { if (activeStep.value > 0) activeStep.value--; };

const goToStep = (index) => { if (index <= activeStep.value) activeStep.value = index; };

// ─────────────────────────────────────────────
// Build FormData helper
// ─────────────────────────────────────────────
const buildFormData = (status) => {
    const fd = new FormData();
    fd.append('status',           status);
    fd.append('department_id',    form.department_id   || '');
    fd.append('branch_id',        form.branch_id       || '');

    // Normalise date
    const rd = form.required_by_date;
    let dateStr = '';
    if (rd) {
        if (rd instanceof Date) {
            dateStr = rd.toISOString().slice(0, 10);
        } else if (typeof rd === 'string') {
            dateStr = rd;
        }
    }
    fd.append('required_by_date', dateStr);

    fd.append('priority',      form.priority);
    fd.append('purpose',       form.purpose || '');
    fd.append('justification', form.justification || '');
    fd.append('notes',         form.notes || '');
    
    // Ensure items is proper JSON
    const itemsJson = JSON.stringify(form.items);
    fd.append('items', itemsJson);
    
    console.log('[buildFormData] items JSON:', itemsJson);

    // Add attachments
    if (form.attachments && form.attachments.length > 0) {
        form.attachments.forEach((file, i) => {
            if (file instanceof File) {
                fd.append(`attachments[${i}]`, file);
            }
        });
    }
    
    return fd;
};

// ─────────────────────────────────────────────
// Save as draft
// ─────────────────────────────────────────────
const saveDraft = () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    
    router.post(route('tenant.requisitions.store'), buildFormData('draft'), {
        forceFormData: true,
        preserveState: true,
        onSuccess: () => {
            isSubmitting.value = false;
            toast.add({ severity: 'success', summary: 'Saved',  detail: 'Draft saved successfully', life: 3000 });
        },
        onError: (errors) => {
            isSubmitting.value = false;
            console.error('[saveDraft] errors:', errors);
            const errorMsg = Object.values(errors).flat().join(', ');
            toast.add({ severity: 'error',   summary: 'Error',  detail: errorMsg || 'Failed to save draft', life: 5000 });
        },
    });
};

// ─────────────────────────────────────────────
// Submit for approval
// ─────────────────────────────────────────────
const submitForApproval = () => {
    if (isSubmitting.value) return;
    
    // Final validation
    if (!isStep1Valid.value) {
        toast.add({ severity: 'warn', summary: 'Incomplete', detail: 'Please complete all required fields in Step 1', life: 3000 });
        activeStep.value = 0;
        return;
    }
    if (!isStep2Valid.value) {
        toast.add({ severity: 'warn', summary: 'No Items', detail: 'Please add at least one item', life: 3000 });
        activeStep.value = 1;
        return;
    }
    if (!isStep3Valid.value) {
        toast.add({ severity: 'warn', summary: 'Purpose Required', detail: 'Purpose must be at least 20 characters', life: 3000 });
        activeStep.value = 2;
        return;
    }

    confirm.require({
        message: 'Submit this requisition for approval? You will not be able to edit it after submission.',
        header:  'Confirm Submission',
        icon:    'pi pi-exclamation-triangle',
        accept:  () => {
            isSubmitting.value = true;
            
            router.post(route('tenant.requisitions.store'), buildFormData('submitted'), {
                forceFormData: true,
                preserveState: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Requisition submitted for approval', life: 3000 });
                },
                onError: (errors) => {
                    isSubmitting.value = false;
                    console.error('[submitForApproval] errors:', errors);
                    const errorMsg = Object.values(errors).flat().join(', ');
                    toast.add({ severity: 'error', summary: 'Submit Failed', detail: errorMsg || 'Failed to submit requisition', life: 5000 });
                },
            });
        },
    });
};

// ─────────────────────────────────────────────
// Auto-save (every 30 s)
// ─────────────────────────────────────────────
const startAutoSave = () => {
    autoSaveInterval.value = setInterval(async () => {
        if (!form.department_id || !form.branch_id) return;
        try {
            const { data } = await axios.post(route('tenant.requisitions.autosave'), {
                id:               draftPRId.value,
                department_id:    form.department_id,
                branch_id:        form.branch_id,
                required_by_date: form.required_by_date instanceof Date
                    ? form.required_by_date.toISOString().slice(0, 10)
                    : form.required_by_date,
                priority:      form.priority,
                purpose:       form.purpose,
                justification: form.justification,
                notes:         form.notes,
            });
            if (data.success) {
                draftPRId.value   = data.pr_id;
                lastSavedAt.value = new Date();
            }
        } catch { /* silent */ }
    }, 30000);
};

const stopAutoSave = () => { 
    if (autoSaveInterval.value) clearInterval(autoSaveInterval.value); 
};

// ─────────────────────────────────────────────
// Unsaved-changes browser warning
// ─────────────────────────────────────────────
const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value) { 
        e.preventDefault(); 
        e.returnValue = ''; 
    }
};

// ─────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────
onMounted(() => {
    startAutoSave();
    window.addEventListener('beforeunload', handleBeforeUnload);
    
    // Show errors from server if any
    if (props.errors && Object.keys(props.errors).length > 0) {
        console.error('[onMounted] Server errors:', props.errors);
        const errorMsg = Object.values(props.errors).flat().join(', ');
        toast.add({ severity: 'error', summary: 'Server Error', detail: errorMsg, life: 5000 });
    }
});

onBeforeUnmount(() => {
    stopAutoSave();
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <TenantLayout title="Create Purchase Requisition">
        <div class="p-6">

            <!-- Page header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Create Purchase Requisition</h1>
                <p class="text-gray-500 mt-1">Complete each step, then submit for approval.</p>
            </div>

            <!-- Steps progress -->
            <Card class="mb-6">
                <template #content>
                    <Steps :model="steps" :activeStep="activeStep" />
                </template>
            </Card>

            <!-- STEP 1 — Basic Information -->
            <Card v-if="activeStep === 0" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <CalendarIcon :size="22" class="text-blue-600" />
                        <span>Basic Information</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="flex flex-col gap-1">
                            <label class="font-semibold text-sm">Department <span class="text-red-500">*</span></label>
                            <Select
                                v-model="form.department_id"
                                :options="props.departments"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Department"
                                filter
                                class="w-full"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-semibold text-sm">Branch <span class="text-red-500">*</span></label>
                            <Select
                                v-model="form.branch_id"
                                :options="props.branches"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Branch"
                                class="w-full"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-semibold text-sm">Required By Date <span class="text-red-500">*</span></label>
                            <DatePicker
                                v-model="form.required_by_date"
                                :minDate="new Date()"
                                dateFormat="yy-mm-dd"
                                placeholder="Pick a date"
                                showIcon
                                class="w-full"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="font-semibold text-sm">Priority <span class="text-red-500">*</span></label>
                            <SelectButton
                                v-model="form.priority"
                                :options="priorityOptions"
                                optionLabel="label"
                                optionValue="value"
                            />
                        </div>

                    </div>
                </template>
            </Card>

            <!-- STEP 2 — Add Items -->
            <Card v-if="activeStep === 1" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <Package :size="22" class="text-blue-600" />
                        <span>Add Items ({{ form.items.length }})</span>
                    </div>
                </template>
                <template #content>

                    <!-- Search row -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                            <div class="md:col-span-4 flex flex-col gap-1">
                                <label class="font-semibold text-sm">Search Item</label>
                                <AutoComplete
                                    v-model="selectedItem"
                                    :suggestions="itemSearchResults"
                                    optionLabel="name"
                                    placeholder="Type item name or code…"
                                    :forceSelection="false"
                                    :dropdown="true"
                                    class="w-full"
                                    @complete="searchItems"
                                    :loading="isSearching"
                                >
                                    <template #option="slotProps">
                                        <div class="flex flex-col">
                                            <span class="font-semibold">{{ slotProps.option.name }}</span>
                                            <span class="text-xs text-gray-500">{{ slotProps.option.code }} | {{ slotProps.option.unit }} | {{ slotProps.option.unit_price }} BDT</span>
                                        </div>
                                    </template>
                                </AutoComplete>
                            </div>

                            <div class="md:col-span-2 flex flex-col gap-1">
                                <label class="font-semibold text-sm">Quantity <span class="text-red-500">*</span></label>
                                <InputNumber
                                    v-model="itemQuantity"
                                    :min="0.01"
                                    :maxFractionDigits="2"
                                    class="w-full"
                                />
                            </div>

                            <div class="md:col-span-3 flex flex-col gap-1">
                                <label class="font-semibold text-sm">Est. Unit Price (BDT)</label>
                                <InputNumber
                                    v-model="itemPrice"
                                    :min="0"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="2"
                                    class="w-full"
                                    :placeholder="selectedItem?.unit_price?.toString() || '0.00'"
                                />
                            </div>

                            <div class="md:col-span-2 flex flex-col gap-1">
                                <label class="font-semibold text-sm">Specifications</label>
                                <InputText v-model="itemSpecs" placeholder="Optional" class="w-full" />
                            </div>

                            <div class="md:col-span-1">
                                <Button 
                                    label="Add" 
                                    icon="pi pi-plus" 
                                    severity="success" 
                                    class="w-full" 
                                    @click="addItem"
                                    :disabled="!selectedItem" 
                                />
                            </div>

                        </div>
                    </div>

                    <!-- Items table -->
                    <DataTable :value="form.items" class="mb-4">
                        <template #empty>
                            <div class="text-center py-10 text-gray-400">
                                <Package :size="48" class="mx-auto mb-3 opacity-40" />
                                <p>No items yet — search and add items above.</p>
                            </div>
                        </template>

                        <Column field="item_code" header="Code" style="width:12%" />
                        <Column field="item_name" header="Item" style="width:25%" />
                        <Column field="unit" header="Unit" style="width:10%" />

                        <Column header="Qty" style="width:12%">
                            <template #body="{ data }">
                                <InputNumber
                                    v-model="data.quantity"
                                    :min="0.01"
                                    :maxFractionDigits="2"
                                    class="w-full"
                                    @input="updateItemTotal(data)"
                                />
                            </template>
                        </Column>

                        <Column header="Unit Price" style="width:15%">
                            <template #body="{ data }">
                                <InputNumber
                                    v-model="data.estimated_unit_price"
                                    :min="0"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="2"
                                    class="w-full"
                                    @input="updateItemTotal(data)"
                                />
                            </template>
                        </Column>

                        <Column header="Total (BDT)" style="width:14%">
                            <template #body="{ data }">
                                <span class="font-semibold">{{ (data.estimated_total || 0).toFixed(2) }}</span>
                            </template>
                        </Column>

                        <Column style="width:12%">
                            <template #body="{ index }">
                                <Button icon="pi pi-trash" severity="danger" text rounded @click="removeItem(index)" />
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Grand total -->
                    <div class="flex justify-end">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-3 text-right">
                            <p class="text-xs text-gray-500 mb-1">Total Amount</p>
                            <p class="text-2xl font-bold text-blue-700">{{ totalAmount.toFixed(2) }} BDT</p>
                        </div>
                    </div>

                </template>
            </Card>

            <!-- STEP 3 — Justification -->
            <Card v-if="activeStep === 2" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <FileTextIcon :size="22" class="text-blue-600" />
                        <span>Justification</span>
                    </div>
                </template>
                <template #content>

                    <div class="mb-6">
                        <label class="font-semibold text-sm block mb-1">
                            Purpose <span class="text-red-500">*</span>
                            <span class="font-normal text-gray-500 ml-1">(Why are these items needed? Minimum 20 characters)</span>
                        </label>
                        <Textarea
                            v-model="form.purpose"
                            :rows="5"
                            placeholder="Explain the purpose and necessity of these items…"
                            class="w-full"
                            autoResize
                        />
                        <small :class="purposeCharCount < 20 ? 'text-red-500' : 'text-green-600'">
                            {{ purposeCharCount }} / 1000 characters
                            <span v-if="purposeCharCount < 20"> — need {{ 20 - purposeCharCount }} more</span>
                        </small>
                    </div>

                    <div class="mb-6">
                        <label class="font-semibold text-sm block mb-1">Additional Details <span class="text-gray-400 font-normal">(optional)</span></label>
                        <Textarea v-model="form.justification" :rows="4" placeholder="Any additional information…" class="w-full" autoResize />
                    </div>

                    <div class="mb-6">
                        <label class="font-semibold text-sm flex items-center gap-1 mb-1">
                            <Upload :size="16" /> Attachments <span class="text-gray-400 font-normal">(optional, max 5 MB each)</span>
                        </label>
                        <FileUpload
                            name="attachments[]"
                            accept="image/*,.pdf,.doc,.docx"
                            :multiple="true"
                            :maxFileSize="5000000"
                            :showUploadButton="false"
                            :showCancelButton="false"
                            mode="advanced"
                            @select="onFileSelect"
                            @remove="onFileRemove"
                        >
                            <template #empty>
                                <p class="text-gray-400 text-sm">Drag & drop files here, or click to browse.</p>
                            </template>
                        </FileUpload>
                    </div>

                    <div>
                        <label class="font-semibold text-sm block mb-1">Internal Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                        <Textarea v-model="form.notes" :rows="3" placeholder="Any internal notes…" class="w-full" autoResize />
                    </div>

                </template>
            </Card>

            <!-- STEP 4 — Review & Submit -->
            <Card v-if="activeStep === 3" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <Eye :size="22" class="text-blue-600" />
                        <span>Review & Submit</span>
                    </div>
                </template>
                <template #content>

                    <!-- Basic info -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-lg">Basic Information</h3>
                            <Button label="Edit" text size="small" @click="goToStep(0)" />
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm bg-gray-50 p-3 rounded">
                            <div>
                                <span class="text-gray-500">Department:</span>
                                <span class="ml-2 font-semibold">{{ props.departments.find(d => d.id === form.department_id)?.name || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Branch:</span>
                                <span class="ml-2 font-semibold">{{ props.branches.find(b => b.id === form.branch_id)?.name || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Required By:</span>
                                <span class="ml-2 font-semibold">{{ form.required_by_date || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Priority:</span>
                                <span class="ml-2 font-semibold capitalize">{{ form.priority }}</span>
                            </div>
                        </div>
                    </div>

                    <Divider />

                    <!-- Items -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-lg">Items ({{ form.items.length }})</h3>
                            <Button label="Edit" text size="small" @click="goToStep(1)" />
                        </div>
                        <DataTable :value="form.items" size="small" class="text-sm">
                            <Column field="item_code" header="Code" style="width:15%" />
                            <Column field="item_name" header="Item" style="width:30%" />
                            <Column field="quantity" header="Qty" style="width:10%" />
                            <Column field="unit" header="Unit" style="width:10%" />
                            <Column header="Unit Price" style="width:15%">
                                <template #body="{ data }">{{ (data.estimated_unit_price || 0).toFixed(2) }}</template>
                            </Column>
                            <Column header="Total" style="width:15%">
                                <template #body="{ data }">{{ (data.estimated_total || 0).toFixed(2) }}</template>
                            </Column>
                        </DataTable>
                        <p class="text-right mt-3 font-bold text-lg text-blue-700">Grand Total: {{ totalAmount.toFixed(2) }} BDT</p>
                    </div>

                    <Divider />

                    <!-- Justification -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-lg">Justification</h3>
                            <Button label="Edit" text size="small" @click="goToStep(2)" />
                        </div>
                        <div class="text-sm space-y-2 bg-gray-50 p-3 rounded">
                            <div>
                                <p class="text-gray-500 font-semibold">Purpose:</p>
                                <p class="whitespace-pre-wrap">{{ form.purpose }}</p>
                            </div>
                            <div v-if="form.justification">
                                <p class="text-gray-500 font-semibold">Additional Details:</p>
                                <p class="whitespace-pre-wrap">{{ form.justification }}</p>
                            </div>
                            <p v-if="form.attachments?.length" class="text-gray-500">
                                Attachments: {{ form.attachments.length }} file(s)
                            </p>
                        </div>
                    </div>

                </template>
            </Card>

            <!-- Navigation bar -->
            <Card>
                <template #content>
                    <div class="flex justify-between items-center">
                        <Button
                            v-if="activeStep > 0"
                            label="Previous"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            @click="prevStep"
                            :disabled="isSubmitting"
                        />
                        <div v-else />

                        <div class="flex gap-3">
                            <Button 
                                label="Save as Draft" 
                                icon="pi pi-save" 
                                severity="secondary" 
                                outlined 
                                @click="saveDraft" 
                                :loading="isSubmitting"
                                :disabled="!form.department_id || !form.branch_id"
                            />
                            <Button
                                v-if="activeStep < steps.length - 1"
                                label="Next"
                                icon="pi pi-arrow-right"
                                iconPos="right"
                                @click="nextStep"
                                :disabled="isSubmitting"
                            />
                            <Button
                                v-else
                                label="Submit for Approval"
                                icon="pi pi-check"
                                severity="success"
                                @click="submitForApproval"
                                :loading="isSubmitting"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Auto-save indicator -->
            <p v-if="lastSavedAt" class="mt-3 text-center text-xs text-gray-400">
                Draft auto-saved at {{ lastSavedAt.toLocaleTimeString() }}
            </p>

        </div>

        <ConfirmDialog />

    </TenantLayout>
</template>