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
import { GitBranch } from 'lucide-vue-next';

const props   = defineProps({ branches: Object, filters: Object });
const confirm = useConfirm();
const toast   = useToast();
const search  = ref(props.filters?.search ?? '');

let timer;
watch(search, (val) => {
    clearTimeout(timer);
    timer = setTimeout(() =>
        router.get(route('tenant.settings.branches.index'), { search: val }, { preserveState: true, replace: true })
    , 400);
});

const destroy = (branch) => {
    confirm.require({
        message: `Delete "${branch.name}"?`,
        header: 'Confirm Delete',
        icon: 'pi pi-trash',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('tenant.settings.branches.destroy', branch.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: `${branch.name} deleted.`, life: 3000 }),
            onError:   (e) => toast.add({ severity: 'error',   summary: 'Error',   detail: Object.values(e)[0], life: 4000 }),
        }),
    });
};
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label: 'Settings', route: '/settings' }, { label: 'Branches' }]">
        <div class="space-y-4">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <GitBranch :size="26" class="text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Branches</h1>
                        <p class="text-sm text-gray-500">Manage school branches / campuses</p>
                    </div>
                </div>
                <Button label="New Branch" icon="pi pi-plus"
                    @click="router.visit(route('tenant.settings.branches.create'))" />
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <InputText v-model="search" placeholder="Search name or code…" class="w-full md:w-72" />
            </div>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <DataTable :value="branches.data">
                    <template #empty>
                        <div class="text-center py-14 text-gray-400">
                            <GitBranch :size="44" class="mx-auto mb-3 opacity-30" />
                            <p>No branches found.</p>
                            <Button label="Add First Branch" size="small" class="mt-3"
                                @click="router.visit(route('tenant.settings.branches.create'))" />
                        </div>
                    </template>

                    <Column field="code" header="Code"      style="width:10%" />
                    <Column field="name" header="Name"      style="width:22%" />
                    <Column header="Head"                   style="width:15%">
                        <template #body="{ data }">{{ data.head?.name ?? '—' }}</template>
                    </Column>
                    <Column header="City"                   style="width:12%">
                        <template #body="{ data }">{{ data.city ?? '—' }}</template>
                    </Column>
                    <Column header="Capacity"               style="width:10%">
                        <template #body="{ data }">{{ data.student_capacity ?? '—' }}</template>
                    </Column>
                    <Column header="Main"                   style="width:8%">
                        <template #body="{ data }">
                            <Tag v-if="data.is_main_branch" value="Main" severity="info" />
                            <span v-else class="text-gray-300">—</span>
                        </template>
                    </Column>
                    <Column header="Status"                 style="width:10%">
                        <template #body="{ data }">
                            <Tag :value="data.is_active ? 'Active' : 'Inactive'"
                                 :severity="data.is_active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                    <Column style="width:9%">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" text rounded size="small"
                                    @click="router.visit(route('tenant.settings.branches.edit', data.id))" />
                                <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                                    @click="destroy(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div v-if="branches.last_page > 1" class="flex justify-center gap-2 p-4 border-t border-gray-100">
                    <Button v-for="p in branches.last_page" :key="p" :label="String(p)"
                        :outlined="p !== branches.current_page" size="small"
                        @click="router.get(route('tenant.settings.branches.index'), { page: p, search })" />
                </div>
            </div>

        </div>
        <ConfirmDialog />
    </TenantLayout>
</template>