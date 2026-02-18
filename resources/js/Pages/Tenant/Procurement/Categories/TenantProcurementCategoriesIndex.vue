<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import TreeTable from 'primevue/treetable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Message from 'primevue/message';
import Card from 'primevue/card';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({ 
    categories: Array 
});

const toast = useToast();
const confirm = useConfirm();
const showDialog = ref(false);
const editingCategory = ref(null);

const form = useForm({ 
    name: '', 
    parent_id: null, 
    description: '', 
    sort_order: 0, 
    is_active: true 
});

// Transform categories to tree structure
const transformToTree = (categories) => {
    const buildTree = (items, parentId = null) => {
        return items
            .filter(item => item.parent_id === parentId)
            .map(item => ({
                key: item.id.toString(),
                data: {
                    id: item.id,
                    code: item.code,
                    name: item.name,
                    description: item.description || '',
                    sort_order: item.sort_order || 0,
                    is_active: item.is_active,
                },
                children: buildTree(items, item.id)
            }));
    };
    return buildTree(categories);
};

const treeData = ref(transformToTree(props.categories));

const openDialog = (category = null) => {
    if (category) {
        form.name = category.name;
        form.parent_id = category.parent_id;
        form.description = category.description || '';
        form.sort_order = category.sort_order || 0;
        form.is_active = category.is_active;
        editingCategory.value = category;
    } else {
        form.reset();
        form.is_active = true;
        form.sort_order = 0;
        editingCategory.value = null;
    }
    showDialog.value = true;
};

const saveCategory = () => {
    if (editingCategory.value) {
        form.put(route('tenant.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Category updated successfully', life: 3000 });
                showDialog.value = false;
                router.reload({ only: ['categories'] });
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors)[0];
                toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
            }
        });
    } else {
        form.post(route('tenant.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Category created successfully', life: 3000 });
                showDialog.value = false;
                router.reload({ only: ['categories'] });
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors)[0];
                toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
            }
        });
    }
};

