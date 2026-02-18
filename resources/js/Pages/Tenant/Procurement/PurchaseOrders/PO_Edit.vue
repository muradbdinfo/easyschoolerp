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
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import AutoComplete from 'primevue/autocomplete';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    purchaseOrder: Object,
    vendors: Array,
    branches: Array,
    departments: Array,
    items: Array,
});

const toast = useToast();

const form = useForm({
    vendor_id:              props.purchaseOrder.vendor_id,
    branch_id:              props.purchaseOrder.branch_id,
    department_id:          props.purchaseOrder.department_id,
    expected_delivery_date: props.purchaseOrder.expected_delivery_date || '',
    delivery_address:       props.purchaseOrder.delivery_address || '',
    vat_percentage:         parseFloat(props.purchaseOrder.vat_percentage) || 0,
    freight_charges:        parseFloat(props.purchaseOrder.freight_charges) || 0,
    discount_amount:        parseFloat(props.purchaseOrder.discount_amount) || 0,
    payment_terms:          props.purchaseOrder.payment_terms || '',
    payment_terms_days:     props.purchaseOrder.payment_terms_days || 30,
    terms_conditions:       props.purchaseOrder.terms_conditions || '',
    notes:                  props.purchaseOrder.notes || '',
    items: props.purchaseOrder.items.map(i => ({
        item_id:        i.item_id,
        item_name:      i.item_name,
        unit:           i.unit,
        quantity:       parseFloat(i.quantity),
        unit_price:     parseFloat(i.unit_price),
        specifications: i.specifications || '',
        pr_item_id:     i.purchase_requisition_item_id,
    })),
});

// Item search
const selectedItem = ref(null);
const addItemQty   = ref(1);
const addItemPrice = ref(0);
const filteredItems = ref([]);

const searchItems = (event) => {
    filteredItems.value = props.items.filter(i =>
        i.name.toLowerCase().includes(event.query.toLowerCase())
    );
};

watch(selectedItem, (item) => {
    if (item) addItemPrice.value = item.current_price || 0;
});

const addItem = () => {
    if (!selectedItem.value) return;
    form.items.push({
        item_id: selectedItem.value.id,
        item_name: selectedItem.value.name,
        unit: selectedItem.value.unit,
        quantity: addItemQty.value,
        unit_price: addItemPrice.value,
        specifications: '',
        pr_item_id: null,
    });
    selectedItem.value = null;
    addItemQty.value = 1;
    addItemPrice.value = 0;
};

const removeItem = (index) => form.items.splice(index, 1);

// Totals
const subtotal   = computed(() => form.items.reduce((s, i) => s + i.quantity * i.unit_price, 0));
const vatAmount  = computed(() => subtotal.value * (form.vat_percentage / 100));
const grandTotal = computed(() => subtotal.value + vatAmount.value + (form.freight_charges || 0) - (form.discount_amount || 0));

const formatCurrency = (val) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(val || 0);

const submit = () => {
    form.put(route('tenant.purchase-orders.update', props.purchaseOrder.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Updated', detail: 'PO updated.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix the errors.', life: 3000 }),
    });
};
</script>

