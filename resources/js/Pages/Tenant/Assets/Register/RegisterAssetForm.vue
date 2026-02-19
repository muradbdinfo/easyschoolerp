<script setup>
import { ref, watch, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import FileUpload from 'primevue/fileupload';
import { Building, User, Tag, TrendingDown, Shield, Camera, FileText } from 'lucide-vue-next';

const props = defineProps({
    form:       Object,
    categories: Array,
    branches:   Array,
    vendors:    Array,
    users:      Array,
    errors:     Object,
    isEdit:     Boolean,
});

const emit = defineEmits(['submit']);

const depreciationMethods = [
    { label: 'Straight Line (SLM)', value: 'slm' },
    { label: 'Written Down Value (WDV)', value: 'wdv' },
    { label: 'No Depreciation', value: 'none' },
];

const statusOptions = [
    { label: 'Active',            value: 'active' },
    { label: 'Under Maintenance', value: 'under_maintenance' },
    { label: 'Disposed',          value: 'disposed' },
    { label: 'Lost',              value: 'lost' },
    { label: 'Damaged',           value: 'damaged' },
    { label: 'Written Off',       value: 'written_off' },
];

const conditionOptions = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good',      value: 'good' },
    { label: 'Fair',      value: 'fair' },
    { label: 'Poor',      value: 'poor' },
];

const floorOptions = ['Ground', '1st', '2nd', '3rd', '4th', '5th'].map(f => ({ label: f + ' Floor', value: f }));

// Auto-fill depreciation from category
function onCategoryChange(catId) {
    const cat = props.categories.find(c => c.id === catId);
    if (cat && !props.isEdit) {
        props.form.depreciation_method    = cat.depreciation_method;
        props.form.depreciation_rate      = parseFloat(cat.depreciation_rate);
        props.form.useful_life_years      = cat.useful_life_years;
        props.form.residual_value_percent = parseFloat(cat.residual_value_percent);
    }
}

// Photo preview
const photoPreview = ref(null);
function onPhotoSelect(event) {
    const file = event.files[0];
    if (!file) return;
    props.form.primary_photo = file;
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target.result; };
    reader.readAsDataURL(file);
}

const err = (field) => props.form.errors?.[field] || props.errors?.[field];
</script>

