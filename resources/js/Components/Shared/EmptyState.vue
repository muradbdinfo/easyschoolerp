<template>
    <div class="text-center py-12">
        <div class="inline-block p-6 bg-gray-100 rounded-full mb-4">
            <component :is="icon" :size="48" class="text-gray-400" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ title }}</h3>
        <p class="text-gray-600 mb-6">{{ message }}</p>
        <Button v-if="actionLabel" :label="actionLabel" @click="$emit('action')" />
    </div>
</template>

<script setup>
import Button from 'primevue/button';

defineProps({
    icon: Object,
    title: String,
    message: String,
    actionLabel: String,
});

defineEmits(['action']);
</script>