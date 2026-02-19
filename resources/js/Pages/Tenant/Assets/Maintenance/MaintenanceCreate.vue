<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import { Wrench } from 'lucide-vue-next';

const props = defineProps({
    preSelectedAsset: Object,
    assets:   Array,
    vendors:  Array,
    users:    Array,
});

const form = useForm({
    asset_id:       props.preSelectedAsset?.id ?? null,
    type:           'routine',
    frequency:      'one_time',
    scheduled_date: null,
    vendor_id:      null,
    estimated_cost: null,
    description:    '',
    assigned_to:    null,
    notes:          '',
});

const typeOptions = [
    { label: 'Routine Maintenance', value: 'routine' },
    { label: 'Repair',              value: 'repair' },
    { label: 'Servicing',           value: 'servicing' },
    { label: 'Calibration',         value: 'calibration' },
    { label: 'Upgrade',             value: 'upgrade' },
];
const frequencyOptions = [
    { label: 'One Time',   value: 'one_time' },
    { label: 'Monthly',    value: 'monthly' },
    { label: 'Quarterly',  value: 'quarterly' },
    { label: 'Yearly',     value: 'yearly' },
];

function submit() {
    form.post(route('tenant.assets.maintenance.store'));
}
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Maintenance', url: route('tenant.assets.maintenance.index') },
        { label: 'Schedule' },
    ]">
        <div class="max-w-2xl mx-auto space-y-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Schedule Maintenance</h1>
                <p class="text-sm text-gray-500 mt-0.5">Schedule maintenance for an asset</p>
            </div>

            <Card>
                <template #header>
                    <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                        <Wrench :size="18" class="text-orange-500" />
                        <h3 class="font-semibold text-gray-900">Maintenance Details</h3>
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset *</label>
                            <Select v-model="form.asset_id" :options="assets" optionLabel="asset_tag"
                                optionValue="id" placeholder="Select asset..." class="w-full"
                                filter :disabled="!!preSelectedAsset" :invalid="!!form.errors.asset_id">
                                <template #option="{ option }">
                                    <span class="font-mono text-xs text-blue-600">{{ option.asset_tag }}</span>
                                    <span class="ml-2 text-sm">{{ option.name }}</span>
                                </template>
                            </Select>
                            <p v-if="form.errors.asset_id" class="text-red-500 text-xs mt-1">{{ form.errors.asset_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency *</label>
                                <Select v-model="form.frequency" :options="frequencyOptions" optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date *</label>
                                <DatePicker v-model="form.scheduled_date" dateFormat="dd/mm/yy" class="w-full" showIcon :invalid="!!form.errors.scheduled_date" />
                                <p v-if="form.errors.scheduled_date" class="text-red-500 text-xs mt-1">{{ form.errors.scheduled_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Cost (৳)</label>
                                <InputNumber v-model="form.estimated_cost" :min="0" class="w-full" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Vendor</label>
                                <Select v-model="form.vendor_id" :options="vendors" optionLabel="name" optionValue="id"
                                    placeholder="Select vendor" class="w-full" showClear />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                <Select v-model="form.assigned_to" :options="users" optionLabel="name" optionValue="id"
                                    placeholder="Select user" class="w-full" showClear filter />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <Textarea v-model="form.description" rows="3" class="w-full" placeholder="What needs to be done?" autoResize />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <Textarea v-model="form.notes" rows="2" class="w-full" autoResize />
                        </div>
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-3">
                <Link :href="route('tenant.assets.maintenance.index')">
                    <Button label="Cancel" severity="secondary" outlined />
                </Link>
                <Button label="Schedule Maintenance" @click="submit" :loading="form.processing" />
            </div>
        </div>
    </TenantLayout>
</template>