<template>
  <TenantLayout>
    <div class="p-6">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Stock Reports</h1>
          <p class="text-sm text-gray-500">Stock movements, issue history and inventory levels</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white border rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
          <input type="date" v-model="filters.date_from"
            class="border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
          <input type="date" v-model="filters.date_to"
            class="border rounded-lg px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
          <select v-model="filters.branch_id" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">All Branches</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
          <select v-model="filters.department_id" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">All Departments</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <Button label="Apply" icon="pi pi-search" @click="applyFilters" />
        <Button label="Reset" icon="pi pi-times" severity="secondary" @click="resetFilters" />
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-3 gap-4 mb-6 lg:grid-cols-6">
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-blue-600">{{ summary.total_sirs }}</p>
          <p class="text-xs text-gray-500 mt-1">Total SIRs</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-green-600">{{ summary.issued_sirs }}</p>
          <p class="text-xs text-gray-500 mt-1">Issued</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-orange-500">{{ summary.pending_sirs }}</p>
          <p class="text-xs text-gray-500 mt-1">Pending</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-gray-700">{{ summary.total_items }}</p>
          <p class="text-xs text-gray-500 mt-1">Total Items</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-yellow-600">{{ summary.low_stock_items }}</p>
          <p class="text-xs text-gray-500 mt-1">Low Stock</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
          <p class="text-2xl font-bold text-red-600">{{ summary.out_of_stock }}</p>
          <p class="text-xs text-gray-500 mt-1">Out of Stock</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 mb-4 border-b">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="['px-4 py-2 text-sm font-medium border-b-2 transition',
            activeTab === tab.key
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700']">
          {{ tab.label }}
          <span v-if="tab.badge" class="ml-1 bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded-full">
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- Tab: SIR Register -->
      <div v-if="activeTab === 'sir'">
        <div class="bg-white border rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Stock Issue Register</h2>
            <span class="text-sm text-gray-500">{{ sirRegister.length }} records</span>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-600">SIR #</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Date</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Department</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Branch</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Requested By</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Issued By</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Issued Date</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Items</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sirRegister" :key="s.id" class="border-b hover:bg-gray-50">
                <td class="px-4 py-2 font-mono font-medium text-blue-700">{{ s.sir_number }}</td>
                <td class="px-4 py-2">{{ s.request_date }}</td>
                <td class="px-4 py-2">{{ s.department }}</td>
                <td class="px-4 py-2">{{ s.branch }}</td>
                <td class="px-4 py-2">{{ s.requested_by }}</td>
                <td class="px-4 py-2">{{ s.issued_by ?? '—' }}</td>
                <td class="px-4 py-2">{{ s.issued_date ?? '—' }}</td>
                <td class="px-4 py-2 text-center">{{ s.items_count }}</td>
                <td class="px-4 py-2">
                  <span :class="statusClass(s.status)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ s.status_badge?.label }}
                  </span>
                </td>
              </tr>
              <tr v-if="!sirRegister.length">
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">No records found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Stock Movement (Ledger Summary) -->
      <div v-if="activeTab === 'ledger'">
        <div class="bg-white border rounded-lg overflow-hidden">
          <div class="px-4 py-3 border-b flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Stock Movement Summary</h2>
            <p class="text-xs text-gray-400">IN = received from GRN · OUT = issued to departments</p>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Code</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Item Name</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Unit</th>
                <th class="px-4 py-2 text-right font-medium text-green-600">Total IN ↑</th>
                <th class="px-4 py-2 text-right font-medium text-red-500">Total OUT ↓</th>
                <th class="px-4 py-2 text-right font-medium text-gray-700">Current Stock</th>
                <th class="px-4 py-2 text-left font-medium text-gray-600">Level</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in ledgerSummary" :key="row.id" class="border-b hover:bg-gray-50">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ row.code }}</td>
                <td class="px-4 py-2 font-medium">{{ row.name }}</td>
                <td class="px-4 py-2 text-gray-500">{{ row.unit }}</td>
                <td class="px-4 py-2 text-right text-green-600 font-medium">+{{ row.total_in }}</td>
                <td class="px-4 py-2 text-right text-red-500 font-medium">-{{ row.total_out }}</td>
                <td class="px-4 py-2 text-right font-bold">{{ row.current_stock }}</td>
                <td class="px-4 py-2">
                  <span v-if="row.current_stock <= 0"
                    class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Out of Stock</span>
                  <span v-else-if="row.current_stock <= row.reorder_level"
                    class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">Low</span>
                  <span v-else class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">OK</span>
                </td>
              </tr>
              <tr v-if="!ledgerSummary.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No movements in selected period</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Low Stock Alerts -->
      <div v-if="activeTab === 'alerts'">
        <div class="grid grid-cols-2 gap-4">
          <!-- Out of Stock -->
          <div class="bg-white border border-red-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-red-50">
              <h2 class="font-semibold text-red-800">🚨 Out of Stock ({{ outOfStock.length }})</h2>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Item</th>
                  <th class="px-3 py-2 text-right font-medium text-gray-600">Stock</th>
                  <th class="px-3 py-2 text-right font-medium text-gray-600">Reorder At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in outOfStock" :key="item.id" class="border-b">
                  <td class="px-3 py-2 font-medium">{{ item.name }}</td>
                  <td class="px-3 py-2 text-right font-bold text-red-600">0 {{ item.unit }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ item.reorder_level }}</td>
                </tr>
                <tr v-if="!outOfStock.length">
                  <td colspan="3" class="px-3 py-6 text-center text-green-600 font-medium">✓ All items in stock</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Low Stock -->
          <div class="bg-white border border-yellow-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-yellow-50">
              <h2 class="font-semibold text-yellow-800">⚠ Low Stock ({{ lowStock.length }})</h2>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-600">Item</th>
                  <th class="px-3 py-2 text-right font-medium text-gray-600">Stock</th>
                  <th class="px-3 py-2 text-right font-medium text-gray-600">Reorder At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in lowStock" :key="item.id" class="border-b">
                  <td class="px-3 py-2 font-medium">{{ item.name }}</td>
                  <td class="px-3 py-2 text-right font-bold text-yellow-600">
                    {{ item.current_stock }} {{ item.unit }}
                  </td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ item.reorder_level }}</td>
                </tr>
                <tr v-if="!lowStock.length">
                  <td colspan="3" class="px-3 py-6 text-center text-green-600 font-medium">✓ No low stock items</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab: Department Wise -->
      <div v-if="activeTab === 'dept'">
        <div class="bg-white border rounded-lg p-4">
          <h2 class="font-semibold text-gray-800 mb-4">Department-wise Issue Summary</h2>
          <div v-if="deptWiseIssues.length" class="space-y-3">
            <div v-for="d in deptWiseIssues" :key="d.department" class="flex items-center gap-3">
              <span class="w-44 text-sm text-gray-700 truncate">{{ d.department }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-5 relative overflow-hidden">
                <div class="bg-blue-500 h-full rounded-full transition-all"
                  :style="{ width: (d.sir_count / maxDeptCount * 100) + '%' }"></div>
              </div>
              <span class="text-sm font-bold w-8 text-right">{{ d.sir_count }}</span>
            </div>
          </div>
          <p v-else class="text-center text-gray-400 py-8">No data for selected period</p>
        </div>
      </div>

    </div>
  </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
  sirRegister: Array, ledgerSummary: Array, deptWiseIssues: Array,
  monthlyTrend: Array, lowStock: Array, outOfStock: Array, summary: Object,
  branches: Array, departments: Array,
  dateFrom: String, dateTo: String, branchId: [String, Number], departmentId: [String, Number],
})

