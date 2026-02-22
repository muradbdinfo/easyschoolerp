<template>
  <TenantLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Stock Issue Requests</h1>
          <p class="text-sm text-gray-500">Manage item issuance from store to departments</p>
        </div>
        <Link :href="route('tenant.stock-issues.create')">
          <Button label="New Request" icon="pi pi-plus" />
        </Link>
      </div>

      <!-- Filters -->
      <div class="flex gap-3 mb-4">
        <InputText v-model="filters.search" placeholder="Search SIR number..." @keyup.enter="applyFilters" class="w-64" />
        <Dropdown v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value"
          placeholder="All Status" showClear class="w-44" @change="applyFilters" />
        <Button label="Search" icon="pi pi-search" @click="applyFilters" />
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg border">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600">SIR #</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Department</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Requested By</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Items</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sir in sirs.data" :key="sir.id" class="border-b hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-medium">{{ sir.sir_number }}</td>
              <td class="px-4 py-3">{{ sir.department?.name ?? '—' }}</td>
              <td class="px-4 py-3">{{ sir.requester?.name ?? '—' }}</td>
              <td class="px-4 py-3">{{ sir.request_date }}</td>
              <td class="px-4 py-3">
                <Tag :value="sir.status_badge?.label" :severity="sir.status_badge?.severity" />
              </td>
              <td class="px-4 py-3">{{ sir.items?.length ?? '—' }}</td>
              <td class="px-4 py-3">
                <Link :href="route('tenant.stock-issues.show', sir.id)">
                  <Button icon="pi pi-eye" size="small" text />
                </Link>
              </td>
            </tr>
            <tr v-if="!sirs.data?.length">
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">No stock issue requests found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <Paginator :rows="sirs.per_page" :totalRecords="sirs.total"
        @page="onPage" class="mt-4" />
    </div>
  </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({ sirs: Object, departments: Array, filters: Object })

const filters = ref({ search: props.filters?.search ?? '', status: props.filters?.status ?? '' })

const statuses = [
  { label: 'Draft',            value: 'draft' },
  { label: 'Submitted',        value: 'submitted' },
  { label: 'Partially Issued', value: 'partially_issued' },
  { label: 'Issued',           value: 'issued' },
  { label: 'Cancelled',        value: 'cancelled' },
]

const applyFilters = () => router.get(route('tenant.stock-issues.index'), filters.value, { preserveState: true })
const onPage = (e) => router.get(route('tenant.stock-issues.index'), { ...filters.value, page: e.page + 1 })
</script>