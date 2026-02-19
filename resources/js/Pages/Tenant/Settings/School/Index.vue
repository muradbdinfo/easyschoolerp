<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import TenantLayout  from '@/Layouts/TenantLayout.vue';
import InputText     from 'primevue/inputtext';
import Textarea      from 'primevue/textarea';
import Select        from 'primevue/select';
import Button        from 'primevue/button';
import ToggleSwitch  from 'primevue/toggleswitch';
import Tag           from 'primevue/tag';
import { useToast }  from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog  from 'primevue/confirmdialog';
import {
    School, Upload, Trash2, Palette, Globe, Calendar,
    Building, ShoppingCart, Box, Info, Settings,
} from 'lucide-vue-next';

const props = defineProps({
    tenant:     Object,
    branches:   Array,
    allModules: Object,
    logoUrl:    String,
});

const toast   = useToast();
const confirm = useConfirm();
const page    = usePage();

// ── School Info Form ───────────────────────────────────────────────────────
const infoForm = useForm({
    name:          props.tenant.name          ?? '',
    contact_name:  props.tenant.contact_name  ?? '',
    contact_email: props.tenant.contact_email ?? '',
    contact_phone: props.tenant.contact_phone ?? '',
    primary_color: props.tenant.primary_color ?? '#3b82f6',
    // settings sub-fields
    'settings.address':             props.tenant.settings?.address             ?? '',
    'settings.city':                props.tenant.settings?.city                ?? '',
    'settings.country':             props.tenant.settings?.country             ?? 'Bangladesh',
    'settings.phone':               props.tenant.settings?.phone               ?? '',
    'settings.website':             props.tenant.settings?.website             ?? '',
    'settings.academic_year_start': props.tenant.settings?.academic_year_start ?? '01-01',
    'settings.academic_year_end':   props.tenant.settings?.academic_year_end   ?? '12-31',
    'settings.currency':            props.tenant.settings?.currency            ?? 'BDT',
    'settings.timezone':            props.tenant.settings?.timezone            ?? 'Asia/Dhaka',
    'settings.date_format':         props.tenant.settings?.date_format         ?? 'DD/MM/YYYY',
});

const submitInfo = () => {
    infoForm.post(route('tenant.settings.school.update'), {
        onSuccess: () => toast.add({ severity:'success', summary:'Saved', detail:'School settings updated.', life:3500 }),
        onError:   () => toast.add({ severity:'error',   summary:'Error',  detail:'Please fix the errors below.', life:4000 }),
    });
};

// ── Logo ──────────────────────────────────────────────────────────────────
const logoInput      = ref(null);
const logoPreviewUrl = ref(props.logoUrl ?? null);
const logoFile       = ref(null);
const logoUploading  = ref(false);

const onLogoChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'warn', summary: 'Too large', detail: 'Logo must be under 2 MB.', life: 4000 });
        return;
    }
    logoFile.value = file;
    logoPreviewUrl.value = URL.createObjectURL(file);
};

const uploadLogo = () => {
    if (!logoFile.value) return;
    const fd = new FormData();
    fd.append('logo', logoFile.value);
    fd.append('name', infoForm.name);           // backend requires name
    fd.append('_method', 'POST');
    logoUploading.value = true;
    router.post(route('tenant.settings.school.update'), fd, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            logoUploading.value = false;
            logoFile.value = null;
            toast.add({ severity: 'success', summary: 'Logo uploaded', detail: 'Logo updated successfully.', life: 3000 });
        },
        onError: (e) => {
            logoUploading.value = false;
            toast.add({ severity: 'error', summary: 'Upload failed', detail: Object.values(e)[0], life: 4000 });
        },
    });
};

const removeLogo = () => {
    confirm.require({
        message: 'Remove the school logo?',
        header: 'Confirm',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remove',
        rejectLabel: 'Cancel',
        accept: () => router.post(route('tenant.settings.school.remove-logo'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                logoPreviewUrl.value = null;
                logoFile.value = null;
                toast.add({ severity: 'success', summary: 'Removed', detail: 'Logo removed.', life: 3000 });
            },
        }),
    });
};

