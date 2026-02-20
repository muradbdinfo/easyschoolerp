<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Button       from 'primevue/button';
import Select       from 'primevue/select';
import InputText    from 'primevue/inputtext';
import InputNumber  from 'primevue/inputnumber';
import Textarea     from 'primevue/textarea';
import DatePicker   from 'primevue/datepicker';
import Toast        from 'primevue/toast';
import Tag          from 'primevue/tag';
import axios        from 'axios';
import {
    ArrowLeft, Package, Plus, Trash2,
    FileText, Building2, Users, CalendarDays,
    DollarSign, AlertCircle, CheckCircle2,
} from 'lucide-vue-next';

// ── Props from controller ─────────────────────────────────────────────────────
const props = defineProps({
    vendors:     { type: Array,  default: () => [] },
    branches:    { type: Array,  default: () => [] },
    departments: { type: Array,  default: () => [] },
    items:       { type: Array,  default: () => [] },
    fromPR:      { type: Object, default: null },    // pre-filled PR from ?from_pr=
});

const toast = useToast();

// ── Approved PRs dropdown (loaded via AJAX) ───────────────────────────────────
const approvedPRs   = ref([]);
const loadingPRs    = ref(false);
const selectedPRId  = ref(null);

const loadApprovedPRs = async () => {
    loadingPRs.value = true;
    try {
        const { data } = await axios.get(route('tenant.purchase-orders.approved-prs'));
        approvedPRs.value = data;
    } catch {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Could not load approved PRs', life: 3000 });
    } finally {
        loadingPRs.value = false;
    }
};

// ── Form state ────────────────────────────────────────────────────────────────
const form = ref({
    purchase_requisition_id: null,
    vendor_id:               null,
    branch_id:               null,
    department_id:           null,
    expected_delivery_date:  null,
    delivery_address:        '',
    vat_percentage:          0,
    freight_charges:         0,
    discount_amount:         0,
    payment_terms:           'Net 30',
    payment_terms_days:      30,
    terms_conditions:        '',
    notes:                   '',
    items:                   [],
});

const errors    = ref({});
const submitting = ref(false);

// ── Pre-fill form from PR data (works for both fromPR prop and AJAX response) ─
const prefillFromPR = (pr) => {
    if (!pr) return;

    form.value.purchase_requisition_id = pr.id;
    form.value.branch_id               = pr.branch?.id     ?? null;
    form.value.department_id           = pr.department?.id ?? null;

    // Map PR items → PO items
    // itemsJson returns: { id, item_id, item_name, unit, quantity, estimated_unit_price, specifications }
    // fromPR prop returns: items array with same shape
    form.value.items = (pr.items ?? []).map(i => ({
        item_id:        i.item_id,
        pr_item_id:     i.id,                         // links PO item back to PR item
        item_name:      i.item_name,
        unit:           i.unit,
        quantity:       Number(i.quantity),
        unit_price:     Number(i.estimated_unit_price),
        specifications: i.specifications ?? '',
    }));
};

onMounted(() => {
    loadApprovedPRs();
    if (props.fromPR) {
        selectedPRId.value = props.fromPR.id;  // triggers watch → prefill
        prefillFromPR(props.fromPR);           // immediate fill (no AJAX needed)
    }
});

// ── Watch PR selection from dropdown ─────────────────────────────────────────
const loadingPRItems = ref(false);

watch(selectedPRId, async (newId) => {
    if (!newId) {
        form.value.purchase_requisition_id = null;
        form.value.branch_id     = null;
        form.value.department_id = null;
        form.value.items         = [];
        return;
    }

    // Fetch full PR with items from dedicated JSON endpoint
    loadingPRItems.value = true;
    try {
        const { data } = await axios.get(
            route('tenant.requisitions.items-json', newId)
        );
        prefillFromPR(data);
    } catch (err) {
        toast.add({
            severity: 'error',
            summary:  'Failed',
            detail:   'Could not load PR items. Please try again.',
            life:     4000,
        });
        selectedPRId.value = null;
    } finally {
        loadingPRItems.value = false;
    }
});

