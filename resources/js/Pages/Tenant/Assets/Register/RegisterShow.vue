<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Timeline from 'primevue/timeline';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Galleria from 'primevue/galleria';
import ConfirmDialog from 'primevue/confirmdialog';
import {
    Box, Edit, Trash2, MoveRight, Wrench, TrendingDown,
    Building, User, Calendar, Tag as TagIcon, Shield, Info,
    CheckCircle, AlertCircle, Clock
} from 'lucide-vue-next';

const props  = defineProps({ asset: Object });
const toast  = useToast();
const confirm= useConfirm();

const formatCurrency = (v) => '৳ ' + Number(v ?? 0).toLocaleString('en-BD');
const formatDate     = (d) => d ? new Date(d).toLocaleDateString('en-BD') : '—';

// Gallery
const galleriaImages = (props.asset.photos ?? []).map(p => ({
    itemImageSrc: `/storage/${p}`,
    thumbnailImageSrc: `/storage/${p}`,
}));
const galleriaVisible = ref(false);
const galleriaActive  = ref(0);

function deleteAsset() {
    confirm.require({
        message:  `Delete asset ${props.asset.asset_tag}?`,
        header:   'Confirm Delete',
        icon:     'pi pi-exclamation-triangle',
        acceptProps:  { label: 'Delete', severity: 'danger' },
        rejectProps:  { label: 'Cancel', severity: 'secondary' },
        accept: () => {
            useForm({}).delete(route('tenant.assets.destroy', props.asset.id), {
                onSuccess: () => router.visit(route('tenant.assets.index')),
            });
        },
    });
}

// Depreciation percentage
const deprecPercent = computed(() => {
    if (!props.asset.acquisition_cost) return 0;
    return Math.round((props.asset.accumulated_depreciation / props.asset.acquisition_cost) * 100);
});

import { computed } from 'vue';

