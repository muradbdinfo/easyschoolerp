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
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    vendors: Array,
    branches: Array,
    departments: Array,
    items: Array,
    fromPR: Object,
});

const toast = useToast();

// Build initial items from PR if provided
const prItems = props.fromPR?.items?.map(pri => ({
    item_id:      pri.item.id,
    item_name:    pri.item.name,
    unit:         pri.item.unit,
    quantity:     pri.quantity,
    unit_price:   pri.item.current_price || 0,
    specifications: '',
    pr_item_id:   pri.id,
})) ?? [];

const form = useForm({
    vendor_id:               null,
    branch_id:               props.fromPR?.branch_id ?? null,
    department_id:           props.fromPR?.department_id ?? null,
    purchase_requisition_id: props.fromPR?.id ?? null,
    expected_delivery_date:  '',
    delivery_address:        '',
    vat_percentage:          0,
    freight_charges:         0,
    discount_amount:         0,
    payment_terms:           '',
    payment_terms_days:      30,
    terms_conditions:        '',
    notes:                   '',
    items:                   prItems,
});

// Item search
const itemQuery = ref('');
const filteredItems = ref([]);
const searchItems = (event) => {
    filteredItems.value = props.items.filter(i =>
        i.name.toLowerCase().includes(event.query.toLowerCase()) ||
        i.code?.toLowerCase().includes(event.query.toLowerCase())
    );
};

const selectedItem = ref(null);
const addItemQty   = ref(1);
const addItemPrice = ref(0);

const addItem = () => {
    if (!selectedItem.value) return;
    form.items.push({
        item_id:      selectedItem.value.id,
        item_name:    selectedItem.value.name,
        unit:         selectedItem.value.unit,
        quantity:     addItemQty.value,
        unit_price:   addItemPrice.value || selectedItem.value.current_price || 0,
        specifications: '',
        pr_item_id:   null,
    });
    selectedItem.value = null;
    addItemQty.value = 1;
    addItemPrice.value = 0;
    itemQuery.value = '';
};

watch(selectedItem, (item) => {
    if (item) addItemPrice.value = item.current_price || 0;
});

const removeItem = (index) => form.items.splice(index, 1);

// Auto-fill payment terms from vendor
watch(() => form.vendor_id, (id) => {
    const vendor = props.vendors.find(v => v.id === id);
    if (vendor) form.payment_terms_days = vendor.payment_terms_days || 30;
});

// Totals
const subtotal = computed(() =>
    form.items.reduce((s, i) => s + (i.quantity * i.unit_price), 0)
);
const vatAmount = computed(() => subtotal.value * (form.vat_percentage / 100));
const grandTotal = computed(() =>
    subtotal.value + vatAmount.value + (form.freight_charges || 0) - (form.discount_amount || 0)
);

const formatCurrency = (val) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(val || 0);

const submit = () => {
    form.transform(data => ({ ...data, items: form.items }))
        .post(route('tenant.purchase-orders.store'), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Created', detail: 'Purchase Order created.', life: 3000 }),
            onError: () => toast.add({ severity: 'error', summary: 'Error', detail: 'Please fix the errors.', life: 3000 }),
        });
};
</script>