// ── Item management ───────────────────────────────────────────────────────────
const addItem = () => {
    form.value.items.push({
        item_id:        null,
        pr_item_id:     null,
        item_name:      '',
        unit:           '',
        quantity:       1,
        unit_price:     0,
        specifications: '',
    });
};

const removeItem = (idx) => {
    form.value.items.splice(idx, 1);
};

const onItemSelect = (idx, itemId) => {
    const item = props.items.find(i => i.id === itemId);
    if (!item) return;
    form.value.items[idx].item_name  = item.name;
    form.value.items[idx].unit       = item.unit;
    form.value.items[idx].unit_price = Number(item.current_price ?? 0);
};

// ── Totals ────────────────────────────────────────────────────────────────────
const subtotal = computed(() =>
    form.value.items.reduce((s, i) => s + (Number(i.quantity) * Number(i.unit_price)), 0)
);
const vatAmount = computed(() =>
    Math.round(subtotal.value * (Number(form.value.vat_percentage ?? 0) / 100) * 100) / 100
);
const grandTotal = computed(() =>
    subtotal.value + vatAmount.value + Number(form.value.freight_charges ?? 0) - Number(form.value.discount_amount ?? 0)
);

const formatAmount = (v) =>
    '৳ ' + Number(v ?? 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });

// ── Submit ────────────────────────────────────────────────────────────────────
const submit = () => {
    if (form.value.items.length === 0) {
        toast.add({ severity: 'warn', summary: 'No Items', detail: 'Add at least one item', life: 3000 });
        return;
    }

    // Check all items have item_id selected
    const hasBlankItem = form.value.items.some(i => !i.item_id);
    if (hasBlankItem) {
        toast.add({ severity: 'warn', summary: 'Incomplete Items', detail: 'Please select an item for every row', life: 4000 });
        return;
    }

    submitting.value = true;
    errors.value     = {};

    // Sanitize payload — ensure correct types for Laravel validation
    const payload = {
        purchase_requisition_id: form.value.purchase_requisition_id || null,
        vendor_id:               form.value.vendor_id,
        branch_id:               form.value.branch_id,
        department_id:           form.value.department_id || null,
        delivery_address:        form.value.delivery_address || null,
        vat_percentage:          Number(form.value.vat_percentage  ?? 0),
        freight_charges:         Number(form.value.freight_charges ?? 0),
        discount_amount:         Number(form.value.discount_amount ?? 0),
        payment_terms:           form.value.payment_terms || null,
        payment_terms_days:      form.value.payment_terms_days ? parseInt(form.value.payment_terms_days) : null,
        terms_conditions:        form.value.terms_conditions || null,
        notes:                   form.value.notes || null,
        // Date: only send if set, use date-only string (not ISO with time)
        expected_delivery_date:  form.value.expected_delivery_date
            ? (() => {
                const d = new Date(form.value.expected_delivery_date);
                return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
              })()
            : null,
        // Items: send all fields including item_name/unit for cases where item_id is null
        items: form.value.items.map(i => ({
            item_id:        i.item_id        ?? null,
            pr_item_id:     i.pr_item_id     ?? null,
            item_name:      i.item_name      ?? '',
            unit:           i.unit           ?? '',
            quantity:       Number(i.quantity  ?? 1),
            unit_price:     Number(i.unit_price ?? 0),
            specifications: i.specifications  ?? null,
        })),
    };

    router.post(route('tenant.purchase-orders.store'), payload, {
        onSuccess: () => {
            // Redirect to PO_Show handles the success flash toast
        },
        onError: (e) => {
            errors.value = e;
            const firstError = Object.values(e)[0] ?? 'Please check the form';
            toast.add({ severity: 'error', summary: 'Validation Error', detail: firstError, life: 6000 });
            console.error('PO validation errors:', e);
        },
        onFinish: () => { submitting.value = false; },
    });
};

