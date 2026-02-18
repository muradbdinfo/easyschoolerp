<template>
    <MarketingLayout>
        <!-- Hero Section -->
        <section class="relative py-20 px-4 bg-gradient-to-br from-primary-50 to-white">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-3xl mx-auto">
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                        Modern School Management
                        <span class="text-primary-600">Made Simple</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8">
                        Complete procurement and asset management system designed specifically for educational institutions.
                    </p>
                    <div class="flex justify-center gap-4">
                        <Button label="Start Free Trial" size="large" />
                        <Button label="Watch Demo" severity="secondary" size="large" outlined />
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="py-20 px-4">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold text-center mb-12">Everything You Need</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <Card v-for="feature in features" :key="feature.title">
                        <template #title>{{ feature.title }}</template>
                        <template #content>
                            <p class="text-gray-600">{{ feature.description }}</p>
                        </template>
                    </Card>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import { ref } from 'vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';

const features = ref([
    {
        title: 'Smart Procurement',
        description: 'Streamline purchase requisitions, orders, and approvals with intelligent workflows.'
    },
    {
        title: 'Asset Management',
        description: 'Track, maintain, and depreciate all school assets with QR code integration.'
    },
    {
        title: 'Multi-Branch Support',
        description: 'Manage multiple campuses from a single, unified platform.'
    }
]);
</script>