const deleteCategory = (category) => {
    confirm.require({
        message: `Are you sure you want to delete category "${category.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('tenant.categories.destroy', category.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Success', detail: 'Category deleted successfully', life: 3000 });
                    router.reload({ only: ['categories'] });
                },
                onError: (errors) => {
                    const errorMsg = Object.values(errors).join(', ');
                    toast.add({ severity: 'error', summary: 'Error', detail: errorMsg, life: 5000 });
                },
            });
        },
    });
};

const getParentOptions = () => {
    const options = [{ id: null, name: 'None (Root Category)' }];
    
    props.categories.forEach(cat => {
        // Don't include the category being edited
        if (!editingCategory.value || cat.id !== editingCategory.value.id) {
            options.push(cat);
        }
    });
    
    return options;
};

const expandAll = () => {
    // Implementation for expand all nodes
    toast.add({ severity: 'info', summary: 'Info', detail: 'Expand all feature', life: 2000 });
};

const collapseAll = () => {
    // Implementation for collapse all nodes
    toast.add({ severity: 'info', summary: 'Info', detail: 'Collapse all feature', life: 2000 });
};
</script>

<template>
    <TenantLayout title="Item Categories">
        <div class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Item Categories</h1>
                        <p class="text-gray-600 mt-1">Manage item categories hierarchy</p>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Expand All" icon="pi pi-plus" @click="expandAll" severity="secondary" outlined size="small" />
                        <Button label="Collapse All" icon="pi pi-minus" @click="collapseAll" severity="secondary" outlined size="small" />
                        <Button label="Add Category" icon="pi pi-plus" @click="openDialog()" />
                    </div>
                </div>

                <Card>
                    <template #content>
                        <Message severity="info" :closable="false" class="mb-4">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle"></i>
                                <span>Categories are organized hierarchically. Click the arrow to expand/collapse subcategories. Total: {{ categories.length }} categories</span>
                            </div>
                        </Message>
                    </template>
                </Card>
            </div>

            <Card>
                <template #content>
                    <TreeTable 
                        :value="treeData" 
                        :paginator="false" 
                        :resizableColumns="true" 
                        class="p-treetable-sm"
                        v-if="treeData.length > 0"
                    >
                        <Column field="name" header="Category Name" :expander="true" style="min-width: 300px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-folder text-blue-600"></i>
                                    <span class="font-medium">{{ slotProps.node.data.name }}</span>
                                    <Tag :value="slotProps.node.data.code" severity="info" class="text-xs font-mono" />
                                </div>
                            </template>
                        </Column>

                        <Column field="description" header="Description" style="min-width: 250px">
                            <template #body="slotProps">
                                <span class="text-gray-600 text-sm">{{ slotProps.node.data.description || 'No description' }}</span>
                            </template>
                        </Column>

                        <Column field="sort_order" header="Order" style="width: 100px">
                            <template #body="slotProps">
                                <span class="text-center block">{{ slotProps.node.data.sort_order }}</span>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status" style="width: 120px">
                            <template #body="slotProps">
                                <Tag 
                                    :value="slotProps.node.data.is_active ? 'Active' : 'Inactive'" 
                                    :severity="slotProps.node.data.is_active ? 'success' : 'warning'" 
                                />
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 180px">
                            <template #body="slotProps">
                                <div class="flex gap-1">
                                    <Button 
                                        icon="pi pi-plus" 
                                        severity="info" 
                                        text 
                                        rounded 
                                        @click="openDialog({ parent_id: slotProps.node.data.id, name: '', description: '', sort_order: 0, is_active: true })" 
                                        v-tooltip.top="'Add Subcategory'" 
                                        size="small"
                                    />
                                    <Button 
                                        icon="pi pi-pencil" 
                                        severity="success" 
                                        text 
                                        rounded 
                                        @click="openDialog(slotProps.node.data)" 
                                        v-tooltip.top="'Edit'" 
                                        size="small"
                                    />
                                    <Button 
                                        icon="pi pi-trash" 
                                        severity="danger" 
                                        text 
                                        rounded 
                                        @click="deleteCategory(slotProps.node.data)" 
                                        v-tooltip.top="'Delete'" 
                                        size="small"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-folder text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500 text-lg">No categories yet</p>
                                <p class="text-gray-400 text-sm mt-2">Create your first category to get started</p>
                                <Button label="Add Category" icon="pi pi-plus" @click="openDialog()" class="mt-4" size="small" />
                            </div>
                        </template>
                    </TreeTable>

                    <div v-else class="text-center py-12">
                        <i class="pi pi-folder text-6xl text-gray-400 mb-4 block"></i>
                        <p class="text-gray-500 text-lg">No categories found</p>
                        <p class="text-gray-400 text-sm mt-2">Click "Add Category" to create your first category</p>
                        <Button label="Add Category" icon="pi pi-plus" @click="openDialog()" class="mt-4" />
                    </div>
                </template>
            </Card>

            <!-- Create/Edit Dialog -->
            <Dialog 
                v-model:visible="showDialog" 
                :header="editingCategory ? 'Edit Category' : 'Add Category'" 
                :style="{width: '500px'}" 
                :modal="true"
                :closable="true"
            >
                <div class="space-y-4 pt-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <InputText 
                            v-model="form.name" 
                            placeholder="Enter category name" 
                            class="w-full" 
                            :class="{ 'p-invalid': form.errors.name }" 
                        />
                        <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Parent Category</label>
                        <Dropdown 
                            v-model="form.parent_id" 
                            :options="getParentOptions()" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Select parent category" 
                            class="w-full" 
                            showClear 
                        />
                        <small class="text-gray-500">Leave empty for root category</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Description</label>
                        <Textarea 
                            v-model="form.description" 
                            rows="3" 
                            placeholder="Enter category description" 
                            class="w-full" 
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Sort Order</label>
                        <InputNumber 
                            v-model="form.sort_order" 
                            placeholder="0" 
                            class="w-full" 
                            :min="0" 
                        />
                        <small class="text-gray-500">Lower numbers appear first</small>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
                        <label for="is_active" class="text-sm font-medium cursor-pointer">Active</label>
                    </div>
                </div>

                <template #footer>
                    <Button label="Cancel" severity="secondary" @click="showDialog = false" text />
                    <Button 
                        :label="editingCategory ? 'Update' : 'Create'" 
                        @click="saveCategory" 
                        :loading="form.processing" 
                    />
                </template>
            </Dialog>
        </div>

        <Toast />
        <ConfirmDialog />
    </TenantLayout>
</template>

<style scoped>
:deep(.p-treetable) {
    font-size: 0.95rem;
}

:deep(.p-treetable .p-treetable-tbody > tr > td) {
    padding: 0.75rem;
}

:deep(.p-treetable-toggler) {
    width: 2rem;
    height: 2rem;
}
</style>