// ── PR option label ───────────────────────────────────────────────────────────
const prOptionLabel = (pr) =>
    `${pr.pr_number} — ${pr.department?.name ?? ''} — ৳${Number(pr.total_amount).toLocaleString()}`;
</script>

<template>
    <TenantLayout title="Create Purchase Order">
        <Toast position="top-right" />

        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- ── Header ─────────────────────────────────────────────────── -->
            <div class="flex items-center gap-3">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text rounded
                    @click="router.visit(route('tenant.purchase-orders.index'))"
                />
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">New Purchase Order</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Create from an approved requisition or manually</p>
                </div>
            </div>

            <!-- ── Step 1: Link to Approved PR ────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                    <h2 class="text-base font-semibold text-gray-800">Link to Approved Requisition</h2>
                    <span class="text-xs text-gray-400 ml-1">(optional — selecting one auto-fills items)</span>
                </div>

                <Select
                    v-model="selectedPRId"
                    :options="approvedPRs"
                    :optionLabel="prOptionLabel"
                    optionValue="id"
                    :loading="loadingPRs"
                    placeholder="Select an approved PR to auto-fill..."
                    class="w-full"
                    showClear
                    filter
                    filterPlaceholder="Search PRs..."
                >
                    <template #option="{ option }">
                        <div class="flex items-center justify-between gap-4 py-1">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ option.pr_number }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ option.department?.name }} · {{ option.branch?.name }}
                                </p>
                                <p class="text-xs text-gray-500 truncate max-w-xs">{{ option.purpose }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-bold text-blue-700 text-sm">৳ {{ Number(option.total_amount).toLocaleString() }}</p>
                                <Tag value="Approved" severity="success" class="text-xs" />
                            </div>
                        </div>
                    </template>

                    <template #empty>
                        <div class="text-center py-4 text-gray-400 text-sm">
                            <CheckCircle2 :size="20" class="mx-auto mb-1 opacity-40" />
                            No approved PRs available
                        </div>
                    </template>
                </Select>

                <!-- Loading PR items -->
                <div v-if="loadingPRItems"
                    class="mt-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-center gap-2 text-sm text-blue-700">
                    <i class="pi pi-spin pi-spinner" />
                    Loading items from PR...
                </div>

                <!-- Show selected PR info -->
                <div v-else-if="form.purchase_requisition_id"
                    class="mt-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-center gap-2 text-sm text-blue-700">
                    <FileText :size="16" />
                    PR linked — {{ form.items.length }} item(s) pre-filled. You can still edit below.
                </div>
            </div>

            <!-- ── Step 2: Basic Details ───────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                    <h2 class="text-base font-semibold text-gray-800">Order Details</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <!-- Vendor -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Vendor <span class="text-red-500">*</span>
                        </label>
                        <Select
                            v-model="form.vendor_id"
                            :options="vendors"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select vendor..."
                            class="w-full"
                            filter
                            filterPlaceholder="Search vendors..."
                            :class="{ 'p-invalid': errors.vendor_id }"
                        >
                            <template #option="{ option }">
                                <div>
                                    <p class="font-medium text-sm">{{ option.name }}</p>
                                    <p class="text-xs text-gray-400">{{ option.code }}</p>
                                </div>
                            </template>
                        </Select>
                        <p v-if="errors.vendor_id" class="mt-1 text-xs text-red-500">{{ errors.vendor_id }}</p>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Delivery Branch <span class="text-red-500">*</span>
                        </label>
                        <Select
                            v-model="form.branch_id"
                            :options="branches"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select branch..."
                            class="w-full"
                            :class="{ 'p-invalid': errors.branch_id }"
                        />
                        <p v-if="errors.branch_id" class="mt-1 text-xs text-red-500">{{ errors.branch_id }}</p>
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                        <Select
                            v-model="form.department_id"
                            :options="[{ name: '— None —', id: null }, ...departments]"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select department..."
                            class="w-full"
                        />
                    </div>

                    <!-- Expected Delivery -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Expected Delivery Date
                        </label>
                        <DatePicker
                            v-model="form.expected_delivery_date"
                            placeholder="dd/mm/yyyy"
                            dateFormat="dd/mm/yy"
                            class="w-full"
                            showIcon
                            :minDate="new Date()"
                        />
                    </div>

                    <!-- Payment Terms -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Terms</label>
                        <Select
                            v-model="form.payment_terms"
                            :options="['Net 7', 'Net 15', 'Net 30', 'Net 45', 'Net 60', 'Advance', 'On Delivery', 'Custom']"
                            placeholder="Select..."
                            class="w-full"
                        />
                    </div>

                    <!-- Delivery Address -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address</label>
                        <Textarea
                            v-model="form.delivery_address"
                            placeholder="Full delivery address..."
                            rows="2"
                            class="w-full text-sm"
                            autoResize
                        />
                    </div>

                </div>
            </div>

            <!-- ── Step 3: Items ───────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">3</div>
                        <h2 class="text-base font-semibold text-gray-800">Items</h2>
                        <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">
                            {{ form.items.length }} item{{ form.items.length !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    <Button
                        label="Add Item"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="addItem"
                    >
                        <template #icon><Plus :size="14" class="mr-1" /></template>
                    </Button>
                </div>

                <!-- Items table -->
                <div v-if="form.items.length > 0" class="space-y-3">
                    <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-gray-400 uppercase tracking-wide px-1 hidden sm:grid">
                        <div class="col-span-4">Item</div>
                        <div class="col-span-2">Unit</div>
                        <div class="col-span-2">Qty</div>
                        <div class="col-span-2">Unit Price</div>
                        <div class="col-span-1 text-right">Total</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div
                        v-for="(item, idx) in form.items"
                        :key="idx"
                        class="grid grid-cols-12 gap-2 items-center bg-gray-50 rounded-lg p-3"
                    >
                        <!-- Item select -->
                        <div class="col-span-12 sm:col-span-4">
                            <Select
                                v-model="item.item_id"
                                :options="items"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select item..."
                                class="w-full text-sm"
                                filter
                                filterPlaceholder="Search..."
                                @change="onItemSelect(idx, item.item_id)"
                            >
                                <template #option="{ option }">
                                    <div>
                                        <p class="text-sm font-medium">{{ option.name }}</p>
                                        <p class="text-xs text-gray-400">{{ option.code }} · {{ option.unit }}</p>
                                    </div>
                                </template>
                            </Select>
                        </div>

                        <!-- Unit -->
                        <div class="col-span-4 sm:col-span-2">
                            <InputText
                                v-model="item.unit"
                                placeholder="Unit"
                                class="w-full text-sm"
                                readonly
                            />
                        </div>

                        <!-- Qty -->
                        <div class="col-span-4 sm:col-span-2">
                            <InputNumber
                                v-model="item.quantity"
                                :min="0.01"
                                :minFractionDigits="0"
                                :maxFractionDigits="2"
                                class="w-full"
                                inputClass="text-sm w-full"
                            />
                        </div>

                        <!-- Unit Price -->
                        <div class="col-span-4 sm:col-span-2">
                            <InputNumber
                                v-model="item.unit_price"
                                :min="0"
                                :minFractionDigits="2"
                                :maxFractionDigits="2"
                                class="w-full"
                                inputClass="text-sm w-full"
                            />
                        </div>

                        <!-- Line total -->
                        <div class="col-span-10 sm:col-span-1 text-right">
                            <span class="text-sm font-semibold text-gray-700">
                                ৳ {{ (item.quantity * item.unit_price).toLocaleString('en-BD', { minimumFractionDigits: 0 }) }}
                            </span>
                        </div>

                        <!-- Remove -->
                        <div class="col-span-2 sm:col-span-1 text-right">
                            <button
                                @click="removeItem(idx)"
                                class="text-red-400 hover:text-red-600 transition p-1 rounded"
                            >
                                <Trash2 :size="15" />
                            </button>
                        </div>

                        <!-- Specifications (full width below) -->
                        <div class="col-span-12">
                            <InputText
                                v-model="item.specifications"
                                placeholder="Specifications / notes for this item (optional)"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty items state -->
                <div v-else class="text-center py-10 text-gray-300 border-2 border-dashed border-gray-200 rounded-xl">
                    <Package :size="36" class="mx-auto mb-2" />
                    <p class="text-sm text-gray-400">No items added yet</p>
                    <p class="text-xs text-gray-300 mt-0.5">Select a PR above to auto-fill, or click "Add Item"</p>
                </div>

                <p v-if="errors.items" class="mt-2 text-xs text-red-500">{{ errors.items }}</p>
            </div>

            <!-- ── Step 4: Pricing & Charges ──────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">4</div>
                    <h2 class="text-base font-semibold text-gray-800">Pricing & Charges</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">VAT %</label>
                        <InputNumber
                            v-model="form.vat_percentage"
                            :min="0" :max="100"
                            :minFractionDigits="0" :maxFractionDigits="2"
                            suffix=" %"
                            class="w-full"
                            inputClass="text-sm w-full"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Freight Charges (৳)</label>
                        <InputNumber
                            v-model="form.freight_charges"
                            :min="0"
                            :minFractionDigits="2"
                            class="w-full"
                            inputClass="text-sm w-full"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Discount (৳)</label>
                        <InputNumber
                            v-model="form.discount_amount"
                            :min="0"
                            :minFractionDigits="2"
                            class="w-full"
                            inputClass="text-sm w-full"
                        />
                    </div>
                </div>

                <!-- Summary box -->
                <div class="bg-gray-50 rounded-xl p-5 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium">{{ formatAmount(subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>VAT ({{ form.vat_percentage }}%)</span>
                        <span class="font-medium">{{ formatAmount(vatAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Freight</span>
                        <span class="font-medium">{{ formatAmount(form.freight_charges) }}</span>
                    </div>
                    <div v-if="form.discount_amount > 0" class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span class="font-medium">− {{ formatAmount(form.discount_amount) }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-base">
                        <span class="text-gray-800">Grand Total</span>
                        <span class="text-blue-700 text-lg">{{ formatAmount(grandTotal) }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Step 5: Terms & Notes ───────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">5</div>
                    <h2 class="text-base font-semibold text-gray-800">Terms & Notes</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Terms & Conditions</label>
                        <Textarea
                            v-model="form.terms_conditions"
                            rows="3"
                            class="w-full text-sm"
                            placeholder="Standard terms and conditions..."
                            autoResize
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Internal Notes</label>
                        <Textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full text-sm"
                            placeholder="Internal notes (not shown to vendor)..."
                            autoResize
                        />
                    </div>
                </div>
            </div>

            <!-- ── Error summary ────────────────────────────────────────── -->
            <div v-if="Object.keys(errors).length > 0"
                class="bg-red-50 border border-red-300 rounded-xl p-5">
                <p class="font-semibold text-red-700 mb-3 flex items-center gap-2">
                    <AlertCircle :size="17" />
                    Error — please check the details below:
                </p>
                <ul class="space-y-1.5 text-sm text-red-600 list-disc list-inside">
                    <li v-for="(msg, field) in errors" :key="field">
                        <strong>{{ field }}:</strong> {{ msg }}
                    </li>
                </ul>
            </div>

            <!-- ── Actions ─────────────────────────────────────────────────── -->
            <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-6 py-4">
                <Button
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="router.visit(route('tenant.purchase-orders.index'))"
                />
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-gray-400">Grand Total</p>
                        <p class="font-bold text-blue-700">{{ formatAmount(grandTotal) }}</p>
                    </div>
                    <Button
                        label="Create Purchase Order"
                        :loading="submitting"
                        :disabled="submitting || form.items.length === 0 || !form.vendor_id || !form.branch_id"
                        @click="submit"
                    >
                        <template #icon><Package :size="16" class="mr-2" /></template>
                    </Button>
                </div>
            </div>

        </div>
    </TenantLayout>
</template>