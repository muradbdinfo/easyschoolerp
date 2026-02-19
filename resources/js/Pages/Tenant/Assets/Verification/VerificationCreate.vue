<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';
import { CheckSquare, Info } from 'lucide-vue-next';

const props = defineProps({ assetCount: Number });
const toast = useToast();

const form = ref({
    name: '',
    cycle_year: new Date().getFullYear(),
    start_date: null,
    end_date: null,
    scope: 'all',
});

const errors = ref({});
const submitting = ref(false);

const scopeOptions = [
    { label: 'All Active Assets', value: 'all' },
    { label: 'By Branch', value: 'branch' },
    { label: 'By Category', value: 'category' },
];

const yearOptions = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 1 + i);

const fmtDate = (d) => d ? new Date(d).toISOString().split('T')[0] : null;

const submit = () => {
    submitting.value = true;
    errors.value = {};

    router.post(route('tenant.assets.verification.store'), {
        ...form.value,
        start_date: fmtDate(form.value.start_date),
        end_date:   fmtDate(form.value.end_date),
    }, {
        onError: (e) => { errors.value = e; },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Verification', url: route('tenant.assets.verification.index') },
        { label: 'New Cycle' },
    ]">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <CheckSquare class="w-7 h-7 text-blue-600" />
                Create Verification Cycle
            </h1>

            <Message severity="info" :closable="false">
                <template #icon><Info class="w-4 h-4" /></template>
                This will create a cycle for <strong>{{ assetCount }} active assets</strong> (with "All" scope).
                Each asset will need to be physically verified.
            </Message>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cycle Name <span class="text-red-500">*</span></label>
                    <InputText v-model="form.name" placeholder="e.g. Annual Verification 2025" class="w-full"
                        :class="{ 'p-invalid': errors.name }" />
                    <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name }}</p>
                </div>

                <!-- Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cycle Year <span class="text-red-500">*</span></label>
                    <Select v-model="form.cycle_year" :options="yearOptions" class="w-40" />
                    <p v-if="errors.cycle_year" class="text-red-500 text-xs mt-1">{{ errors.cycle_year }}</p>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                        <DatePicker v-model="form.start_date" class="w-full" showIcon dateFormat="dd/mm/yy"
                            :class="{ 'p-invalid': errors.start_date }" />
                        <p v-if="errors.start_date" class="text-red-500 text-xs mt-1">{{ errors.start_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                        <DatePicker v-model="form.end_date" class="w-full" showIcon dateFormat="dd/mm/yy"
                            :class="{ 'p-invalid': errors.end_date }" />
                        <p v-if="errors.end_date" class="text-red-500 text-xs mt-1">{{ errors.end_date }}</p>
                    </div>
                </div>

                <!-- Scope -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                    <Select v-model="form.scope" :options="scopeOptions" optionLabel="label" optionValue="value" class="w-full" />
                    <p class="text-xs text-gray-500 mt-1">
                        Currently only "All Active Assets" is supported. Branch/Category filtering coming soon.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                    <Button label="Cancel" severity="secondary" outlined
                        @click="router.visit(route('tenant.assets.verification.index'))" />
                    <Button label="Create Cycle" icon="pi pi-check" severity="primary"
                        :loading="submitting" @click="submit" />
                </div>
            </div>
        </div>
    </TenantLayout>
</template>