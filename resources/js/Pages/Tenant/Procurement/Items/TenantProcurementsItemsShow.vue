<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Breadcrumb from 'primevue/breadcrumb';
import Image from 'primevue/image';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({ item: Object, stats: Object });
const toast = useToast();
const confirm = useConfirm();
const activeTab = ref(0);

const editItem = () => router.visit(route('tenant.items.edit', props.item.id));

const deleteItem = () => {
    confirm.require({
        message: `Are you sure you want to delete item "${props.item.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.items.destroy', props.item.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Success', detail: 'Item deleted successfully', life: 3000 }),
            });
        },
    });
};

const getStockSeverity = () => {
    const severityMap = { 'Out of Stock': 'danger', 'Reorder': 'warning', 'Low Stock': 'warning', 'Overstock': 'info', 'In Stock': 'success' };
    return severityMap[props.item.stock_status.label] || 'info';
};
</script>

<template>
    <TenantLayout :title="`Item: ${item.name}`">
        <div class="p-6">
            <Breadcrumb :home="{label: 'Dashboard', url: '/'}" :model="[{label: 'Procurement', url: '/procurement'},{label: 'Items', url: route('tenant.items.index')},{label: item.name}]" class="mb-4" />

            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-0 shadow-sm mb-6">
                <template #content>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-6">
                            <div class="w-32 h-32 bg-white rounded-lg shadow-sm overflow-hidden flex-shrink-0">
                                <Image v-if="item.photo_url" :src="item.photo_url" :alt="item.name" class="w-full h-full object-cover" preview />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100"><i class="pi pi-image text-5xl text-gray-400"></i></div>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-3xl font-bold text-gray-900">{{ item.name }}</h1>
                                <div class="flex items-center gap-3 mt-2 flex-wrap">
                                    <Tag :value="item.code" severity="info" class="font-mono" />
                                    <Tag :value="item.status_badge.label" :severity="item.status_badge.severity" />
                                    <Tag :value="item.type_label" />
                                    <Tag :value="item.stock_status.label" :severity="getStockSeverity()" />
                                </div>
                                <p class="text-gray-600 mt-3" v-if="item.description">{{ item.description }}</p>
                                <div class="mt-3 flex items-center gap-4">
                                    <div><span class="text-sm text-gray-600">Category:</span><span class="font-medium ml-2">{{ item.category?.name || 'N/A' }}</span></div>
                                    <div><span class="text-sm text-gray-600">Unit:</span><span class="font-medium ml-2">{{ item.unit }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" label="Edit" severity="success" @click="editItem" outlined />
                            <Button icon="pi pi-trash" label="Delete" severity="danger" @click="deleteItem" outlined />
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <Card><template #content><div class="flex items-center justify-between"><div><p class="text-sm text-gray-600">Current Stock</p><p class="text-2xl font-bold text-gray-900">{{ item.current_stock }} <span class="text-sm font-normal">{{ item.unit }}</span></p></div><i class="pi pi-box text-4xl text-blue-500"></i></div></template></Card>
                <Card><template #content><div class="flex items-center justify-between"><div><p class="text-sm text-gray-600">Current Price</p><p class="text-2xl font-bold text-green-600">{{ item.current_price?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}</p></div><i class="pi pi-dollar text-4xl text-green-500"></i></div></template></Card>
                <Card><template #content><div class="flex items-center justify-between"><div><p class="text-sm text-gray-600">Reorder Level</p><p class="text-2xl font-bold text-orange-600">{{ item.reorder_level }} <span class="text-sm font-normal">{{ item.unit }}</span></p></div><i class="pi pi-exclamation-triangle text-4xl text-orange-500"></i></div></template></Card>
                <Card><template #content><div class="flex items-center justify-between"><div><p class="text-sm text-gray-600">Lead Time</p><p class="text-2xl font-bold text-purple-600">{{ item.lead_time_days }} <span class="text-sm font-normal">days</span></p></div><i class="pi pi-clock text-4xl text-purple-500"></i></div></template></Card>
            </div>

            <TabView v-model:activeIndex="activeTab">
                <TabPanel header="Details">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card><template #title>Pricing Information</template><template #content><div class="space-y-3"><div class="flex justify-between"><span class="text-gray-600">Current Price:</span><span class="font-semibold text-lg">{{ item.current_price?.toLocaleString('en-BD', { style: 'currency', currency: 'BDT' }) }}</span></div></div></template></Card>
                        <Card><template #title>Stock Information</template><template #content><div class="space-y-3"><div class="flex justify-between"><span class="text-gray-600">Current Stock:</span><span class="font-semibold text-lg">{{ item.current_stock }} {{ item.unit }}</span></div><Divider /><div class="flex justify-between"><span class="text-gray-600">Minimum Level:</span><span class="font-medium">{{ item.min_stock_level }} {{ item.unit }}</span></div></div></template></Card>
                    </div>
                </TabPanel>
                <TabPanel header="Stock History">
                    <Card><template #content><div class="text-center py-8"><i class="pi pi-chart-line text-6xl text-gray-400 mb-4"></i><p class="text-gray-500 text-lg">Stock history coming soon</p></div></template></Card>
                </TabPanel>
                <TabPanel header="Activity">
                    <Card><template #content><div class="space-y-3 text-sm"><div class="flex justify-between"><span class="text-gray-600">Created by:</span><span class="font-medium">{{ item.creator?.name || 'System' }}</span></div><div class="flex justify-between"><span class="text-gray-600">Created on:</span><span class="font-medium">{{ new Date(item.created_at).toLocaleString() }}</span></div></div></template></Card>
                </TabPanel>
            </TabView>
        </div>
        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>