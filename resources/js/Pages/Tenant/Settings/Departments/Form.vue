<script setup>
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import { Building2 } from 'lucide-vue-next';

// PrimeVue v4 → ToggleSwitch | PrimeVue v3 → InputSwitch
// Change the import + tag below if you're on v3
import ToggleSwitch from 'primevue/toggleswitch';

const props  = defineProps({ department: Object, heads: Array });
const isEdit = !!props.department;

const form = useForm({
    code:               props.department?.code               ?? '',
    name:               props.department?.name               ?? '',
    description:        props.department?.description        ?? '',
    head_id:            props.department?.head_id            ?? null,
    annual_budget:      Number(props.department?.annual_budget)      || 0,
    approval_threshold: Number(props.department?.approval_threshold) || 50000,
    is_active:          props.department?.is_active          ?? true,
    phone:              props.department?.phone              ?? '',
    email:              props.department?.email              ?? '',
    location:           props.department?.location           ?? '',
});

const submit = () => isEdit
    ? form.put(route('tenant.settings.departments.update', props.department.id))
    : form.post(route('tenant.settings.departments.store'));
</script>

<template>
    <TenantLayout :breadcrumbItems="[
        { label: 'Settings', route: '/settings' },
        { label: 'Departments', route: route('tenant.settings.departments.index') },
        { label: isEdit ? 'Edit' : 'New' }
    ]">
        <div class="max-w-3xl space-y-4">

            <div class="flex items-center gap-2">
                <Building2 :size="26" class="text-blue-600" />
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ isEdit ? 'Edit Department' : 'New Department' }}
                </h1>
            </div>

            <Card>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Code <span class="text-red-500">*</span></label>
                            <InputText v-model="form.code" placeholder="e.g. ENG"
                                class="w-full" :class="{ 'p-invalid': form.errors.code }" />
                            <small class="text-red-500">{{ form.errors.code }}</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Name <span class="text-red-500">*</span></label>
                            <InputText v-model="form.name" placeholder="e.g. English Department"
                                class="w-full" :class="{ 'p-invalid': form.errors.name }" />
                            <small class="text-red-500">{{ form.errors.name }}</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Department Head</label>
                            <Dropdown v-model="form.head_id" :options="heads" optionLabel="name" optionValue="id"
                                placeholder="Select head" showClear filter class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Approval Threshold (BDT)</label>
                            <InputNumber v-model="form.approval_threshold" :min="0" :minFractionDigits="2" class="w-full" />
                            <small class="text-gray-400 text-xs">PRs above this need VP/Principal approval</small>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Annual Budget (BDT)</label>
                            <InputNumber v-model="form.annual_budget" :min="0" :minFractionDigits="2" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Phone</label>
                            <InputText v-model="form.phone" placeholder="+880-..." class="w-full" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold">Email</label>
                            <InputText v-model="form.email" type="email" class="w-full"
                                :class="{ 'p-invalid': form.errors.email }" />
                            <small class="text-red-500">{{ form.errors.email }}</small>
                        </div>

                        <div class="flex items-center gap-3 mt-2">
                            <!-- PrimeVue v3? Replace ToggleSwitch with InputSwitch -->
                            <ToggleSwitch v-model="form.is_active" />
                            <label class="text-sm font-semibold">Active</label>
                        </div>

                        <div class="md:col-span-2 flex flex-col gap-1">
                            <label class="text-sm font-semibold">Description</label>
                            <Textarea v-model="form.description" :rows="3" class="w-full" autoResize />
                        </div>

                        <div class="md:col-span-2 flex flex-col gap-1">
                            <label class="text-sm font-semibold">Location / Room</label>
                            <InputText v-model="form.location" placeholder="e.g. Block B, Room 204" class="w-full" />
                        </div>

                    </div>
                </template>
            </Card>

            <div class="flex gap-3">
                <Button label="Save" icon="pi pi-check" :loading="form.processing" @click="submit" />
                <Button label="Cancel" severity="secondary" outlined
                    @click="$inertia.visit(route('tenant.settings.departments.index'))" />
            </div>

        </div>
    </TenantLayout>
</template>