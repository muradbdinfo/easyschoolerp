<script setup>
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Breadcrumb from 'primevue/breadcrumb';
import Divider from 'primevue/divider';
import Galleria from 'primevue/galleria';
import Toast from 'primevue/toast';
import { ref } from 'vue';

const props = defineProps({ grn: Object });

const showGallery = ref(false);
const activePhotoIndex = ref(0);

const photos = (props.grn.photos || []).map(p => ({
    itemImageSrc: `/storage/${p}`,
    thumbnailImageSrc: `/storage/${p}`,
}));

const formatCurrency = (v) =>
    new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(v || 0);
const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';
const formatDT   = (d) => d ? new Date(d).toLocaleString() : '—';

const totalAccepted = props.grn.items?.reduce((s, i) => s + parseFloat(i.quantity_accepted), 0) ?? 0;
const totalRejected = props.grn.items?.reduce((s, i) => s + parseFloat(i.quantity_rejected), 0) ?? 0;
const totalValue    = props.grn.items?.reduce((s, i) => s + (parseFloat(i.quantity_accepted) * parseFloat(i.unit_price)), 0) ?? 0;

const printGRN = () => window.print();
</script>

<template>
    <TenantLayout :title="`GRN: ${grn.grn_number}`">
        <div class="p-6">
            <Breadcrumb
                :home="{ label: 'Dashboard', url: route('dashboard') }"
                :model="[
                    { label: 'Procurement' },
                    { label: 'GRN', url: route('tenant.grn.index') },
                    { label: grn.grn_number }
                ]"
                class="mb-4"
            />

            <!-- Hero -->
            <Card class="mb-6 bg-gradient-to-r from-green-50 to-teal-50 border-0 shadow-sm">
                <template #content>
                    <div class="flex flex-wrap justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="pi pi-truck text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ grn.grn_number }}</h1>
                                <p class="text-gray-600">{{ grn.vendor?.name }} — {{ grn.branch?.name }}</p>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <Tag :value="grn.status_badge.label" :severity="grn.status_badge.severity" />
                                    <Tag :value="`PO: ${grn.purchase_order?.po_number}`" severity="info" />
                                    <Tag :value="formatDate(grn.receipt_date)" severity="secondary" />
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 items-start">
                            <Button icon="pi pi-print" label="Print" severity="secondary" outlined @click="printGRN" />
                            <Button
                                icon="pi pi-arrow-right"
                                label="View PO"
                                severity="info" outlined
                                @click="router.visit(route('tenant.purchase-orders.show', grn.purchase_order_id))"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <Card>
                    <template #content>
                        <p class="text-sm text-gray-500">Items</p>
                        <p class="text-2xl font-bold text-gray-900">{{ grn.items?.length }}</p>
                    </template>
                </Card>
                <Card>
                    <template #content>
                        <p class="text-sm text-gray-500">Total Accepted</p>
                        <p class="text-2xl font-bold text-green-600">{{ totalAccepted }}</p>
                    </template>
                </Card>
                <Card>
                    <template #content>
                        <p class="text-sm text-gray-500">Total Rejected</p>
                        <p class="text-2xl font-bold text-red-600">{{ totalRejected }}</p>
                    </template>
                </Card>
                <Card>
                    <template #content>
                        <p class="text-sm text-gray-500">Received Value</p>
                        <p class="text-xl font-bold text-blue-700">{{ formatCurrency(totalValue) }}</p>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Receipt Info -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2"><i class="pi pi-info-circle text-blue-600"></i> Receipt Details</div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Receipt Date</p>
                                    <p class="font-medium">{{ formatDate(grn.receipt_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Received By</p>
                                    <p class="font-medium">{{ grn.receiver?.name }}</p>
                                </div>
                                <div v-if="grn.supplier_invoice_no">
                                    <p class="text-gray-500">Supplier Invoice</p>
                                    <p class="font-mono font-medium">{{ grn.supplier_invoice_no }}</p>
                                </div>
                                <div v-if="grn.supplier_delivery_note">
                                    <p class="text-gray-500">Delivery Note</p>
                                    <p class="font-mono font-medium">{{ grn.supplier_delivery_note }}</p>
                                </div>
                                <div v-if="grn.vehicle_number">
                                    <p class="text-gray-500">Vehicle</p>
                                    <p class="font-medium">{{ grn.vehicle_number }}</p>
                                </div>
                                <div v-if="grn.quality_checker">
                                    <p class="text-gray-500">Quality Checker</p>
                                    <p class="font-medium">{{ grn.quality_checker?.name }}</p>
                                </div>
                            </div>
                            <div v-if="grn.quality_remarks" class="mt-4 p-3 bg-yellow-50 rounded text-sm">
                                <p class="text-gray-500 font-medium mb-1">Quality Remarks</p>
                                <p>{{ grn.quality_remarks }}</p>
                            </div>
                        </template>
                    </Card>

                    <!-- Items -->
                    <Card>
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-list text-blue-600"></i>
                                Received Items
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="grn.items" stripedRows class="text-sm">
                                <Column field="item_name" header="Item">
                                    <template #body="{ data }">
                                        <div>
                                            <div class="font-medium">{{ data.item_name }}</div>
                                            <Tag v-if="data.item?.is_asset" value="Asset" severity="warning" class="text-xs mt-1" />
                                        </div>
                                    </template>
                                </Column>
                                <Column field="unit" header="Unit" style="width:70px" />
                                <Column field="quantity_ordered" header="Ordered" style="width:90px" />
                                <Column field="quantity_received" header="Received" style="width:90px" />
                                <Column field="quantity_accepted" header="Accepted" style="width:90px">
                                    <template #body="{ data }">
                                        <span class="text-green-600 font-semibold">{{ data.quantity_accepted }}</span>
                                    </template>
                                </Column>
                                <Column field="quantity_rejected" header="Rejected" style="width:90px">
                                    <template #body="{ data }">
                                        <span :class="parseFloat(data.quantity_rejected) > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'">
                                            {{ data.quantity_rejected }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="unit_price" header="Unit Price">
                                    <template #body="{ data }">{{ formatCurrency(data.unit_price) }}</template>
                                </Column>
                                <Column header="Value">
                                    <template #body="{ data }">
                                        <span class="font-semibold">{{ formatCurrency(parseFloat(data.quantity_accepted) * parseFloat(data.unit_price)) }}</span>
                                    </template>
                                </Column>
                            </DataTable>

                            <!-- Rejection details -->
                            <div v-if="grn.items?.some(i => parseFloat(i.quantity_rejected) > 0)" class="mt-4">
                                <p class="text-sm font-medium text-red-700 mb-2">Rejection Details</p>
                                <div v-for="item in grn.items.filter(i => parseFloat(i.quantity_rejected) > 0)"
                                    :key="item.id"
                                    class="text-sm p-2 bg-red-50 rounded mb-2">
                                    <span class="font-medium">{{ item.item_name }}</span>:
                                    {{ item.quantity_rejected }} rejected — {{ item.rejection_reason || 'No reason given' }}
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Photos -->
                    <Card v-if="photos.length > 0">
                        <template #title>
                            <div class="flex items-center gap-2"><i class="pi pi-camera text-blue-600"></i> Photos</div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                                <div v-for="(photo, index) in photos" :key="index"
                                    class="aspect-square rounded overflow-hidden cursor-pointer border hover:border-blue-400 transition"
                                    @click="activePhotoIndex = index; showGallery = true">
                                    <img :src="photo.itemImageSrc" class="w-full h-full object-cover" />
                                </div>
                            </div>
                            <Galleria
                                v-model:visible="showGallery"
                                v-model:activeIndex="activePhotoIndex"
                                :value="photos"
                                :numVisible="5"
                                containerStyle="max-width: 850px"
                                :circular="true"
                                :fullScreen="true"
                                :showItemNavigators="true"
                            >
                                <template #item="{ item }">
                                    <img :src="item.itemImageSrc" class="max-h-screen object-contain" />
                                </template>
                                <template #thumbnail="{ item }">
                                    <img :src="item.thumbnailImageSrc" class="h-16 object-cover" />
                                </template>
                            </Galleria>
                        </template>
                    </Card>

                    <Card v-if="grn.notes">
                        <template #title>Notes</template>
                        <template #content>
                            <p class="text-gray-700 whitespace-pre-line">{{ grn.notes }}</p>
                        </template>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <template #title>Vendor</template>
                        <template #content>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-gray-500">Name:</span> <span class="font-medium">{{ grn.vendor?.name }}</span></div>
                                <div><span class="text-gray-500">Code:</span> <span class="font-mono">{{ grn.vendor?.code }}</span></div>
                                <div v-if="grn.vendor?.phone"><span class="text-gray-500">Phone:</span> {{ grn.vendor?.phone }}</div>
                                <div v-if="grn.vendor?.email">
                                    <span class="text-gray-500">Email:</span>
                                    <a :href="`mailto:${grn.vendor.email}`" class="text-blue-600 ml-1">{{ grn.vendor.email }}</a>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Activity</template>
                        <template #content>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created by</span>
                                    <span class="font-medium">{{ grn.creator?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created on</span>
                                    <span>{{ formatDT(grn.created_at) }}</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- Asset creation notice -->
                    <Card v-if="grn.items?.some(i => i.assets_created)">
                        <template #title>
                            <span class="text-green-700">Assets Created</span>
                        </template>
                        <template #content>
                            <div class="text-sm space-y-1">
                                <div v-for="item in grn.items.filter(i => i.assets_created)" :key="item.id"
                                    class="p-2 bg-green-50 rounded">
                                    {{ item.item_name }} ({{ item.quantity_accepted }} unit(s))
                                </div>
                            </div>
                            <Button label="View Assets" icon="pi pi-box" severity="success" outlined size="small"
                                class="w-full mt-3"
                                @click="router.visit(route('assets.register.index'))" />
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <Toast />
    </TenantLayout>
</template>

<style>
@media print {
    .p-toast, button, nav, .p-breadcrumb { display: none !important; }
}
</style>