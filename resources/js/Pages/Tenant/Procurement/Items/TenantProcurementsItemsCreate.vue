<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';
import SelectButton from 'primevue/selectbutton';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({ categories: Array });
const toast = useToast();
const photoPreview = ref(null);

const form = useForm({
    name: '', description: '', category_id: null, type: 'consumable', unit: 'pcs', unit_secondary: '', conversion_factor: null,
    current_price: 0, min_stock_level: 0, max_stock_level: 0, reorder_level: 0, lead_time_days: 7,
    is_consumable: true, is_asset: false, asset_threshold_amount: null,
    brand: '', model: '', manufacturer: '', specifications: '', photo: null, barcode: '', sku: '',
    status: 'active', notes: '',
});

const typeOptions = [
    { label: 'Consumable', value: 'consumable' },
    { label: 'Asset', value: 'asset' },
    { label: 'Both', value: 'both' },
];

const unitOptions = [
    { label: 'Pieces (pcs)', value: 'pcs' }, { label: 'Kilogram (kg)', value: 'kg' }, { label: 'Liter (l)', value: 'l' },
    { label: 'Box', value: 'box' }, { label: 'Pack', value: 'pack' }, { label: 'Dozen', value: 'dozen' },
];

const statusOptions = [
    { label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }, { label: 'Discontinued', value: 'discontinued' },
];

const onPhotoSelect = (event) => {
    const file = event.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
};

const submit = () => {
    form.post(route('tenant.items.store'), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Success', detail: 'Item created successfully', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please check the form for errors', life: 3000 }),
    });
};

const cancel = () => {
    if (confirm('Are you sure? Any unsaved changes will be lost.')) window.history.back();
};

const updateTypeFlags = (type) => {
    form.is_consumable = type === 'consumable' || type === 'both';
    form.is_asset = type === 'asset' || type === 'both';
};
</script>

