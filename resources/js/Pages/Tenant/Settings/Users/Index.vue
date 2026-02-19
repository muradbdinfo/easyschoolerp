<script setup>
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import DataTable    from 'primevue/datatable';
import Column       from 'primevue/column';
import Button       from 'primevue/button';
import InputText    from 'primevue/inputtext';
import Select       from 'primevue/select';
import Tag          from 'primevue/tag';
import Avatar       from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { Users, UserPlus, UserCheck, UserX, Shield } from 'lucide-vue-next';

const props   = defineProps({
    users:       Object,
    stats:       Object,
    branches:    Array,
    departments: Array,
    filters:     Object,
    roles:       Object,
});

const confirm = useConfirm();
const toast   = useToast();
const page    = usePage();

// Flash messages
const flash = computed(() => page.props.flash ?? {});
watch(flash, (f) => {
    if (f.success) toast.add({ severity: 'success', summary: 'Success', detail: f.success, life: 3500 });
    if (f.error)   toast.add({ severity: 'error',   summary: 'Error',   detail: f.error,   life: 5000 });
}, { deep: true });

// Filters
const search    = ref(props.filters?.search    ?? '');
const roleFilter= ref(props.filters?.role      ?? '');
const branchFilter = ref(props.filters?.branch_id ?? '');
const statusFilter = ref(props.filters?.status ?? '');

let timer;
const applyFilters = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(route('tenant.settings.users.index'), {
            search:    search.value    || undefined,
            role:      roleFilter.value || undefined,
            branch_id: branchFilter.value || undefined,
            status:    statusFilter.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
};

watch([search, roleFilter, branchFilter, statusFilter], applyFilters);

// Role options for filter dropdown
const roleOptions = computed(() => [
    { label: 'All Roles', value: '' },
    ...Object.entries(props.roles).map(([value, label]) => ({ label, value })),
]);

const statusOptions = [
    { label: 'All Status', value: '' },
    { label: 'Active',     value: 'active' },
    { label: 'Inactive',   value: 'inactive' },
];

// Actions
const toggleActive = (user) => {
    const action = user.is_active ? 'deactivate' : 'activate';
    confirm.require({
        message: `${action.charAt(0).toUpperCase() + action.slice(1)} "${user.name}"?`,
        header: 'Confirm',
        icon: user.is_active ? 'pi pi-ban' : 'pi pi-check-circle',
        acceptClass: user.is_active ? 'p-button-warning' : 'p-button-success',
        acceptLabel: action.charAt(0).toUpperCase() + action.slice(1),
        rejectLabel: 'Cancel',
        accept: () => router.post(route('tenant.settings.users.toggle-active', user.id), {}, {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Done', detail: `User ${action}d.`, life: 3000 }),
        }),
    });
};

const destroy = (user) => {
    confirm.require({
        message: `Permanently delete "${user.name}"? This cannot be undone.`,
        header: 'Delete User',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Delete',
        rejectLabel: 'Cancel',
        accept: () => router.delete(route('tenant.settings.users.destroy', user.id), {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: `${user.name} deleted.`, life: 3000 }),
            onError:   (e) => toast.add({ severity: 'error',   summary: 'Error',   detail: Object.values(e)[0], life: 5000 }),
        }),
    });
};

const getInitials = (name) => {
    const parts = (name ?? '').split(' ');
    return parts.length >= 2
        ? parts[0][0] + parts[parts.length - 1][0]
        : (name ?? '??').substring(0, 2).toUpperCase();
};

const roleColor = (role) => ({
    admin:     'contrast',
    principal: 'contrast',
    vp:        'info',
    dept_head: 'info',
    teacher:   'secondary',
    staff:     'secondary',
}[role] ?? 'secondary');
</script>