<template>
    <TenantLayout title="Edit Purchase Order">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Edit Purchase Order</h1>
                    <p class="text-gray-600 mt-1 font-mono text-lg">{{ purchaseOrder.po_number }}</p>
                </div>
                <div class="flex gap-2">
                    <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined
                        @click="router.visit(route('tenant.purchase-orders.show', purchaseOrder.id))" />
                    <Button label="Save Changes" icon="pi pi-check" @click="submit"
                        :loading="form.processing" :disabled="!form.isDirty" />
                </div>
            </div>

            <Message v-if="form.isDirty" severity="warn" :closable="false" class="mb-4">
                You have unsaved changes.
            </Message>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">

                    <Card>
                        <template #title>Order Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Vendor <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.vendor_id" :options="vendors" optionLabel="name" optionValue="id" placeholder="Select Vendor" class="w-full" filter :class="{'p-invalid': form.errors.vendor_id}" />
                                    <small class="p-error" v-if="form.errors.vendor_id">{{ form.errors.vendor_id }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Branch <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" class="w-full" :class="{'p-invalid': form.errors.branch_id}" />
                                    <small class="p-error" v-if="form.errors.branch_id">{{ form.errors.branch_id }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Department</label>
                                    <Dropdown v-model="form.department_id" :options="departments" optionLabel="name" optionValue="id" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Expected Delivery</label>
                                    <InputText v-model="form.expected_delivery_date" type="date" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Payment Terms (Days)</label>
                                    <InputNumber v-model="form.payment_terms_days" :min="0" :max="365" suffix=" days" class="w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Delivery Address</label>
                                    <Textarea v-model="form.delivery_address" rows="2" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Order Items</template>
                        <template #content>
                            <div class="flex gap-2 mb-4 flex-wrap items-end">
                                <div class="flex-1 min-w-48">
                                    <label class="block text-sm font-medium mb-1">Add Item</label>
                                    <AutoComplete v-model="selectedItem" :suggestions="filteredItems" @complete="searchItems" optionLabel="name" placeholder="Search items…" class="w-full" forceSelection />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Qty</label>
                                    <InputNumber v-model="addItemQty" :min="0.01" style="width:90px" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Unit Price</label>
                                    <InputNumber v-model="addItemPrice" :min="0" style="width:120px" />
                                </div>
                                <Button icon="pi pi-plus" label="Add" @click="addItem" :disabled="!selectedItem" />
                            </div>

                            <DataTable :value="form.items" stripedRows>
                                <template #empty><div class="text-center py-6 text-gray-400">No items added</div></template>
                                <Column field="item_name" header="Item" />
                                <Column field="unit" header="Unit" style="width:80px" />
                                <Column header="Qty" style="width:120px">
                                    <template #body="{ index }">
                                        <InputNumber v-model="form.items[index].quantity" :min="0.01" inputStyle="width:80px" />
                                    </template>
                                </Column>
                                <Column header="Unit Price" style="width:140px">
                                    <template #body="{ index }">
                                        <InputNumber v-model="form.items[index].unit_price" :min="0" inputStyle="width:100px" />
                                    </template>
                                </Column>
                                <Column header="Total">
                                    <template #body="{ data }">
                                        <span class="font-medium">{{ formatCurrency(data.quantity * data.unit_price) }}</span>
                                    </template>
                                </Column>
                                <Column style="width:50px">
                                    <template #body="{ index }">
                                        <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeItem(index)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Terms & Notes</template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Terms & Conditions</label>
                                    <Textarea v-model="form.terms_conditions" rows="3" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Notes</label>
                                    <Textarea v-model="form.notes" rows="2" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <template #title>Order Summary</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">VAT (%)</label>
                                    <InputNumber v-model="form.vat_percentage" :min="0" :max="100" suffix="%" class="w-full" />
                                </div>
                                <div class="flex justify-between text-sm text-gray-500">
                                    <span>VAT Amount</span><span>{{ formatCurrency(vatAmount) }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Freight</label>
                                    <InputNumber v-model="form.freight_charges" :min="0" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Discount</label>
                                    <InputNumber v-model="form.discount_amount" :min="0" class="w-full" />
                                </div>
                                <Divider />
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Grand Total</span>
                                    <span class="text-blue-700">{{ formatCurrency(grandTotal) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>PO Info</template>
                        <template #content>
                            <div class="text-sm space-y-2">
                                <div><span class="text-gray-500">PO#:</span> <span class="font-mono font-medium">{{ purchaseOrder.po_number }}</span></div>
                                <div><span class="text-gray-500">Date:</span> {{ new Date(purchaseOrder.po_date).toLocaleDateString() }}</div>
                                <div><span class="text-gray-500">Created by:</span> {{ purchaseOrder.creator?.name }}</div>
                                <Divider />
                                <Tag :value="purchaseOrder.status_badge.label" :severity="purchaseOrder.status_badge.severity" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <Toast />
    </TenantLayout>
</template>