<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    availablePOs: Array,
    selectedPO: Object,
    users: Array,
    branches: Array,
});

const toast = useToast();

// Build initial items if PO pre-selected
const buildItems = (po) => po?.items?.map(poi => ({
    purchase_order_item_id: poi.id,
    item_id:    poi.item.id,
    item_name:  poi.item_name || poi.item.name,
    unit:       poi.unit || poi.item.unit,
    unit_price: parseFloat(poi.unit_price || 0),
    quantity_ordered:  parseFloat(poi.quantity || 0),
    quantity_received: parseFloat(poi.pending_quantity ?? poi.quantity ?? 0),
    quantity_accepted: parseFloat(poi.pending_quantity ?? poi.quantity ?? 0),
    quantity_rejected: 0,
    rejection_reason:  '',
    is_asset:   poi.item.is_asset,
    threshold:  parseFloat(poi.item.asset_threshold_amount || 5000),
})) ?? [];

const form = useForm({
    purchase_order_id:      props.selectedPO?.id ?? null,
    receipt_date:           new Date().toISOString().split('T')[0],
    received_by:            null,
    supplier_invoice_no:    '',
    supplier_delivery_note: '',
    vehicle_number:         '',
    overall_status:         'passed',
    quality_checked_by:     null,
    quality_remarks:        '',
    notes:                  '',
    items:                  buildItems(props.selectedPO),
});

const selectedPOData = ref(props.selectedPO);
const uploadedPhotos  = ref([]);

// When PO is selected, load its items
watch(() => form.purchase_order_id, (poId) => {
    const po = props.availablePOs.find(p => p.id === poId);
    selectedPOData.value = po;
    form.items = buildItems(po);
});

// Auto-compute rejected quantity
const updateRejected = (index) => {
    const item = form.items[index];
    item.quantity_rejected = Math.max(0,
        parseFloat(item.quantity_received || 0) - parseFloat(item.quantity_accepted || 0)
    );
    // Auto-update overall status
    autoStatus();
};

const autoStatus = () => {
    const hasRejects = form.items.some(i => parseFloat(i.quantity_rejected) > 0);
    const allAccepted = form.items.every(i =>
        parseFloat(i.quantity_accepted) >= parseFloat(i.quantity_ordered)
    );
    if (!hasRejects && allAccepted) form.overall_status = 'passed';
    else if (hasRejects && form.items.some(i => parseFloat(i.quantity_accepted) > 0)) form.overall_status = 'partial';
    else if (hasRejects && form.items.every(i => parseFloat(i.quantity_accepted) === 0)) form.overall_status = 'failed';
};

const statusOptions = [
    { label: 'Passed',  value: 'passed'  },
    { label: 'Partial', value: 'partial' },
    { label: 'Failed',  value: 'failed'  },
];

// Assets preview
const assetItems = computed(() =>
    form.items.filter(i => i.is_asset && i.unit_price >= i.threshold && parseFloat(i.quantity_accepted) > 0)
);

const totalValue = computed(() =>
    form.items.reduce((s, i) => s + (parseFloat(i.quantity_accepted) * parseFloat(i.unit_price)), 0)
);

const formatCurrency = (v) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(v || 0);

const onPhotoSelect = (event) => {
    uploadedPhotos.value = event.files;
};

const submit = () => {
    // Use FormData for file uploads
    const formData = new FormData();

    // Append scalar fields
    const fields = ['purchase_order_id','receipt_date','received_by','supplier_invoice_no',
        'supplier_delivery_note','vehicle_number','overall_status','quality_checked_by',
        'quality_remarks','notes'];
    fields.forEach(f => { if (form[f] !== null && form[f] !== '') formData.append(f, form[f]); });

    // Append items as JSON-like indexed fields
    form.items.forEach((item, i) => {
        Object.entries(item).forEach(([k, v]) => {
            if (!['is_asset','threshold'].includes(k)) {
                formData.append(`items[${i}][${k}]`, v ?? '');
            }
        });
    });

    // Append photos
    uploadedPhotos.value.forEach(file => formData.append('photos[]', file));

    form.transform(() => formData)
        .post(route('tenant.grn.store'), {
            forceFormData: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Created', detail: 'GRN created successfully.', life: 3000 }),
            onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix the errors.', life: 3000 }),
        });
};
</script>

