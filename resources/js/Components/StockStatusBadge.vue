<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    currentStock: { type: Number, required: true },
    reorderLevel: { type: Number, default: 0 },
    minLevel: { type: Number, default: 0 },
    maxLevel: { type: Number, default: 0 },
});

const status = computed(() => {
    if (props.currentStock <= 0) {
        return { label: 'Out of Stock', severity: 'danger', icon: 'pi-times-circle' };
    } else if (props.currentStock <= props.reorderLevel) {
        return { label: 'Reorder', severity: 'warning', icon: 'pi-exclamation-triangle' };
    } else if (props.currentStock <= props.minLevel) {
        return { label: 'Low Stock', severity: 'warning', icon: 'pi-exclamation-circle' };
    } else if (props.maxLevel > 0 && props.currentStock >= props.maxLevel) {
        return { label: 'Overstock', severity: 'info', icon: 'pi-info-circle' };
    } else {
        return { label: 'In Stock', severity: 'success', icon: 'pi-check-circle' };
    }
});
</script>

<template>
    <Tag :value="status.label" :severity="status.severity">
        <template #default>
            <div class="flex items-center gap-1">
                <i :class="status.icon"></i>
                <span>{{ status.label }}</span>
            </div>
        </template>
    </Tag>
</template>