<template>
    <div class="space-y-4">
        <!-- Basic Information -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <Tag :size="18" class="text-blue-500" />
                    <h3 class="font-semibold text-gray-900">Basic Information</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label">Asset Name *</label>
                        <InputText v-model="form.name" class="w-full" :invalid="!!err('name')" placeholder="e.g. Dell Optiplex 7090" />
                        <p v-if="err('name')" class="form-error">{{ err('name') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Category *</label>
                        <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id"
                            placeholder="Select category" class="w-full" :invalid="!!err('category_id')"
                            @change="onCategoryChange(form.category_id)" />
                        <p v-if="err('category_id')" class="form-error">{{ err('category_id') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Status *</label>
                        <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Condition *</label>
                        <Select v-model="form.condition" :options="conditionOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div v-if="isEdit">
                        <label class="form-label">Asset Tag</label>
                        <InputText :value="form.asset_tag" class="w-full bg-gray-50" disabled />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Acquisition Details -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <FileText :size="18" class="text-green-500" />
                    <h3 class="font-semibold text-gray-900">Acquisition Details</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Acquisition Date *</label>
                        <DatePicker v-model="form.acquisition_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                        <p v-if="err('acquisition_date')" class="form-error">{{ err('acquisition_date') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Acquisition Cost (৳) *</label>
                        <InputNumber v-model="form.acquisition_cost" :min="0" :maxFractionDigits="2"
                            class="w-full" mode="currency" currency="BDT" locale="en-BD" />
                    </div>
                    <div>
                        <label class="form-label">Vendor</label>
                        <Select v-model="form.vendor_id" :options="vendors" optionLabel="name" optionValue="id"
                            placeholder="Select vendor" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Invoice Number</label>
                        <InputText v-model="form.invoice_number" class="w-full" placeholder="INV-001" />
                    </div>
                    <div>
                        <label class="form-label">PO Number</label>
                        <InputText v-model="form.po_number" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">GRN Number</label>
                        <InputText v-model="form.grn_number" class="w-full" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Product Details -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <h3 class="font-semibold text-gray-900">Product Details</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Brand</label>
                        <InputText v-model="form.brand" class="w-full" placeholder="e.g. Dell" />
                    </div>
                    <div>
                        <label class="form-label">Model Number</label>
                        <InputText v-model="form.model_number" class="w-full" placeholder="e.g. Optiplex 7090" />
                    </div>
                    <div>
                        <label class="form-label">Serial Number</label>
                        <InputText v-model="form.serial_number" class="w-full" :invalid="!!err('serial_number')" />
                        <p v-if="err('serial_number')" class="form-error">{{ err('serial_number') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Color</label>
                        <InputText v-model="form.color" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Specifications</label>
                        <Textarea v-model="form.specifications" rows="3" class="w-full" placeholder="RAM, Processor, etc." autoResize />
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Description / Notes</label>
                        <Textarea v-model="form.description" rows="2" class="w-full" autoResize />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Location -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <Building :size="18" class="text-purple-500" />
                    <h3 class="font-semibold text-gray-900">Location</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Branch</label>
                        <Select v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id"
                            placeholder="Select branch" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Building / Block</label>
                        <InputText v-model="form.building" class="w-full" placeholder="Main Building" />
                    </div>
                    <div>
                        <label class="form-label">Floor</label>
                        <Select v-model="form.floor" :options="floorOptions" optionLabel="label" optionValue="value"
                            placeholder="Select floor" class="w-full" showClear />
                    </div>
                    <div>
                        <label class="form-label">Room</label>
                        <InputText v-model="form.room" class="w-full" placeholder="Room 201" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Specific Location</label>
                        <InputText v-model="form.location_details" class="w-full" placeholder="Principal's office desk" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Custodian -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <User :size="18" class="text-indigo-500" />
                    <h3 class="font-semibold text-gray-900">Custodian</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Assigned To</label>
                        <Select v-model="form.custodian_id" :options="users" optionLabel="name" optionValue="id"
                            placeholder="Select user" class="w-full" showClear filter />
                    </div>
                    <div>
                        <label class="form-label">Assignment Date</label>
                        <DatePicker v-model="form.custodian_assigned_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Depreciation -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <TrendingDown :size="18" class="text-orange-500" />
                    <h3 class="font-semibold text-gray-900">Depreciation Settings</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Depreciation Method *</label>
                        <Select v-model="form.depreciation_method" :options="depreciationMethods"
                            optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Start Date</label>
                        <DatePicker v-model="form.depreciation_start_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                    </div>
                    <div>
                        <label class="form-label">Annual Rate (%) *</label>
                        <InputNumber v-model="form.depreciation_rate" :min="0" :max="100" suffix="%" class="w-full"
                            :disabled="form.depreciation_method === 'none'" />
                    </div>
                    <div>
                        <label class="form-label">Useful Life (Years) *</label>
                        <InputNumber v-model="form.useful_life_years" :min="1" :max="100" class="w-full"
                            :disabled="form.depreciation_method === 'none'" />
                    </div>
                    <div>
                        <label class="form-label">Residual Value (%)</label>
                        <InputNumber v-model="form.residual_value_percent" :min="0" :max="100" suffix="%" class="w-full"
                            :disabled="form.depreciation_method === 'none'" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Warranty & Insurance -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <Shield :size="18" class="text-teal-500" />
                    <h3 class="font-semibold text-gray-900">Warranty & Insurance</h3>
                </div>
            </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Warranty (Months)</label>
                        <InputNumber v-model="form.warranty_months" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Warranty Provider</label>
                        <InputText v-model="form.warranty_provider" class="w-full" />
                    </div>
                    <Divider class="md:col-span-2 my-0" />
                    <div>
                        <label class="form-label">Insurance Company</label>
                        <InputText v-model="form.insurance_company" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Policy Number</label>
                        <InputText v-model="form.insurance_policy_number" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Insured Value (৳)</label>
                        <InputNumber v-model="form.insured_value" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="form-label">Insurance Expiry</label>
                        <DatePicker v-model="form.insurance_expiry_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Photo -->
        <Card>
            <template #header>
                <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                    <Camera :size="18" class="text-pink-500" />
                    <h3 class="font-semibold text-gray-900">Primary Photo</h3>
                </div>
            </template>
            <template #content>
                <div class="flex gap-4 items-start">
                    <div v-if="photoPreview || form.primary_photo_url"
                        class="w-32 h-32 rounded-xl overflow-hidden border border-gray-200 flex-shrink-0">
                        <img :src="photoPreview || form.primary_photo_url" class="w-full h-full object-cover" />
                    </div>
                    <FileUpload mode="basic" accept="image/*" :maxFileSize="2097152"
                        chooseLabel="Choose Photo" @select="onPhotoSelect" auto
                        class="p-button-outlined" />
                </div>
            </template>
        </Card>

        <!-- Notes -->
        <div>
            <label class="form-label">Additional Notes</label>
            <Textarea v-model="form.notes" rows="3" class="w-full" autoResize />
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <Link :href="route('tenant.assets.index')">
                <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button :label="isEdit ? 'Update Asset' : 'Register Asset'" @click="emit('submit')"
                :loading="form.processing" />
        </div>
    </div>
</template>

<style scoped>
.form-label  { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-error  { @apply text-red-500 text-xs mt-1; }
</style>