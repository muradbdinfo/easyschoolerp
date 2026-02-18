<script setup>
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import { GitBranch } from 'lucide-vue-next';

// PrimeVue v4 → ToggleSwitch + DatePicker
// PrimeVue v3 → InputSwitch  + Calendar
// Change both the import AND the tag name below if on v3
import ToggleSwitch from 'primevue/toggleswitch';
import DatePicker from 'primevue/datepicker';

const props  = defineProps({ branch: Object, heads: Array });
const isEdit = !!props.branch;

const form = useForm({
    code:             props.branch?.code             ?? '',
    name:             props.branch?.name             ?? '',
    description:      props.branch?.description      ?? '',
    head_id:          props.branch?.head_id          ?? null,
    address:          props.branch?.address          ?? '',
    city:             props.branch?.city             ?? '',
    district:         props.branch?.district         ?? '',
    postal_code:      props.branch?.postal_code      ?? '',
    country:          props.branch?.country          ?? 'Bangladesh',
    phone:            props.branch?.phone            ?? '',
    email:            props.branch?.email            ?? '',
    fax:              props.branch?.fax              ?? '',
    // DatePicker needs a Date object or null
    established_date: props.branch?.established_date
        ? new Date(props.branch.established_date)
        : null,
    is_active:        props.branch?.is_active        ?? true,
    is_main_branch:   props.branch?.is_main_branch   ?? false,
    student_capacity: props.branch?.student_capacity ?? null,
    staff_count:      props.branch?.staff_count      ?? null,
    annual_budget:    Number(props.branch?.annual_budget) || 0,
});

const submit = () => {
    // Normalise Date → string for Laravel
    if (form.established_date instanceof Date) {
        form.established_date = form.established_date.toISOString().slice(0, 10);
    }
    isEdit
        ? form.put(route('tenant.settings.branches.update', props.branch.id))
        : form.post(route('tenant.settings.branches.store'));
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Settings', route: '/settings' },
        { label: 'Branches', route: route('tenant.settings.branches.index') },
        { label: isEdit ? 'Edit' : 'New' }
    ]">
        <div class="max-w-4xl space-y-4">

            <div class="flex items-center gap-2">
                <GitBranch :size="26" class="text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Branch' : 'New Branch' }}</h1>
            </div>

            <!-- Basic details -->
            <Card>
                <template #title>Basic Details</template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Code <span class="text-red-500">*</span></label>
                            <InputText v-model="form.code" placeholder="e.g. JR"
                                class="w-full" :class="{ 'p-invalid': form.errors.code }" />
                            <small class="text-red-500">{{ form.errors.code }}</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Name <span class="text-red-500">*</span></label>
                            <InputText v-model="form.name" placeholder="e.g. Junior Branch"
                                class="w-full" :class="{ 'p-invalid': form.errors.name }" />
                            <small class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Branch Head</label>
                            <Dropdown v-model="form.head_id" :options="heads" optionLabel="name" optionValue="id"
                                placeholder="Select head" showClear filter class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Established Date</label>
                            <!-- PrimeVue v3? Replace DatePicker with Calendar -->
                            <DatePicker v-model="form.established_date" dateFormat="yy-mm-dd" showIcon class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Student Capacity</label>
                            <InputNumber v-model="form.student_capacity" :min="0" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Staff Count</label>
                            <InputNumber v-model="form.staff_count" :min="0" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Annual Budget (BDT)</label>
                            <InputNumber v-model="form.annual_budget" :min="0" :minFractionDigits="2" class="w-full" />
                        </div>

                        <div class="flex gap-6 items-center mt-2">
                            <div class="flex items-center gap-2">
                                <!-- PrimeVue v3? Replace ToggleSwitch with InputSwitch -->
                                <ToggleSwitch v-model="form.is_active" />
                                <label class="text-sm font-semibold">Active</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <ToggleSwitch v-model="form.is_main_branch" />
                                <label class="text-sm font-semibold">Main Branch</label>
                            </div>
                        </div>

                        <div class="md:col-span-2 flex flex-col gap-1">
                            <label class="text-sm font-semibold">Description</label>
                            <Textarea v-model="form.description" :rows="2" class="w-full" autoResize />
                        </div>

                    </div>
                </template>
            </Card>

            <!-- Location & Contact -->
            <Card>
                <template #title>Location &amp; Contact</template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="md:col-span-2 flex flex-col gap-1">
                            <label class="text-sm font-semibold">Address</label>
                            <Textarea v-model="form.address" :rows="2" placeholder="Street address" class="w-full" autoResize />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">City</label>
                            <InputText v-model="form.city" placeholder="Chattogram" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">District</label>
                            <InputText v-model="form.district" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Postal Code</label>
                            <InputText v-model="form.postal_code" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Country</label>
                            <InputText v-model="form.country" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Phone</label>
                            <InputText v-model="form.phone" placeholder="+880-31-..." class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Email</label>
                            <InputText v-model="form.email" type="email" class="w-full"
                                :class="{ 'p-invalid': form.errors.email }" />
                            <small class="text-red-500">{{ form.errors.email }}</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Fax</label>
                            <InputText v-model="form.fax" class="w-full" />
                        </div>

                    </div>
                </template>
            </Card>

            <div class="flex gap-3">
                <Button label="Save" icon="pi pi-check" :loading="form.processing" @click="submit" />
                <Button label="Cancel" severity="secondary" outlined
                    @click="$inertia.visit(route('tenant.settings.branches.index'))" />
            </div>

        </div>
    </TenantLayout>
</template>