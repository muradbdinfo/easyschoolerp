<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { MoveRight, Building, User } from 'lucide-vue-next';

const props = defineProps({
    preSelectedAsset: Object,
    assets:           Array,
    branches:         Array,
    users:            Array,
});

const form = useForm({
    asset_id:        props.preSelectedAsset?.id ?? null,
    to_branch_id:    null,
    to_location:     '',
    to_custodian_id: null,
    transfer_date:   new Date(),
    reason:          '',
    condition_before:'good',
    notes:           '',
});

const conditionOptions = [
    { label: 'Excellent', value: 'excellent' },
    { label: 'Good',      value: 'good' },
    { label: 'Fair',      value: 'fair' },
    { label: 'Poor',      value: 'poor' },
];

const selectedAsset = computed(() =>
    props.preSelectedAsset ?? props.assets.find(a => a.id === form.asset_id)
);

function submit() {
    form.post(route('tenant.assets.transfers.store'));
}
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Transfers', url: route('tenant.assets.transfers.index') },
        { label: 'New Transfer' },
    ]">
        <div class="max-w-2xl mx-auto space-y-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transfer Asset</h1>
                <p class="text-sm text-gray-500 mt-0.5">Move asset to a new location or custodian</p>
            </div>

            <!-- Select Asset -->
            <Card>
                <template #content>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Asset to Transfer *</label>
                            <Select v-model="form.asset_id" :options="assets" optionLabel="asset_tag"
                                optionValue="id" placeholder="Select or search asset..." class="w-full"
                                filter :disabled="!!preSelectedAsset">
                                <template #option="{ option }">
                                    <div>
                                        <span class="font-mono text-xs text-blue-600">{{ option.asset_tag }}</span>
                                        <span class="ml-2 text-sm">{{ option.name }}</span>
                                    </div>
                                </template>
                            </Select>
                        </div>

                        <!-- Current Details (read only) -->
                        <div v-if="selectedAsset" class="bg-gray-50 rounded-lg p-3 grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <Building :size="13" class="text-gray-400" />
                                <span>{{ selectedAsset.branch?.name ?? 'No Branch' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <User :size="13" class="text-gray-400" />
                                <span>{{ selectedAsset.custodian?.name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Transfer Details -->
            <Card>
                <template #header>
                    <div class="flex items-center gap-2 px-4 pt-4 pb-1">
                        <MoveRight :size="18" class="text-blue-500" />
                        <h3 class="font-semibold text-gray-900">Transfer Details</h3>
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Branch *</label>
                                <Select v-model="form.to_branch_id" :options="branches" optionLabel="name" optionValue="id"
                                    placeholder="Select branch" class="w-full" :invalid="!!form.errors.to_branch_id" />
                                <p v-if="form.errors.to_branch_id" class="text-red-500 text-xs mt-1">{{ form.errors.to_branch_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Date *</label>
                                <DatePicker v-model="form.transfer_date" dateFormat="dd/mm/yy" class="w-full" showIcon />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Location</label>
                            <InputText v-model="form.to_location" class="w-full" placeholder="Room, Building, Floor..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Custodian</label>
                            <Select v-model="form.to_custodian_id" :options="users" optionLabel="name" optionValue="id"
                                placeholder="Select user" class="w-full" showClear filter />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Condition Before Transfer</label>
                            <Select v-model="form.condition_before" :options="conditionOptions"
                                optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Transfer *</label>
                            <Textarea v-model="form.reason" rows="3" class="w-full"
                                placeholder="Why is this asset being transferred?" :invalid="!!form.errors.reason" autoResize />
                            <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <Textarea v-model="form.notes" rows="2" class="w-full" autoResize />
                        </div>

                        <div v-if="form.to_branch_id && selectedAsset?.branch_id && form.to_branch_id !== selectedAsset.branch_id"
                            class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-700">
                            ⚠ Cross-branch transfer requires principal approval before executing.
                        </div>
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-3">
                <Link :href="route('tenant.assets.transfers.index')">
                    <Button label="Cancel" severity="secondary" outlined />
                </Link>
                <Button label="Submit Transfer" @click="submit" :loading="form.processing" />
            </div>
        </div>
    </TenantLayout>
</template>