// Build timeline events
const timelineEvents = computed(() => {
    const events = [];
    events.push({ title: 'Registered', date: props.asset.created_at, icon: Box, severity: 'success', description: `By ${props.asset.created_by?.name ?? 'System'}` });
    for (const t of (props.asset.transfers ?? [])) {
        events.push({ title: 'Transfer', date: t.transfer_date, icon: MoveRight, severity: 'info',
            description: `${t.from_branch?.name ?? '?'} → ${t.to_branch?.name ?? '?'}` });
    }
    for (const m of (props.asset.maintenances ?? [])) {
        events.push({ title: m.type_label, date: m.scheduled_date, icon: Wrench, severity: 'warning',
            description: m.status });
    }
    return events.sort((a, b) => new Date(b.date) - new Date(a.date));
});
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Register', url: route('tenant.assets.index') },
        { label: asset.asset_tag },
    ]">
        <ConfirmDialog />

        <div class="space-y-4">
            <!-- Hero Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex flex-col md:flex-row gap-4 items-start">
                    <!-- Photo -->
                    <div class="w-24 h-24 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        <img v-if="asset.primary_photo_url" :src="asset.primary_photo_url" class="w-full h-full object-cover" />
                        <Box v-else :size="32" class="text-gray-300" />
                    </div>
                    <!-- Info -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="font-mono text-sm font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ asset.asset_tag }}</span>
                            <Tag :severity="asset.status_badge.severity" :value="asset.status_badge.label" />
                            <Tag :severity="asset.condition_badge.severity" :value="asset.condition_badge.label" />
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ asset.name }}</h1>
                        <p class="text-gray-500 text-sm mt-0.5">{{ asset.category?.name }} &bull; {{ asset.brand }} {{ asset.model_number }}</p>
                        <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-600">
                            <span class="flex items-center gap-1.5">
                                <Building :size="14" class="text-gray-400" />
                                {{ asset.branch?.name ?? '—' }}<span v-if="asset.room">, {{ asset.room }}</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <User :size="14" class="text-gray-400" />
                                {{ asset.custodian?.name ?? 'Unassigned' }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Calendar :size="14" class="text-gray-400" />
                                Acquired: {{ formatDate(asset.acquisition_date) }}
                            </span>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="flex gap-2 flex-shrink-0">
                        <Link :href="route('tenant.assets.edit', asset.id)">
                            <Button severity="secondary" size="small" outlined>
                                <Edit :size="14" class="mr-1" /> Edit
                            </Button>
                        </Link>
                        <Link :href="route('tenant.assets.transfers.create', { asset_id: asset.id })">
                            <Button severity="info" size="small" outlined>
                                <MoveRight :size="14" class="mr-1" /> Transfer
                            </Button>
                        </Link>
                        <Link :href="route('tenant.assets.maintenance.create', { asset_id: asset.id })">
                            <Button severity="warning" size="small" outlined>
                                <Wrench :size="14" class="mr-1" /> Maintenance
                            </Button>
                        </Link>
                        <Button severity="danger" size="small" outlined @click="deleteAsset">
                            <Trash2 :size="14" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Acquisition Cost</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ formatCurrency(asset.acquisition_cost) }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Accumulated Depreciation</p>
                    <p class="text-xl font-bold text-red-500 mt-1">{{ formatCurrency(asset.accumulated_depreciation) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ deprecPercent }}% of cost</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-500 font-medium">Net Book Value</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ formatCurrency(asset.net_book_value) }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <TabView>
                    <!-- Details Tab -->
                    <TabPanel header="Details">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-2">
                            <!-- Basic -->
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <TagIcon :size="16" class="text-blue-500" /> Asset Information
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex"><dt class="text-gray-500 w-36">Serial No.</dt><dd class="font-medium">{{ asset.serial_number ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Brand</dt><dd class="font-medium">{{ asset.brand ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Model</dt><dd class="font-medium">{{ asset.model_number ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Color</dt><dd class="font-medium">{{ asset.color ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Vendor</dt><dd class="font-medium">{{ asset.vendor?.name ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Invoice No.</dt><dd class="font-medium">{{ asset.invoice_number ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">PO No.</dt><dd class="font-medium">{{ asset.po_number ?? '—' }}</dd></div>
                                </dl>
                            </div>
                            <!-- Depreciation -->
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <TrendingDown :size="16" class="text-orange-500" /> Depreciation
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex"><dt class="text-gray-500 w-36">Method</dt><dd class="font-medium">{{ asset.depreciation_method?.toUpperCase() }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Rate</dt><dd class="font-medium">{{ asset.depreciation_rate }}% p.a.</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Useful Life</dt><dd class="font-medium">{{ asset.useful_life_years }} years</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Residual Value</dt><dd class="font-medium">{{ asset.residual_value_percent }}%</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Start Date</dt><dd class="font-medium">{{ formatDate(asset.depreciation_start_date) }}</dd></div>
                                </dl>
                            </div>
                            <!-- Warranty -->
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <Shield :size="16" class="text-teal-500" /> Warranty & Insurance
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex items-center"><dt class="text-gray-500 w-36">Warranty</dt>
                                        <dd class="flex items-center gap-2">
                                            <Tag :severity="asset.warranty_status.severity" :value="asset.warranty_status.label" />
                                        </dd>
                                    </div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Provider</dt><dd class="font-medium">{{ asset.warranty_provider ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Insurance</dt><dd class="font-medium">{{ asset.insurance_company ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Policy No.</dt><dd class="font-medium">{{ asset.insurance_policy_number ?? '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Insured Value</dt><dd class="font-medium">{{ asset.insured_value ? formatCurrency(asset.insured_value) : '—' }}</dd></div>
                                    <div class="flex"><dt class="text-gray-500 w-36">Ins. Expiry</dt><dd class="font-medium">{{ formatDate(asset.insurance_expiry_date) }}</dd></div>
                                </dl>
                            </div>
                            <!-- Specifications -->
                            <div v-if="asset.specifications">
                                <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <Info :size="16" class="text-indigo-500" /> Specifications
                                </h4>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ asset.specifications }}</p>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- Transfers -->
                    <TabPanel :header="`Transfers (${asset.transfers?.length ?? 0})`">
                        <div class="p-2">
                            <div class="flex justify-end mb-3">
                                <Link :href="route('tenant.assets.transfers.create', { asset_id: asset.id })">
                                    <Button size="small"><MoveRight :size="14" class="mr-1" /> Transfer Asset</Button>
                                </Link>
                            </div>
                            <DataTable :value="asset.transfers ?? []" class="p-datatable-sm" :rows="10">
                                <Column field="transfer_number" header="Transfer #" style="width:130px">
                                    <template #body="{ data }">
                                        <span class="font-mono text-xs">{{ data.transfer_number }}</span>
                                    </template>
                                </Column>
                                <Column header="From → To" style="min-width:180px">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ data.from_branch?.name ?? '?' }} → {{ data.to_branch?.name ?? '?' }}</span>
                                    </template>
                                </Column>
                                <Column header="Custodian Change" style="min-width:160px">
                                    <template #body="{ data }">
                                        <span class="text-sm text-gray-600">{{ data.from_custodian?.name ?? '?' }} → {{ data.to_custodian?.name ?? '?' }}</span>
                                    </template>
                                </Column>
                                <Column field="transfer_date" header="Date" style="width:110px">
                                    <template #body="{ data }"><span class="text-sm">{{ formatDate(data.transfer_date) }}</span></template>
                                </Column>
                                <Column header="Status" style="width:100px">
                                    <template #body="{ data }"><Tag :severity="data.status_badge.severity" :value="data.status_badge.label" /></template>
                                </Column>
                                <template #empty><div class="text-center py-6 text-gray-500 text-sm">No transfers yet.</div></template>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- Maintenance -->
                    <TabPanel :header="`Maintenance (${asset.maintenances?.length ?? 0})`">
                        <div class="p-2">
                            <div class="flex justify-end mb-3">
                                <Link :href="route('tenant.assets.maintenance.create', { asset_id: asset.id })">
                                    <Button size="small"><Wrench :size="14" class="mr-1" /> Schedule Maintenance</Button>
                                </Link>
                            </div>
                            <DataTable :value="asset.maintenances ?? []" class="p-datatable-sm">
                                <Column field="maintenance_number" header="#" style="width:120px">
                                    <template #body="{ data }"><span class="font-mono text-xs">{{ data.maintenance_number }}</span></template>
                                </Column>
                                <Column header="Type" style="width:140px">
                                    <template #body="{ data }"><span class="text-sm">{{ data.type_label }}</span></template>
                                </Column>
                                <Column field="scheduled_date" header="Scheduled" style="width:110px">
                                    <template #body="{ data }"><span class="text-sm">{{ formatDate(data.scheduled_date) }}</span></template>
                                </Column>
                                <Column header="Vendor" style="width:140px">
                                    <template #body="{ data }"><span class="text-sm">{{ data.vendor?.name ?? '—' }}</span></template>
                                </Column>
                                <Column header="Cost" style="width:110px">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ data.actual_cost ? formatCurrency(data.actual_cost) : (data.estimated_cost ? formatCurrency(data.estimated_cost) + ' est.' : '—') }}</span>
                                    </template>
                                </Column>
                                <Column header="Status" style="width:110px">
                                    <template #body="{ data }"><Tag :severity="data.status_badge.severity" :value="data.status_badge.label" /></template>
                                </Column>
                                <template #empty><div class="text-center py-6 text-gray-500 text-sm">No maintenance records.</div></template>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- Depreciation Schedule -->
                    <TabPanel :header="`Depreciation (${asset.depreciation_schedules?.length ?? 0})`">
                        <div class="p-2">
                            <DataTable :value="asset.depreciation_schedules ?? []" class="p-datatable-sm" :rows="12">
                                <Column header="Period" style="width:100px">
                                    <template #body="{ data }"><span class="text-sm font-medium">{{ data.month }}/{{ data.year }}</span></template>
                                </Column>
                                <Column header="Opening Value" style="width:140px">
                                    <template #body="{ data }"><span class="text-sm">{{ formatCurrency(data.opening_value) }}</span></template>
                                </Column>
                                <Column header="Depreciation" style="width:140px">
                                    <template #body="{ data }"><span class="text-sm text-red-500">{{ formatCurrency(data.depreciation_amount) }}</span></template>
                                </Column>
                                <Column header="Closing Value" style="width:140px">
                                    <template #body="{ data }"><span class="text-sm font-semibold">{{ formatCurrency(data.closing_value) }}</span></template>
                                </Column>
                                <Column field="processed_date" header="Processed" style="width:110px">
                                    <template #body="{ data }"><span class="text-sm text-gray-500">{{ formatDate(data.processed_date) }}</span></template>
                                </Column>
                                <template #empty>
                                    <div class="text-center py-6 text-gray-500 text-sm">
                                        No depreciation entries yet.
                                        <Link :href="route('tenant.assets.depreciation.run')" class="text-blue-600 hover:underline ml-1">Run Depreciation</Link>
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- Photos -->
                    <TabPanel :header="`Photos (${(asset.photos ?? []).length + (asset.primary_photo ? 1 : 0)})`">
                        <div class="p-2">
                            <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
                                <div v-if="asset.primary_photo_url"
                                    class="aspect-square rounded-lg overflow-hidden border-2 border-blue-500 relative cursor-pointer">
                                    <img :src="asset.primary_photo_url" class="w-full h-full object-cover" />
                                    <span class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-xs text-center py-0.5">Primary</span>
                                </div>
                                <div v-for="(photo, i) in (asset.photos ?? [])" :key="i"
                                    class="aspect-square rounded-lg overflow-hidden border border-gray-200 cursor-pointer hover:border-blue-400 transition">
                                    <img :src="`/storage/${photo}`" class="w-full h-full object-cover" />
                                </div>
                                <div v-if="!asset.primary_photo_url && !asset.photos?.length"
                                    class="col-span-5 text-center py-8 text-gray-500 text-sm">No photos uploaded.</div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- History Timeline -->
                    <TabPanel header="History">
                        <div class="p-4 max-w-xl">
                            <Timeline :value="timelineEvents" align="alternate">
                                <template #content="{ item }">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">{{ item.title }}</p>
                                        <p class="text-gray-500">{{ item.description }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(item.date) }}</p>
                                    </div>
                                </template>
                                <template #marker="{ item }">
                                    <div class="w-8 h-8 rounded-full bg-white border-2 flex items-center justify-center"
                                        :class="{
                                            'border-green-500': item.severity === 'success',
                                            'border-blue-500':  item.severity === 'info',
                                            'border-orange-500':item.severity === 'warning',
                                        }">
                                        <component :is="item.icon" :size="14" :class="{
                                            'text-green-500': item.severity === 'success',
                                            'text-blue-500':  item.severity === 'info',
                                            'text-orange-500':item.severity === 'warning',
                                        }" />
                                    </div>
                                </template>
                            </Timeline>
                        </div>
                    </TabPanel>
                </TabView>
            </div>
        </div>
    </TenantLayout>
</template>