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
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import { useToast } from 'primevue/usetoast';

const props = defineProps({ item: Object, categories: Array });
const toast = useToast();
const photoPreview = ref(props.item.photo_url);
const newPhotoPreview = ref(null);

const form = useForm({
    name: props.item.name, description: props.item.description || '', category_id: props.item.category_id,
    type: props.item.type, unit: props.item.unit, unit_secondary: props.item.unit_secondary || '',
    conversion_factor: props.item.conversion_factor, current_price: props.item.current_price,
    min_stock_level: props.item.min_stock_level || 0, max_stock_level: props.item.max_stock_level || 0,
    reorder_level: props.item.reorder_level || 0, lead_time_days: props.item.lead_time_days || 7,
    is_consumable: props.item.is_consumable, is_asset: props.item.is_asset,
    asset_threshold_amount: props.item.asset_threshold_amount, brand: props.item.brand || '',
    model: props.item.model || '', manufacturer: props.item.manufacturer || '',
    specifications: props.item.specifications || '', photo: null, barcode: props.item.barcode || '',
    sku: props.item.sku || '', status: props.item.status, notes: props.item.notes || '',
});

const typeOptions = [{ label: 'Consumable', value: 'consumable' }, { label: 'Asset', value: 'asset' }, { label: 'Both', value: 'both' }];
const unitOptions = [{ label: 'Pieces (pcs)', value: 'pcs' }, { label: 'Kilogram (kg)', value: 'kg' }, { label: 'Liter (l)', value: 'l' }, { label: 'Box', value: 'box' }];
const statusOptions = [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }, { label: 'Discontinued', value: 'discontinued' }];

const onPhotoSelect = (event) => {
    const file = event.files[0];
    if (file) {
        form.photo = file;
        newPhotoPreview.value = URL.createObjectURL(file);
    }
};

const removeNewPhoto = () => {
    form.photo = null;
    newPhotoPreview.value = null;
};

const submit = () => {
    form.post(route('tenant.items.update', props.item.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Success', detail: 'Item updated successfully', life: 3000 }),
    });
};

const cancel = () => {
    if (form.isDirty && confirm('Are you sure? Any unsaved changes will be lost.')) {
        window.history.back();
    } else if (!form.isDirty) {
        window.history.back();
    }
};
</script>

<template>
    <TenantLayout title="Edit Item">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div><h1 class="text-3xl font-bold text-gray-900">Edit Item</h1><p class="text-gray-600 mt-1">Update item information for {{ item.name }}</p><div class="mt-2"><Tag :value="item.code" severity="info" class="font-mono" /></div></div>
                    <div class="flex gap-2">
                        <Button label="Cancel" icon="pi pi-times" severity="secondary" @click="cancel" outlined />
                        <Button label="Save Changes" icon="pi pi-check" @click="submit" :loading="form.processing" :disabled="!form.isDirty" />
                    </div>
                </div>
                <Message v-if="form.isDirty" severity="warn" :closable="false">You have unsaved changes. Click "Save Changes" to update the item.</Message>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <Card><template #title>Basic Information</template><template #content><div class="grid grid-cols-2 gap-4"><div class="col-span-2"><label class="block text-sm font-medium mb-2">Item Name *</label><InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" /><small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small></div><div><label class="block text-sm font-medium mb-2">Category *</label><Dropdown v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" class="w-full" filter /></div><div><label class="block text-sm font-medium mb-2">Type *</label><Dropdown v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" /></div><div class="col-span-2"><label class="block text-sm font-medium mb-2">Status</label><SelectButton v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" /></div></div></template></Card>
                        <Card><template #title>Pricing & Stock</template><template #content><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-2">Price *</label><InputNumber v-model="form.current_price" mode="currency" currency="BDT" locale="en-BD" class="w-full" /></div><div><label class="block text-sm font-medium mb-2">Min Stock</label><InputNumber v-model="form.min_stock_level" class="w-full" /></div><div><label class="block text-sm font-medium mb-2">Max Stock</label><InputNumber v-model="form.max_stock_level" class="w-full" /></div><div><label class="block text-sm font-medium mb-2">Reorder Level</label><InputNumber v-model="form.reorder_level" class="w-full" /></div></div></template></Card>
                    </div>
                    <div class="space-y-6">
                        <Card><template #title>Photo</template><template #content><div class="space-y-4"><div v-if="photoPreview && !newPhotoPreview"><p class="text-sm font-medium mb-2">Current Photo:</p><Image :src="photoPreview" alt="Current photo" width="250" preview class="w-full rounded" /></div><div v-if="newPhotoPreview" class="relative"><p class="text-sm font-medium mb-2">New Photo:</p><Image :src="newPhotoPreview" alt="New photo" width="250" preview class="w-full rounded" /><Button icon="pi pi-times" severity="danger" rounded size="small" class="absolute top-2 right-2" @click="removeNewPhoto" /></div><FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" @select="onPhotoSelect" :chooseLabel="photoPreview ? 'Change Photo' : 'Upload Photo'" class="w-full" :auto="true" /><small class="text-gray-500 block">Max size: 2MB</small></div></template></Card>
                        <Card><template #title>Item Information</template><template #content><div class="space-y-3 text-sm"><div><span class="text-gray-600">Item Code:</span><span class="font-mono font-medium ml-2">{{ item.code }}</span></div><Divider /><div v-if="item.creator"><span class="text-gray-600">Created by:</span><span class="ml-2">{{ item.creator.name }}</span></div><div><span class="text-gray-600">Created on:</span><span class="ml-2">{{ new Date(item.created_at).toLocaleDateString() }}</span></div></div></template></Card>
                    </div>
                </div>
            </form>
        </div>
        <Toast />
    </TenantLayout>
</template>