<template>
    <TenantLayout :breadcrumbItems="[{ label:'Settings' }, { label:'Users' }]">
        <div class="space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Users :size="26" class="text-blue-600" />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                        <p class="text-sm text-gray-500">Manage staff accounts and roles</p>
                    </div>
                </div>
                <Button
                    label="Add User"
                    icon="pi pi-plus"
                    @click="router.visit(route('tenant.settings.users.create'))"
                />
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <Users :size="20" class="text-blue-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                        <p class="text-xs text-gray-500">Total Users</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                        <UserCheck :size="20" class="text-green-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
                        <p class="text-xs text-gray-500">Active</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                        <UserX :size="20" class="text-gray-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.inactive }}</p>
                        <p class="text-xs text-gray-500">Inactive</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
                <InputText
                    v-model="search"
                    placeholder="Search name or email…"
                    class="w-full sm:w-64"
                />
                <Select
                    v-model="roleFilter"
                    :options="roleOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Roles"
                    class="w-44"
                />
                <Select
                    v-model="branchFilter"
                    :options="[{ id: '', name: 'All Branches' }, ...branches]"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="All Branches"
                    class="w-44"
                />
                <Select
                    v-model="statusFilter"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All Status"
                    class="w-36"
                />
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <DataTable :value="users.data" :loading="false">
                    <template #empty>
                        <div class="text-center py-16 text-gray-400">
                            <Users :size="44" class="mx-auto mb-3 opacity-25" />
                            <p class="font-medium">No users found</p>
                            <Button
                                label="Add First User"
                                size="small"
                                class="mt-3"
                                @click="router.visit(route('tenant.settings.users.create'))"
                            />
                        </div>
                    </template>

                    <!-- User column -->
                    <Column header="User" style="width:32%">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar
                                    :label="getInitials(data.name)"
                                    shape="circle"
                                    class="!bg-blue-600 !text-white flex-shrink-0"
                                />
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ data.name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ data.email }}</p>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Role -->
                    <Column header="Role" style="width:14%">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1.5">
                                <Shield :size="13" class="text-gray-400" />
                                <Tag
                                    :value="roles[data.role] ?? data.role"
                                    :severity="roleColor(data.role)"
                                    class="text-xs"
                                />
                            </div>
                        </template>
                    </Column>

                    <!-- Branch -->
                    <Column header="Branch" style="width:16%">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-700">{{ data.branch?.name ?? '—' }}</span>
                        </template>
                    </Column>

                    <!-- Department -->
                    <Column header="Department" style="width:16%">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-700">{{ data.department?.name ?? '—' }}</span>
                        </template>
                    </Column>

                    <!-- Phone -->
                    <Column header="Phone" style="width:12%">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-600">{{ data.phone ?? '—' }}</span>
                        </template>
                    </Column>

                    <!-- Status -->
                    <Column header="Status" style="width:8%">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_active ? 'Active' : 'Inactive'"
                                :severity="data.is_active ? 'success' : 'secondary'"
                            />
                        </template>
                    </Column>

                    <!-- Actions -->
                    <Column style="width:8%">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    text rounded size="small"
                                    v-tooltip.top="'Edit'"
                                    @click="router.visit(route('tenant.settings.users.edit', data.id))"
                                />
                                <Button
                                    :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                                    text rounded size="small"
                                    :severity="data.is_active ? 'warning' : 'success'"
                                    :v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                                    @click="toggleActive(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    text rounded size="small"
                                    severity="danger"
                                    v-tooltip.top="'Delete'"
                                    @click="destroy(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <!-- Pagination -->
                <div v-if="users.last_page > 1"
                     class="flex justify-center gap-2 p-4 border-t border-gray-100">
                    <Button
                        v-for="p in users.last_page" :key="p"
                        :label="String(p)"
                        :outlined="p !== users.current_page"
                        size="small"
                        @click="router.get(route('tenant.settings.users.index'), { page: p, search: search.value })"
                    />
                </div>
            </div>

        </div>

        <ConfirmDialog />
    </TenantLayout>
</template>