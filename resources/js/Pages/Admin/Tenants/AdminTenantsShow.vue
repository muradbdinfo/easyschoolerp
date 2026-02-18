<template>
    <AdminLayout :page-title="`Tenant: ${tenant.name}`">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header with Actions -->
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <Button 
                        icon="pi pi-arrow-left" 
                        text 
                        rounded 
                        @click="$inertia.visit(route('admin.tenants.index'))"
                    />
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ tenant.name }}</h1>
                        <p class="text-gray-500">{{ tenant.subdomain }}.easyschool.local</p>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <Button 
                        label="Edit" 
                        icon="pi pi-pencil" 
                        outlined
                        @click="$inertia.visit(route('admin.tenants.edit', tenant.id))"
                    />
                    <Button 
                        v-if="tenant.status === 'active'"
                        label="Suspend" 
                        icon="pi pi-pause" 
                        severity="warning"
                        @click="suspendTenant"
                    />
                    <Button 
                        v-else-if="tenant.status === 'suspended'"
                        label="Activate" 
                        icon="pi pi-check" 
                        severity="success"
                        @click="activateTenant"
                    />
                </div>
            </div>

            <!-- Status Banner -->
            <div v-if="tenant.status === 'trial'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-clock text-yellow-600 text-xl"></i>
                    <div>
                        <p class="font-medium text-yellow-900">Trial Period Active</p>
                        <p class="text-sm text-yellow-700">
                            Trial ends on {{ formatDate(tenant.trial_ends_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tenant Information Card -->
                <Card class="col-span-2">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-building text-blue-600"></i>
                            <span>Tenant Information</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-500">School Name</label>
                                    <p class="font-medium text-gray-900">{{ tenant.name }}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-gray-500">Subdomain</label>
                                    <p class="font-medium text-gray-900">{{ tenant.subdomain }}</p>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-gray-500">Status</label>
                                    <div class="mt-1">
                                        <Tag :value="tenant.status" :severity="getStatusSeverity(tenant.status)" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-gray-500">Plan</label>
                                    <div class="mt-1">
                                        <Tag :value="tenant.plan.toUpperCase()" severity="info" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-gray-500">Monthly Revenue (MRR)</label>
                                    <p class="font-medium text-gray-900">
                                        ${{ tenant.mrr ? tenant.mrr.toFixed(2) : '0.00' }}
                                    </p>
                                </div>
                                
                                <div>
                                    <label class="text-sm text-gray-500">Database</label>
                                    <p class="font-medium text-gray-900 font-mono text-sm">{{ tenant.database_name }}</p>
                                </div>
                            </div>

                            <Divider />

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-500">Created</label>
                                    <p class="font-medium text-gray-900">{{ formatDate(tenant.created_at) }}</p>
                                </div>
                                
                                <div v-if="tenant.trial_ends_at">
                                    <label class="text-sm text-gray-500">Trial Ends</label>
                                    <p class="font-medium text-gray-900">{{ formatDate(tenant.trial_ends_at) }}</p>
                                </div>
                            </div>

                            <!-- Active Modules -->
                            <div v-if="tenant.active_modules && tenant.active_modules.length">
                                <label class="text-sm text-gray-500 block mb-2">Active Modules</label>
                                <div class="flex flex-wrap gap-2">
                                    <Tag 
                                        v-for="module in tenant.active_modules" 
                                        :key="module"
                                        :value="module"
                                        severity="success"
                                        icon="pi pi-check"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Contact Information Card -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <i class="pi pi-user text-blue-600"></i>
                            <span>Contact Information</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-500">Contact Name</label>
                                <p class="font-medium text-gray-900">{{ tenant.contact_name }}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-500">Email</label>
                                <a 
                                    :href="`mailto:${tenant.contact_email}`" 
                                    class="font-medium text-blue-600 hover:text-blue-700 block"
                                >
                                    {{ tenant.contact_email }}
                                </a>
                            </div>
                            
                            <div v-if="tenant.contact_phone">
                                <label class="text-sm text-gray-500">Phone</label>
                                <a 
                                    :href="`tel:${tenant.contact_phone}`" 
                                    class="font-medium text-blue-600 hover:text-blue-700 block"
                                >
                                    {{ tenant.contact_phone }}
                                </a>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Users Card -->
            <Card>
                <template #title>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-users text-blue-600"></i>
                            <span>Users ({{ tenant.users?.length || 0 }})</span>
                        </div>
                        <Button 
                            label="Add User" 
                            icon="pi pi-plus" 
                            size="small"
                            outlined
                        />
                    </div>
                </template>
                <template #content>
                    <div v-if="tenant.users && tenant.users.length > 0">
                        <DataTable 
                            :value="tenant.users" 
                            :paginator="true" 
                            :rows="10"
                            responsiveLayout="scroll"
                        >
                            <Column field="name" header="Name" sortable></Column>
                            <Column field="email" header="Email" sortable></Column>
                            <Column field="role" header="Role" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.role || 'User'" />
                                </template>
                            </Column>
                            <Column field="created_at" header="Joined" sortable>
                                <template #body="slotProps">
                                    {{ formatDate(slotProps.data.created_at) }}
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 100px">
                                <template #body="slotProps">
                                    <div class="flex gap-2">
                                        <Button icon="pi pi-eye" text rounded size="small" />
                                        <Button icon="pi pi-pencil" text rounded size="small" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        <i class="pi pi-users text-4xl mb-3 block"></i>
                        <p>No users found for this tenant</p>
                    </div>
                </template>
            </Card>

            <!-- Quick Links -->
            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-link text-blue-600"></i>
                        <span>Quick Links</span>
                    </div>
                </template>
                <template #content>
                    <div class="flex flex-wrap gap-3">
                        <a 
                            :href="`http://${tenant.subdomain}.easyschool.local`" 
                            target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition"
                        >
                            <i class="pi pi-external-link"></i>
                            <span>Visit Tenant Site</span>
                        </a>
                        
                        <Button 
                            label="View Subscription" 
                            icon="pi pi-credit-card" 
                            outlined
                        />
                        
                        <Button 
                            label="View Payments" 
                            icon="pi pi-dollar" 
                            outlined
                        />
                        
                        <Button 
                            label="Activity Log" 
                            icon="pi pi-history" 
                            outlined
                        />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Suspend Confirmation Dialog -->
        <ConfirmDialog></ConfirmDialog>
    </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    tenant: Object,
});

const confirm = useConfirm();
const toast = useToast();

const getStatusSeverity = (status) => {
    const severityMap = {
        trial: 'warning',
        active: 'success',
        suspended: 'danger',
        cancelled: 'secondary',
    };
    return severityMap[status] || 'info';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const suspendTenant = () => {
    confirm.require({
        message: `Are you sure you want to suspend ${props.tenant.name}? Users will not be able to access their account.`,
        header: 'Suspend Tenant',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-warning',
        accept: () => {
            router.post(route('admin.tenants.suspend', props.tenant.id), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Tenant suspended successfully',
                        life: 3000
                    });
                },
            });
        },
    });
};

const activateTenant = () => {
    confirm.require({
        message: `Are you sure you want to activate ${props.tenant.name}?`,
        header: 'Activate Tenant',
        icon: 'pi pi-check-circle',
        acceptClass: 'p-button-success',
        accept: () => {
            router.post(route('admin.tenants.activate', props.tenant.id), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Tenant activated successfully',
                        life: 3000
                    });
                },
            });
        },
    });
};
</script>