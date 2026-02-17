<script setup>
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { Building2 } from 'lucide-vue-next';

const props   = defineProps({ departments: Object, filters: Object });
const confirm = useConfirm();
const toast   = useToast();
const search  = ref(props.filters?.search ?? '');

let timer;
watch(search, (val) => {
    clearTimeout(timer);
    timer = setTimeout(() =>
        router.get(route('tenant.settings.departments.index'), { search: val }, { preserveState: true, replace: true })
    , 400);
});

const destroy = (dept) => {
    confirm.require({
        message: `Delete "${dept.name}"?`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('tenant.settings.departments.destroy', dept.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: `${dept.name} deleted.`, life: 3000 }),
            onError:   (e) => toast.add({ severity: 'error',   summary: 'Error',   detail: Object.values(e)[0], life: 4000 }),
        }),
    });
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Settings', route: '/settings' }, { label: 'Departments' }]">
        <div class="space-y-4">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Building2 :size="26" class="text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Departments</h1>
                        <p class="text-sm text-gray-500">Manage school departments</p>
                    </div>
                </div>
                <Button label="New Department" icon="pi pi-plus"
                    @click="router.visit(route('tenant.settings.departments.create'))" />
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <InputText v-model="search" placeholder="Search name or code…" class="w-full md:w-72" />
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <DataTable :value="departments.data">
                    <template #empty>
                        <div class="text-center py-14 text-gray-400">
                            <Building2 :size="44" class="mx-auto mb-3 opacity-30" />
                            <p>No departments found.</p>
                            <Button label="Add First Department" size="small" class="mt-3"
                                @click="router.visit(route('tenant.settings.departments.create'))" />
                        </div>
                    </template>

                    <Column field="code" header="Code"  style="width:10%" />
                    <Column field="name" header="Name"  style="width:22%" />
                    <Column header="Head"               style="width:18%">
                        <template #body="{ data }">{{ data.head?.name ?? '—' }}</template>
                    </Column>
                    <Column header="Budget (BDT)"       style="width:14%">
                        <template #body="{ data }">{{ Number(data.annual_budget).toLocaleString() }}</template>
                    </Column>
                    <Column header="Threshold (BDT)"    style="width:14%">
                        <template #body="{ data }">{{ Number(data.approval_threshold).toLocaleString() }}</template>
                    </Column>
                    <Column header="Status"             style="width:10%">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                                 :severity="data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                    <Column style="width:9%">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" text rounded size="small"
                                    @click="router.visit(route('tenant.settings.departments.edit', data.id))" />
                                <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                                    @click="destroy(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div v-if="departments.last_page > 1" class="flex justify-center gap-2 p-4 border-t border-gray-100">
                    <Button v-for="p in departments.last_page" :key="p" :label="String(p)"
                        :outlined="p !== departments.current_page" size="small"
                        @click="router.get(route('tenant.settings.departments.index'), { page: p, search })" />
                </div>
            </div>

        </div>
        <ConfirmDialog />
    </TenantLayout>
</template>