<template>
    <TenantLayout title="Create Item">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div><h1 class="text-3xl font-bold text-gray-900">Create New Item</h1><p class="text-gray-600 mt-1">Add a new item to your inventory</p></div>
                    <div class="flex gap-2">
                        <Button label="Cancel" icon="pi pi-times" severity="secondary" @click="cancel" outlined />
                        <Button label="Save Item" icon="pi pi-check" @click="submit" :loading="form.processing" />
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <template #title>Basic Information</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Item Name <span class="text-red-500">*</span></label><InputText v-model="form.name" placeholder="Enter item name" class="w-full" :class="{ 'p-invalid': form.errors.name }" /><small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small></div>
                                        <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Description</label><Textarea v-model="form.description" rows="3" placeholder="Enter item description" class="w-full" /></div>
                                        <div><label class="block text-sm font-medium mb-2">Category <span class="text-red-500">*</span></label><Dropdown v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" placeholder="Select Category" class="w-full" :class="{ 'p-invalid': form.errors.category_id }" filter /><small class="p-error" v-if="form.errors.category_id">{{ form.errors.category_id }}</small></div>
                                        <div><label class="block text-sm font-medium mb-2">Item Type <span class="text-red-500">*</span></label><Dropdown v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Select Type" class="w-full" @change="updateTypeFlags(form.type)" /></div>
                                        <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Status</label><SelectButton v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" /></div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Measurement</template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Unit <span class="text-red-500">*</span></label><Dropdown v-model="form.unit" :options="unitOptions" optionLabel="label" optionValue="value" placeholder="Select Unit" class="w-full" filter /></div>
                                    <div><label class="block text-sm font-medium mb-2">Secondary Unit</label><Dropdown v-model="form.unit_secondary" :options="unitOptions" optionLabel="label" optionValue="value" placeholder="Optional" class="w-full" showClear /><small class="text-gray-500">Optional dual unit</small></div>
                                    <div><label class="block text-sm font-medium mb-2">Conversion Factor</label><InputNumber v-model="form.conversion_factor" placeholder="1.0" class="w-full" :min="0" :minFractionDigits="2" :maxFractionDigits="4" /><small class="text-gray-500">Secondary to primary</small></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Pricing</template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Current Price <span class="text-red-500">*</span></label><InputNumber v-model="form.current_price" mode="currency" currency="BDT" locale="en-BD" placeholder="0.00" class="w-full" :class="{ 'p-invalid': form.errors.current_price }" /><small class="p-error" v-if="form.errors.current_price">{{ form.errors.current_price }}</small></div>
                                    <div v-if="form.type === 'asset' || form.type === 'both'"><label class="block text-sm font-medium mb-2">Asset Threshold Amount</label><InputNumber v-model="form.asset_threshold_amount" mode="currency" currency="BDT" locale="en-BD" placeholder="5000.00" class="w-full" /><small class="text-gray-500">If purchase > this amount, create asset</small></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Stock Management</template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Minimum Stock Level</label><InputNumber v-model="form.min_stock_level" placeholder="0" class="w-full" :min="0" /><small class="text-gray-500">Alert when below this</small></div>
                                    <div><label class="block text-sm font-medium mb-2">Maximum Stock Level</label><InputNumber v-model="form.max_stock_level" placeholder="0" class="w-full" :min="0" /><small class="text-gray-500">Target maximum stock</small></div>
                                    <div><label class="block text-sm font-medium mb-2">Reorder Level</label><InputNumber v-model="form.reorder_level" placeholder="0" class="w-full" :min="0" /><small class="text-gray-500">When to reorder</small></div>
                                    <div><label class="block text-sm font-medium mb-2">Lead Time (Days)</label><InputNumber v-model="form.lead_time_days" placeholder="7" class="w-full" :min="0" :max="365" suffix=" days" /><small class="text-gray-500">Delivery time after order</small></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Product Details</template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium mb-2">Brand</label><InputText v-model="form.brand" placeholder="Enter brand name" class="w-full" /></div>
                                    <div><label class="block text-sm font-medium mb-2">Model</label><InputText v-model="form.model" placeholder="Enter model number" class="w-full" /></div>
                                    <div><label class="block text-sm font-medium mb-2">Manufacturer</label><InputText v-model="form.manufacturer" placeholder="Enter manufacturer" class="w-full" /></div>
                                    <div><label class="block text-sm font-medium mb-2">Barcode</label><InputText v-model="form.barcode" placeholder="Enter barcode" class="w-full" :class="{ 'p-invalid': form.errors.barcode }" /><small class="p-error" v-if="form.errors.barcode">{{ form.errors.barcode }}</small></div>
                                    <div><label class="block text-sm font-medium mb-2">SKU</label><InputText v-model="form.sku" placeholder="Enter SKU" class="w-full" /></div>
                                    <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Specifications</label><Textarea v-model="form.specifications" rows="4" placeholder="Enter detailed specifications" class="w-full" /></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Additional Information</template>
                            <template #content>
                                <div><label class="block text-sm font-medium mb-2">Notes</label><Textarea v-model="form.notes" rows="5" placeholder="Any additional notes about this item" class="w-full" /></div>
                            </template>
                        </Card>
                    </div>

                    <div class="space-y-6">
                        <Card>
                            <template #title>Item Photo</template>
                            <template #content>
                                <div class="space-y-4">
                                    <div v-if="photoPreview" class="relative"><Image :src="photoPreview" alt="Item preview" width="250" preview class="w-full rounded" /><Button icon="pi pi-times" severity="danger" rounded size="small" class="absolute top-2 right-2" @click="removePhoto" /></div>
                                    <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" @select="onPhotoSelect" chooseLabel="Choose Photo" class="w-full" :auto="true" />
                                    <small class="text-gray-500 block">Max size: 2MB<br>Formats: JPG, PNG, WEBP</small>
                                    <small class="p-error" v-if="form.errors.photo">{{ form.errors.photo }}</small>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Quick Tips</template>
                            <template #content>
                                <div class="space-y-3 text-sm text-gray-600">
                                    <div class="flex gap-2"><i class="pi pi-check text-green-500 mt-0.5"></i><span>Item code will be auto-generated (ITM-00001)</span></div>
                                    <div class="flex gap-2"><i class="pi pi-check text-green-500 mt-0.5"></i><span>Set reorder level to get low stock alerts</span></div>
                                    <div class="flex gap-2"><i class="pi pi-check text-green-500 mt-0.5"></i><span>Photo is optional but recommended</span></div>
                                    <div class="flex gap-2"><i class="pi pi-check text-green-500 mt-0.5"></i><span>Asset threshold triggers automatic asset creation</span></div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </form>
        </div>

        <Toast />
    </TenantLayout>
</template>