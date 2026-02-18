<script setup>
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import AssetForm from './AssetForm.vue';

const props = defineProps({
    categories: Array,
    branches:   Array,
    vendors:    Array,
    users:      Array,
});

const form = useForm({
    name:                   '',
    category_id:            null,
    item_id:                null,
    acquisition_date:       null,
    acquisition_cost:       0,
    vendor_id:              null,
    invoice_number:         '',
    po_number:              '',
    grn_number:             '',
    brand:                  '',
    model_number:           '',
    serial_number:          '',
    color:                  '',
    specifications:         '',
    description:            '',
    branch_id:              null,
    building:               '',
    floor:                  '',
    room:                   '',
    location_details:       '',
    custodian_id:           null,
    custodian_assigned_date:null,
    depreciation_method:    'slm',
    depreciation_rate:      10,
    useful_life_years:      5,
    residual_value_percent: 10,
    depreciation_start_date:null,
    warranty_months:        null,
    warranty_provider:      '',
    insurance_company:      '',
    insurance_policy_number:'',
    insured_value:          null,
    insurance_expiry_date:  null,
    status:                 'active',
    condition:              'good',
    notes:                  '',
    primary_photo:          null,
});

function submit() {
    form.post(route('tenant.assets.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Assets' }, { label: 'Register', url: route('tenant.assets.index') }, { label: 'New Asset' }]">
        <div class="max-w-4xl mx-auto">
            <div class="mb-5">
                <h1 class="text-2xl font-bold text-gray-900">Register New Asset</h1>
                <p class="text-sm text-gray-500 mt-0.5">Add a new asset to the register</p>
            </div>

            <AssetForm
                :form="form"
                :categories="categories"
                :branches="branches"
                :vendors="vendors"
                :users="users"
                :errors="form.errors"
                :isEdit="false"
                @submit="submit"
            />
        </div>
    </TenantLayout>
</template>