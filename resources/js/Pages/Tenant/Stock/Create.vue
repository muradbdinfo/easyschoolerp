<template>
  <TenantLayout>
    <div class="p-6 max-w-4xl mx-auto">
      <div class="flex items-center gap-3 mb-6">
        <Link :href="route('tenant.stock-issues.index')"><Button icon="pi pi-arrow-left" text /></Link>
        <h1 class="text-2xl font-bold">New Stock Issue Request</h1>
      </div>

      <form @submit.prevent="submit">
        <!-- Basic Info -->
        <Card class="mb-4">
          <template #title>Request Details</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Department *</label>
                <Dropdown v-model="form.department_id" :options="departments"
                  optionLabel="name" optionValue="id" placeholder="Select department"
                  class="w-full" :class="{'p-invalid': errors.department_id}" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Branch *</label>
                <Dropdown v-model="form.branch_id" :options="branches"
                  optionLabel="name" optionValue="id" placeholder="Select branch"
                  class="w-full" :class="{'p-invalid': errors.branch_id}" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Required By</label>
                <Calendar v-model="form.required_by_date" dateFormat="yy-mm-dd" class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Purpose</label>
                <InputText v-model="form.purpose" class="w-full" />
              </div>
            </div>
          </template>
        </Card>

        <!-- Items -->
        <Card class="mb-4">
          <template #title>
            <div class="flex justify-between items-center">
              <span>Items</span>
              <Button label="Add Item" icon="pi pi-plus" size="small" type="button" @click="addLine" />
            </div>
          </template>
          <template #content>
            <!-- Use plain loop instead of DataTable to avoid recursive update bug -->
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b text-left text-gray-600">
                  <th class="py-2 pr-3 font-medium" style="width:40%">Item *</th>
                  <th class="py-2 pr-3 font-medium" style="width:18%">Available</th>
                  <th class="py-2 pr-3 font-medium" style="width:18%">Qty *</th>
                  <th class="py-2 pr-3 font-medium" style="width:15%">Unit</th>
                  <th class="py-2 font-medium" style="width:9%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, index) in form.items" :key="index" class="border-b">
                  <td class="py-2 pr-3">
                    <Dropdown v-model="form.items[index].item_id" :options="items"
                      optionLabel="name" optionValue="id" placeholder="Select item"
                      class="w-full" filter @change="onItemChange(index)" />
                  </td>
                  <td class="py-2 pr-3 text-green-600 font-medium">
                    {{ getStockByItemId(form.items[index].item_id) }}
                  </td>
                  <td class="py-2 pr-3">
                    <input type="number"
                      v-model.number="form.items[index].quantity_requested"
                      :max="getMaxQtyByItemId(form.items[index].item_id)"
                      min="1" step="1"
                      style="width:90px;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;" />
                  </td>
                  <td class="py-2 pr-3 text-gray-600">{{ form.items[index].unit || '—' }}</td>
                  <td class="py-2">
                    <Button icon="pi pi-trash" severity="danger" text size="small"
                      type="button" @click="removeLine(index)" />
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="errors.items" class="text-red-500 text-sm mt-2">{{ errors.items }}</p>
          </template>
        </Card>

        <div class="flex gap-3 justify-end">
          <Link :href="route('tenant.stock-issues.index')">
            <Button label="Cancel" severity="secondary" type="button" />
          </Link>
          <Button label="Submit Request" icon="pi pi-send" type="submit" :loading="form.processing" />
        </div>
      </form>
    </div>
  </TenantLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({ departments: Array, branches: Array, items: Array })

const form = useForm({
  department_id: null, branch_id: null,
  required_by_date: null, purpose: '',
  items: [{ item_id: null, quantity_requested: 1, unit: '' }],
})
const errors = form.errors

const addLine    = () => form.items.push({ item_id: null, quantity_requested: 1, unit: '' })
const removeLine = (i) => form.items.splice(i, 1)

const onItemChange = (i) => {
  const item = props.items.find(it => it.id === form.items[i].item_id)
  if (item) form.items[i].unit = item.unit
}

const getItem              = (i) => props.items.find(it => it.id === form.items[i]?.item_id)
const getItemById          = (id) => props.items.find(it => it.id === id)
const getStockByItemId     = (id) => { const it = getItemById(id); return it ? `${it.current_stock} ${it.unit}` : '—' }
const getMaxQtyByItemId    = (id) => { const it = getItemById(id); return it ? it.current_stock : 9999 }

const submit = () => form.post(route('tenant.stock-issues.store'))
</script>