const activeTab = ref('sir')

const tabs = computed(() => [
  { key: 'sir',    label: 'SIR Register' },
  { key: 'ledger', label: 'Stock Movement' },
  { key: 'alerts', label: 'Stock Alerts', badge: (props.summary.out_of_stock + props.summary.low_stock_items) || null },
  { key: 'dept',   label: 'By Department' },
])

const filters = ref({
  date_from:     props.dateFrom,
  date_to:       props.dateTo,
  branch_id:     props.branchId ?? '',
  department_id: props.departmentId ?? '',
})

const maxDeptCount = computed(() =>
  Math.max(...(props.deptWiseIssues?.map(d => d.sir_count) ?? [1]))
)

const applyFilters = () =>
  router.get(route('tenant.reports.stock'), filters.value, { preserveState: true })

const resetFilters = () => {
  filters.value = { date_from: '', date_to: '', branch_id: '', department_id: '' }
  applyFilters()
}

const statusClass = (status) => ({
  'submitted':        'bg-yellow-100 text-yellow-700',
  'partially_issued': 'bg-blue-100 text-blue-700',
  'issued':           'bg-green-100 text-green-700',
  'cancelled':        'bg-red-100 text-red-700',
  'draft':            'bg-gray-100 text-gray-600',
}[status] ?? 'bg-gray-100 text-gray-600')
</script>