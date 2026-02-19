<script setup>
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import AssetForm from './AssetForm.vue';

const props = defineProps({
    asset:      Object,
    categories: Array,
    branches:   Array,
    vendors:    Array,
    users:      Array,
});

const form = useForm({
    asset_tag:              props.asset.asset_tag,
    name:                   props.asset.name,
    category_id:            props.asset.category_id,
    acquisition_date:       props.asset.acquisition_date,
    acquisition_cost:       parseFloat(props.asset.acquisition_cost),
    vendor_id:              props.asset.vendor_id,
    invoice_number:         props.asset.invoice_number ?? '',
    po_number:              props.asset.po_number ?? '',
    grn_number:             props.asset.grn_number ?? '',
    brand:                  props.asset.brand ?? '',
    model_number:           props.asset.model_number ?? '',
    serial_number:          props.asset.serial_number ?? '',
    color:                  props.asset.color ?? '',
    specifications:         props.asset.specifications ?? '',
    description:            props.asset.description ?? '',
    branch_id:              props.asset.branch_id,
    building:               props.asset.building ?? '',
    floor:                  props.asset.floor ?? '',
    room:                   props.asset.room ?? '',
    location_details:       props.asset.location_details ?? '',
    custodian_id:           props.asset.custodian_id,
    custodian_assigned_date:props.asset.custodian_assigned_date,
    depreciation_method:    props.asset.depreciation_method,
    depreciation_rate:      parseFloat(props.asset.depreciation_rate),
    useful_life_years:      props.asset.useful_life_years,
    residual_value_percent: parseFloat(props.asset.residual_value_percent),
    depreciation_start_date:props.asset.depreciation_start_date,
    warranty_months:        props.asset.warranty_months,
    warranty_provider:      props.asset.warranty_provider ?? '',
    insurance_company:      props.asset.insurance_company ?? '',
    insurance_policy_number:props.asset.insurance_policy_number ?? '',
    insured_value:          props.asset.insured_value ? parseFloat(props.asset.insured_value) : null,
    insurance_expiry_date:  props.asset.insurance_expiry_date,
    status:                 props.asset.status,
    condition:              props.asset.condition,
    notes:                  props.asset.notes ?? '',
    primary_photo:          null,
    primary_photo_url:      props.asset.primary_photo_url,
    _method:                'PUT',
});

function submit() {
    form.post(route('tenant.assets.update', props.asset.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Assets' },
        { label: 'Register', url: route('tenant.assets.index') },
        { label: asset.asset_tag, url: route('tenant.assets.show', asset.id) },
        { label: 'Edit' },
    ]">
        <div class="max-w-4xl mx-auto">
            <div class="mb-5">
                <h1 class="text-2xl font-bold text-gray-900">Edit Asset</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ asset.asset_tag }} — {{ asset.name }}</p>
            </div>

            <AssetForm
                :form="form"
                :categories="categories"
                :branches="branches"
                :vendors="vendors"
                :users="users"
                :errors="form.errors"
                :isEdit="true"
                @submit="submit"
            />
        </div>
    </TenantLayout>
</template>