<template>
    <TenantLayout title="Create Purchase Order">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create Purchase Order</h1>
                    <p class="text-gray-600 mt-1" v-if="fromPR">
                        From PR: <span class="font-mono font-medium">{{ fromPR.pr_number }}</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="router.visit(route('tenant.purchase-orders.index'))" />
                    <Button label="Create PO" icon="pi pi-check" @click="submit" :loading="form.processing" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Main content -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic Info -->
                    <Card>
                        <template #title>Order Information</template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Vendor <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.vendor_id" :options="vendors" optionLabel="name" optionValue="id" placeholder="Select Vendor" class="w-full" :class="{'p-invalid': form.errors.vendor_id}" filter />
                                    <small class="p-error" v-if="form.errors.vendor_id">{{ form.errors.vendor_id }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Branch <span class="text-red-500">*</span></label>
                                    <Dropdown v-model="form.branch_id" :options="branches" optionLabel="name" optionValue="id" placeholder="Select Branch" class="w-full" :class="{'p-invalid': form.errors.branch_id}" />
                                    <small class="p-error" v-if="form.errors.branch_id">{{ form.errors.branch_id }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Department</label>
                                    <Dropdown v-model="form.department_id" :options="departments" optionLabel="name" optionValue="id" placeholder="Select Department" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Expected Delivery Date</label>
                                    <InputText v-model="form.expected_delivery_date" type="date" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Payment Terms (Days)</label>
                                    <InputNumber v-model="form.payment_terms_days" :min="0" :max="365" suffix=" days" class="w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Delivery Address</label>
                                    <Textarea v-model="form.delivery_address" rows="2" placeholder="Delivery address…" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Items -->
                    <Card>
                        <template #title>Order Items</template>
                        <template #content>
                            <!-- Add Item Row -->
                            <div class="flex gap-2 mb-4 flex-wrap items-end">
                                <div class="flex-1 min-w-48">
                                    <label class="block text-sm font-medium mb-1">Search Item</label>
                                    <AutoComplete
                                        v-model="selectedItem"
                                        :suggestions="filteredItems"
                                        @complete="searchItems"
                                        optionLabel="name"
                                        placeholder="Type to search items…"
                                        class="w-full"
                                        forceSelection
                                    >
                                        <template #option="{ option }">
                                            <div>
                                                <span class="font-medium">{{ option.name }}</span>
                                                <span class="text-xs text-gray-500 ml-2">{{ option.code }} | {{ option.unit }}</span>
                                            </div>
                                        </template>
                                    </AutoComplete>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Qty</label>
                                    <InputNumber v-model="addItemQty" :min="0.01" placeholder="1" style="width:90px" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Unit Price</label>
                                    <InputNumber v-model="addItemPrice" :min="0" placeholder="0.00" style="width:120px" />
                                </div>
                                <Button icon="pi pi-plus" label="Add" @click="addItem" :disabled="!selectedItem" />
                            </div>

                            <small class="p-error block mb-2" v-if="form.errors.items">{{ form.errors.items }}</small>

                            <!-- Items Table -->
                            <DataTable :value="form.items" stripedRows>
                                <template #empty>
                                    <div class="text-center py-6 text-gray-400">
                                        <i class="pi pi-box text-4xl mb-2"></i>
                                        <p>No items added yet</p>
                                    </div>
                                </template>
                                <Column field="item_name" header="Item" />
                                <Column field="unit" header="Unit" style="width:80px" />
                                <Column header="Qty" style="width:120px">
                                    <template #body="{ data, index }">
                                        <InputNumber v-model="form.items[index].quantity" :min="0.01" inputStyle="width:80px" />
                                    </template>
                                </Column>
                                <Column header="Unit Price" style="width:140px">
                                    <template #body="{ data, index }">
                                        <InputNumber v-model="form.items[index].unit_price" :min="0" inputStyle="width:100px" />
                                    </template>
                                </Column>
                                <Column header="Total">
                                    <template #body="{ data }">
                                        <span class="font-medium">{{ formatCurrency(data.quantity * data.unit_price) }}</span>
                                    </template>
                                </Column>
                                <Column style="width:60px">
                                    <template #body="{ index }">
                                        <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeItem(index)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <!-- Terms & Notes -->
                    <Card>
                        <template #title>Terms & Notes</template>
                        <template #content>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Terms & Conditions</label>
                                    <Textarea v-model="form.terms_conditions" rows="3" placeholder="Standard T&C…" class="w-full" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Notes</label>
                                    <Textarea v-model="form.notes" rows="2" placeholder="Internal notes…" class="w-full" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right: Totals -->
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
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>VAT Amount</span>
                                    <span>{{ formatCurrency(vatAmount) }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Freight Charges</label>
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

                    <Card v-if="fromPR">
                        <template #title>Linked PR</template>
                        <template #content>
                            <div class="text-sm space-y-2">
                                <div><span class="text-gray-500">PR Number:</span> <span class="font-mono font-medium">{{ fromPR.pr_number }}</span></div>
                                <div><span class="text-gray-500">Purpose:</span> {{ fromPR.purpose }}</div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #content>
                            <div class="space-y-2">
                                <Button label="Create PO" icon="pi pi-check" class="w-full" @click="submit" :loading="form.processing" />
                                <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined class="w-full" @click="router.visit(route('tenant.purchase-orders.index'))" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <Toast />
    </TenantLayout>
</template>