// ── Modules Form ──────────────────────────────────────────────────────────
const activeModules = ref(new Set(props.tenant.active_modules ?? []));

const toggleModule = (key) => {
    if (activeModules.value.has(key)) {
        activeModules.value.delete(key);
    } else {
        activeModules.value.add(key);
    }
};

const moduleIcons = { procurement: ShoppingCart, assets: Box };

const savingModules = ref(false);
const submitModules = () => {
    savingModules.value = true;
    router.post(route('tenant.settings.school.modules'), {
        active_modules: [...activeModules.value],
    }, {
        preserveScroll: true,
        onFinish: () => { savingModules.value = false; },
        onSuccess: () => toast.add({ severity:'success', summary:'Saved', detail:'Modules updated.', life:3000 }),
        onError:   (e) => toast.add({ severity:'error',   summary:'Error',  detail: Object.values(e)[0], life:4000 }),
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────
const timezones = [
    { label: 'Asia/Dhaka (GMT+6)',      value: 'Asia/Dhaka'      },
    { label: 'Asia/Kolkata (GMT+5:30)', value: 'Asia/Kolkata'    },
    { label: 'Asia/Karachi (GMT+5)',    value: 'Asia/Karachi'    },
    { label: 'UTC',                     value: 'UTC'             },
];

const dateFormats = [
    { label: 'DD/MM/YYYY (31/12/2024)', value: 'DD/MM/YYYY' },
    { label: 'MM/DD/YYYY (12/31/2024)', value: 'MM/DD/YYYY' },
    { label: 'YYYY-MM-DD (2024-12-31)', value: 'YYYY-MM-DD' },
];

const currencies = [
    { label: 'BDT (৳)',  value: 'BDT' },
    { label: 'USD ($)',   value: 'USD' },
    { label: 'EUR (€)',   value: 'EUR' },
    { label: 'GBP (£)',   value: 'GBP' },
    { label: 'INR (₹)',   value: 'INR' },
];

const monthDayOptions = Array.from({ length: 12 }, (_, m) => {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return { label: `1 ${months[m]}`, value: `${String(m+1).padStart(2,'0')}-01` };
});
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label:'Settings' }, { label:'School Settings' }]">
        <div class="max-w-3xl space-y-7">

            <!-- Page Header -->
            <div class="flex items-center gap-3">
                <Settings :size="26" class="text-blue-600" />
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">School Settings</h1>
                    <p class="text-sm text-gray-500">Configure your school information, branding, and modules</p>
                </div>
            </div>

            <!-- ── 1. Logo ─────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Upload :size="18" class="text-blue-500" />
                    <h2 class="font-semibold text-gray-800">School Logo</h2>
                </div>

                <div class="flex items-start gap-6">
                    <!-- Preview -->
                    <div class="flex-shrink-0">
                        <div v-if="logoPreviewUrl"
                             class="w-24 h-24 rounded-xl border-2 border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                            <img :src="logoPreviewUrl" alt="Logo" class="object-contain w-full h-full p-1" />
                        </div>
                        <div v-else
                             class="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                            <School :size="30" class="text-gray-300" />
                        </div>
                    </div>

                    <!-- Upload controls -->
                    <div class="flex-1 space-y-3">
                        <p class="text-sm text-gray-600">
                            Upload a PNG or JPG logo (max 2 MB). Recommended size: 200×200px.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <input
                                ref="logoInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onLogoChange"
                            />
                            <Button
                                label="Choose File"
                                icon="pi pi-upload"
                                outlined
                                size="small"
                                @click="logoInput.click()"
                            />
                            <Button
                                v-if="logoFile"
                                label="Upload Logo"
                                icon="pi pi-check"
                                size="small"
                                :loading="logoUploading"
                                @click="uploadLogo"
                            />
                            <Button
                                v-if="logoPreviewUrl && !logoFile"
                                label="Remove"
                                icon="pi pi-trash"
                                text
                                size="small"
                                severity="danger"
                                @click="removeLogo"
                            />
                        </div>
                        <p v-if="logoFile" class="text-xs text-blue-600 font-medium">
                            {{ logoFile.name }} selected — click "Upload Logo" to save.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── 2. School Info Form ───────────────────────────────── -->
            <form @submit.prevent="submitInfo">
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                    <div class="flex items-center gap-2 mb-1">
                        <Building :size="18" class="text-blue-500" />
                        <h2 class="font-semibold text-gray-800">School Information</h2>
                    </div>

                    <!-- School name -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">
                            School Name <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            v-model="infoForm.name"
                            placeholder="e.g. Greenfield International School"
                            class="w-full"
                            :class="{ 'p-invalid': infoForm.errors.name }"
                        />
                        <p v-if="infoForm.errors.name" class="text-xs text-red-500">{{ infoForm.errors.name }}</p>
                    </div>

                    <!-- Address -->
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Address</label>
                        <Textarea
                            v-model="infoForm['settings.address']"
                            placeholder="School street address"
                            rows="2"
                            class="w-full"
                            autoResize
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">City</label>
                            <InputText v-model="infoForm['settings.city']" placeholder="Dhaka" class="w-full" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Country</label>
                            <InputText v-model="infoForm['settings.country']" placeholder="Bangladesh" class="w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">School Phone</label>
                            <InputText v-model="infoForm['settings.phone']" placeholder="02-XXXXXXXX" class="w-full" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Website</label>
                            <InputText v-model="infoForm['settings.website']" placeholder="https://myschool.edu.bd" class="w-full" />
                        </div>
                    </div>
                </div>

                <!-- ── 3. Contact Person ───────────────────────────── -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5 mt-5">
                    <h2 class="font-semibold text-gray-800">Contact Person</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Contact Name</label>
                            <InputText v-model="infoForm.contact_name" placeholder="Principal's name" class="w-full" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Contact Phone</label>
                            <InputText v-model="infoForm.contact_phone" placeholder="01711-XXXXXX" class="w-full" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Contact Email</label>
                        <InputText
                            v-model="infoForm.contact_email"
                            type="email"
                            placeholder="principal@myschool.edu.bd"
                            class="w-full"
                            :class="{ 'p-invalid': infoForm.errors.contact_email }"
                        />
                        <p v-if="infoForm.errors.contact_email" class="text-xs text-red-500">{{ infoForm.errors.contact_email }}</p>
                    </div>
                </div>

                <!-- ── 4. Preferences ─────────────────────────────── -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5 mt-5">
                    <div class="flex items-center gap-2 mb-1">
                        <Globe :size="18" class="text-blue-500" />
                        <h2 class="font-semibold text-gray-800">Regional Preferences</h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Currency</label>
                            <Select
                                v-model="infoForm['settings.currency']"
                                :options="currencies"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Timezone</label>
                            <Select
                                v-model="infoForm['settings.timezone']"
                                :options="timezones"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Date Format</label>
                            <Select
                                v-model="infoForm['settings.date_format']"
                                :options="dateFormats"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- ── 5. Academic Year ───────────────────────────── -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5 mt-5">
                    <div class="flex items-center gap-2 mb-1">
                        <Calendar :size="18" class="text-blue-500" />
                        <h2 class="font-semibold text-gray-800">Academic Year</h2>
                    </div>
                    <p class="text-sm text-gray-500 -mt-2">
                        Used for reports, depreciation cycles, and budget periods.
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Academic Year Starts</label>
                            <Select
                                v-model="infoForm['settings.academic_year_start']"
                                :options="monthDayOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Academic Year Ends</label>
                            <Select
                                v-model="infoForm['settings.academic_year_end']"
                                :options="monthDayOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- ── 6. Brand Color ──────────────────────────────── -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mt-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Palette :size="18" class="text-blue-500" />
                        <h2 class="font-semibold text-gray-800">Brand Color</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <input
                            type="color"
                            v-model="infoForm.primary_color"
                            class="w-12 h-12 rounded-lg border border-gray-200 cursor-pointer p-0.5"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ infoForm.primary_color }}
                            </p>
                            <p class="text-xs text-gray-400">
                                Used for sidebar accents and branding elements.
                            </p>
                        </div>
                        <div
                            class="ml-4 w-8 h-8 rounded-full border border-gray-200"
                            :style="{ backgroundColor: infoForm.primary_color }"
                        />
                    </div>
                </div>

                <!-- Save button -->
                <div class="flex justify-end gap-3 mt-5">
                    <Button
                        type="submit"
                        label="Save Settings"
                        icon="pi pi-check"
                        :loading="infoForm.processing"
                    />
                </div>
            </form>

            <!-- ── 7. Modules ──────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Box :size="18" class="text-blue-500" />
                    <div>
                        <h2 class="font-semibold text-gray-800">Active Modules</h2>
                        <p class="text-xs text-gray-400">
                            Toggle which modules appear in the sidebar. Contact your admin to unlock additional modules.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(mod, key) in allModules" :key="key"
                        class="flex items-center justify-between p-4 rounded-xl border transition"
                        :class="activeModules.has(key)
                            ? 'border-blue-200 bg-blue-50'
                            : 'border-gray-200 bg-gray-50'"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center"
                                :class="activeModules.has(key) ? 'bg-blue-100' : 'bg-gray-200'"
                            >
                                <component
                                    :is="moduleIcons[key]"
                                    :size="20"
                                    :class="activeModules.has(key) ? 'text-blue-600' : 'text-gray-400'"
                                />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ mod.label }}</p>
                                <p class="text-xs text-gray-500">{{ mod.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Tag
                                :value="activeModules.has(key) ? 'Active' : 'Inactive'"
                                :severity="activeModules.has(key) ? 'success' : 'secondary'"
                                class="text-xs"
                            />
                            <ToggleSwitch
                                :modelValue="activeModules.has(key)"
                                @update:modelValue="toggleModule(key)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <Button
                        label="Save Modules"
                        icon="pi pi-check"
                        :loading="savingModules"
                        @click="submitModules"
                    />
                </div>
            </div>

            <!-- ── 8. Plan Info (read-only) ───────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <Info :size="18" class="text-blue-500" />
                    <h2 class="font-semibold text-gray-800">Subscription</h2>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="space-y-1">
                        <p class="text-gray-500">Plan</p>
                        <Tag
                            :value="(tenant.plan ?? 'basic').charAt(0).toUpperCase() + (tenant.plan ?? 'basic').slice(1)"
                            severity="info"
                        />
                    </div>
                    <div class="space-y-1">
                        <p class="text-gray-500">Status</p>
                        <Tag
                            :value="(tenant.status ?? 'trial').charAt(0).toUpperCase() + (tenant.status ?? 'trial').slice(1)"
                            :severity="tenant.status === 'active' ? 'success' : tenant.status === 'suspended' ? 'danger' : 'warning'"
                        />
                    </div>
                    <div v-if="tenant.trial_ends_at" class="space-y-1">
                        <p class="text-gray-500">Trial Ends</p>
                        <p class="font-medium text-gray-800">{{ tenant.trial_ends_at }}</p>
                    </div>
                    <div v-if="tenant.subscription_ends_at" class="space-y-1">
                        <p class="text-gray-500">Subscription Ends</p>
                        <p class="font-medium text-gray-800">{{ tenant.subscription_ends_at }}</p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-400">
                    To upgrade your plan or add modules, contact your system administrator.
                </p>
            </div>

        </div>

        <ConfirmDialog />
    </TenantLayout>
</template>