<template>
    <TenantLayout title="Receive Goods">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Receive Goods</h1>
                    <p class="text-gray-600 mt-1">Record goods received against a purchase order</p>
                </div>
                <div class="flex gap-2">
                    <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined
                        @click="router.visit(route('tenant.grn.index'))" />
                    <Button label="Save GRN" icon="pi pi-check" @click="submit" :loading="form.processing"
                        :disabled="!form.purchase_order_id || form.items.length === 0" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- PO Selection -->
                    <Card>
                        <template #title>Purchase Order</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Select PO <span class="text-red-500">*</span></label>
                                    <Dropdown
                                        v-model="form.purchase_order_id"
                                        :options="availablePOs"
                                        optionLabel="po_number"
                                        optionValue="id"
                                        placeholder="Select Purchase Order"
                                        class="w-full"
                                        :class="{'p-invalid': form.errors.purchase_order_id}"
                                        filter
                                    >
                                        <template #option="{ option }">
                                            <div>
                                                <span class="font-mono font-medium">{{ option.po_number }}</span>
                                                <span class="text-gray-500 ml-2 text-sm">{{ option.vendor?.name }}</span>
                                            </div>
                                        </template>
                                    </Dropdown>
                                    <small class="p-error" v-if="form.errors.purchase_order_id">{{ form.errors.purchase_order_id }}</small>
                                </div>

                                <div v-if="selectedPOData" class="md:col-span-2">
                                    <div class="bg-blue-50 rounded p-3 text-sm flex flex-wrap gap-4">
                                        <div><span class="text-gray-500">Vendor:</span> <strong>{{ selectedPOData.vendor?.name }}</strong></div>
                                        <div><span class="text-gray-500">Branch:</span> <strong>{{ selectedPOData.branch?.name }}</strong></div>
                                        <div><span class="text-gray-500">PO Value:</span> <strong>{{ formatCurrency(selectedPOData.total_amount) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Receipt Details -->
                    <Card>
                        <template #title>Receipt Details</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Receipt Date <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.receipt_date" type="date" class="w-full"
                                        :class="{'p-invalid': form.errors.receipt_date}" />
                                    <small class="p-error" v-if="form.errors.receipt_date">{{ form.errors.receipt_date }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Received By <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.received_by" :options="users" optionLabel="name" optionValue="id"
                                        placeholder="Select receiver" class="w-full" filter
                                        :class="{'p-invalid': form.errors.received_by}" />
                                    <small class="p-error" v-if="form.errors.received_by">{{ form.errors.received_by }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Supplier Invoice #</label>
                                    <InputText v-model="form.supplier_invoice_no" placeholder="INV-2026-001" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Delivery Note #</label>
                                    <InputText v-model="form.supplier_delivery_note" placeholder="DN-001" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Vehicle Number</label>
                                    <InputText v-model="form.vehicle_number" placeholder="Dhaka Metro-X-00000" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Items Table -->
                    <Card v-if="form.items.length > 0">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-list text-blue-600"></i>
                                Items to Receive
                            </div>
                        </template>
                        <template #content>
                            <small class="p-error block mb-3" v-if="form.errors.items">{{ form.errors.items }}</small>

                            <DataTable :value="form.items" stripedRows class="text-sm">
                                <Column field="item_name" header="Item">
                                    <template #body="{ data }">
                                        <div>
                                            <div class="font-medium">{{ data.item_name }}</div>
                                            <div class="flex gap-1 mt-1">
                                                <Tag v-if="data.is_asset && data.unit_price >= data.threshold"
                                                    value="Asset" severity="warning" class="text-xs" />
                                                <span class="text-xs text-gray-400">{{ data.unit }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="quantity_ordered" header="Ordered" style="width:90px">
                                    <template #body="{ data }">
                                        <span class="font-medium">{{ data.quantity_ordered }}</span>
                                    </template>
                                </Column>
                                <Column header="Received" style="width:110px">
                                    <template #body="{ data, index }">
                                        <InputNumber v-model="form.items[index].quantity_received"
                                            :min="0" :max="data.quantity_ordered"
                                            inputStyle="width:80px"
                                            @input="updateRejected(index)" />
                                    </template>
                                </Column>
                                <Column header="Accepted" style="width:110px">
                                    <template #body="{ data, index }">
                                        <InputNumber v-model="form.items[index].quantity_accepted"
                                            :min="0" :max="form.items[index].quantity_received"
                                            inputStyle="width:80px"
                                            @input="updateRejected(index)" />
                                    </template>
                                </Column>
                                <Column header="Rejected" style="width:80px">
                                    <template #body="{ data }">
                                        <span :class="data.quantity_rejected > 0 ? 'text-red-600 font-bold' : 'text-gray-400'">
                                            {{ data.quantity_rejected }}
                                        </span>
                                    </template>
                                </Column>
                                <Column header="Rejection Reason">
                                    <template #body="{ data, index }">
                                        <InputText v-if="data.quantity_rejected > 0"
                                            v-model="form.items[index].rejection_reason"
                                            placeholder="Reason…" class="w-full text-xs"
                                            :class="{'p-invalid': !data.rejection_reason && data.quantity_rejected > 0}" />
                                        <span v-else class="text-gray-300">—</span>
                                    </template>
                                </Column>
                            </DataTable>

                            <!-- Asset alert -->
                            <Message v-if="assetItems.length > 0" severity="warn" :closable="false" class="mt-4">
                                <strong>{{ assetItems.length }} asset item(s)</strong> will be auto-registered in the Asset Register after saving.
                            </Message>
                        </template>
                    </Card>

                    <!-- Quality Check -->
                    <Card>
                        <template #title>Quality Inspection</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Overall Status <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.overall_status" :options="statusOptions"
                                        optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Quality Checked By</label>
                                    <Dropdown v-model="form.quality_checked_by" :options="users"
                                        optionLabel="name" optionValue="id" placeholder="Select inspector"
                                        class="w-full" filter />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Quality Remarks</label>
                                    <Textarea v-model="form.quality_remarks" rows="2"
                                        placeholder="Inspection observations…" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Photos -->
                    <Card>
                        <template #title>Photos</template>
                        <template #content>
                            <FileUpload
                                mode="advanced"
                                :multiple="true"
                                accept="image/*"
                                :maxFileSize="5000000"
                                @select="onPhotoSelect"
                                :auto="false"
                                chooseLabel="Choose Photos"
                                class="w-full"
                            >
                                <template #empty>
                                    <div class="text-center py-6 text-gray-400">
                                        <i class="pi pi-camera text-4xl mb-2"></i>
                                        <p>Drag & drop photos here or click Choose Photos</p>
                                        <p class="text-xs mt-1">Max 5MB per file</p>
                                    </div>
                                </template>
                            </FileUpload>
                        </template>
                    </Card>

                    <!-- Notes -->
                    <Card>
                        <template #title>Notes</template>
                        <template #content>
                            <Textarea v-model="form.notes" rows="3" placeholder="Any additional notes…" class="w-full" />
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <template #title>Receipt Summary</template>
                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Items</span>
                                    <span class="font-medium">{{ form.items.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Accepted</span>
                                    <span class="font-medium text-green-600">
                                        {{ form.items.reduce((s,i) => s + parseFloat(i.quantity_accepted||0), 0) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Rejected</span>
                                    <span class="font-medium text-red-600">
                                        {{ form.items.reduce((s,i) => s + parseFloat(i.quantity_rejected||0), 0) }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-600 font-medium">Received Value</span>
                                    <span class="font-bold text-blue-700">{{ formatCurrency(totalValue) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Quality Status</span>
                                    <Tag :value="form.overall_status" :severity="form.overall_status==='passed'?'success':form.overall_status==='failed'?'danger':'warning'" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card v-if="assetItems.length > 0">
                        <template #title>
                            <span class="text-orange-600">Assets to be Created</span>
                        </template>
                        <template #content>
                            <div class="space-y-2">
                                <div v-for="item in assetItems" :key="item.item_id" class="text-sm p-2 bg-orange-50 rounded">
                                    <div class="font-medium">{{ item.item_name }}</div>
                                    <div class="text-gray-500">
                                        {{ item.quantity_accepted }} × {{ formatCurrency(item.unit_price) }}
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    These items exceed the asset threshold and will be registered automatically.
                                </p>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #content>
                            <div class="space-y-2">
                                <Button label="Save GRN" icon="pi pi-check" class="w-full"
                                    @click="submit" :loading="form.processing"
                                    :disabled="!form.purchase_order_id || form.items.length === 0" />
                                <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined class="w-full"
                                    @click="router.visit(route('tenant.grn.index'))" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <Toast />
    </TenantLayout>
</template>