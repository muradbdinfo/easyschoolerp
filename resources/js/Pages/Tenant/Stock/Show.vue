<template>
  <TenantLayout>
    <div class="p-6 max-w-4xl mx-auto">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.stock-issues.index')">
            <Button icon="pi pi-arrow-left" text />
          </Link>
          <div>
            <h1 class="text-2xl font-bold">{{ sir.sir_number }}</h1>
            <p class="text-sm text-gray-500">{{ sir.department?.name }} · {{ sir.branch?.name }} · {{ sir.request_date }}</p>
          </div>
        </div>
        <Tag :value="sir.status_badge?.label" :severity="sir.status_badge?.severity" class="text-base px-3 py-1" />
      </div>

      <!-- Flash success with assets created -->
      <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-green-800 font-medium">{{ $page.props.flash.success }}</p>
      </div>

      <!-- Details -->
      <Card class="mb-4">
        <template #content>
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div><p class="text-gray-500">Requested By</p><p class="font-medium">{{ sir.requester?.name }}</p></div>
            <div><p class="text-gray-500">Required By</p><p class="font-medium">{{ sir.required_by_date ?? '—' }}</p></div>
            <div><p class="text-gray-500">Purpose</p><p class="font-medium">{{ sir.purpose ?? '—' }}</p></div>
            <div v-if="sir.issuer"><p class="text-gray-500">Issued By</p><p class="font-medium">{{ sir.issuer.name }}</p></div>
            <div v-if="sir.issued_date"><p class="text-gray-500">Issued Date</p><p class="font-medium">{{ sir.issued_date }}</p></div>
            <div v-if="sir.notes"><p class="text-gray-500">Notes</p><p class="font-medium">{{ sir.notes }}</p></div>
          </div>
        </template>
      </Card>

      <!-- Items Table -->
      <Card class="mb-4">
        <template #title>Items</template>
        <template #content>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Item</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Unit</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Requested</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Issued</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">In Stock</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Is Asset?</th>
                <th v-if="canIssue" class="px-3 py-2 text-left font-medium text-gray-600">Issue Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in sir.items" :key="line.id" class="border-b">
                <td class="px-3 py-2 font-medium">{{ line.item?.name }}</td>
                <td class="px-3 py-2 text-gray-500">{{ line.unit }}</td>
                <td class="px-3 py-2">{{ line.quantity_requested }}</td>
                <td class="px-3 py-2">
                  <span :class="line.quantity_issued > 0 ? 'text-green-600 font-medium' : 'text-gray-400'">
                    {{ line.quantity_issued }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <span :class="line.item?.current_stock < line.quantity_requested ? 'text-red-500 font-medium' : 'text-gray-700'">
                    {{ line.item?.current_stock }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <span v-if="line.item?.is_asset" class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">
                    Asset ⚡
                  </span>
                  <span v-else class="text-xs text-gray-400">Consumable</span>
                </td>
                <td v-if="canIssue" class="px-3 py-2">
                  <input
                    v-if="line.quantity_issued < line.quantity_requested"
                    type="number"
                    v-model.number="issueQtys[line.id]"
                    :max="Math.min(line.quantity_requested - line.quantity_issued, line.item?.current_stock ?? 0)"
                    min="0" step="1"
                    style="width:80px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;"
                  />
                  <span v-else class="text-green-600 text-xs font-medium">✓ Done</span>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </Card>

      <!-- Issue Action (storekeeper) -->
      <Card v-if="canIssue" class="mb-4 border-blue-200 bg-blue-50">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-blue-900">Process Stock Issue</p>
              <p class="text-sm text-blue-700 mt-1">
                Set quantities to issue for each item and click Process.
                <span class="font-medium">Items marked ⚡ Asset will auto-create asset records.</span>
              </p>
            </div>
            <Button label="Process Issue" icon="pi pi-check" @click="processIssue"
              :loading="issuing" severity="success" />
          </div>
        </template>
      </Card>

      <!-- Auto-created Assets (shown after issue) -->
      <Card v-if="$page.props.flash?.created_assets?.length" class="mb-4 border-orange-200">
        <template #title>
          <span class="text-orange-700">⚡ Assets Auto-Created</span>
        </template>
        <template #content>
          <p class="text-sm text-gray-600 mb-3">
            The following asset records were created automatically from this stock issue:
          </p>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-3 py-2 text-left font-medium">Asset Tag</th>
                <th class="px-3 py-2 text-left font-medium">Name</th>
                <th class="px-3 py-2 text-left font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="asset in $page.props.flash.created_assets" :key="asset.id" class="border-b">
                <td class="px-3 py-2 font-mono font-medium text-orange-700">{{ asset.asset_tag }}</td>
                <td class="px-3 py-2">{{ asset.name }}</td>
                <td class="px-3 py-2">
                  <Link :href="route('tenant.assets.show', asset.id)">
                    <Button label="View Asset" size="small" text icon="pi pi-arrow-right" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </Card>

      <!-- Cancel -->
      <div v-if="sir.status === 'submitted'" class="flex justify-end">
        <Button label="Cancel Request" severity="danger" outlined @click="cancelRequest" />
      </div>

    </div>
  </TenantLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({ sir: Object, canIssue: Boolean })
const issuing = ref(false)

// Pre-fill issue qty = remaining needed (capped by stock)
const issueQtys = reactive(
  Object.fromEntries(
    props.sir.items.map(l => [
      l.id,
      Math.min(
        l.quantity_requested - l.quantity_issued,
        l.item?.current_stock ?? 0
      )
    ])
  )
)

const processIssue = () => {
  issuing.value = true
  router.post(route('tenant.stock-issues.issue', props.sir.id),
    { quantities: issueQtys },
    {
      onFinish: () => issuing.value = false,
    }
  )
}

const cancelRequest = () => {
  if (confirm('Cancel this stock issue request?')) {
    router.post(route('tenant.stock-issues.cancel', props.sir.id))
  }
}
</script>