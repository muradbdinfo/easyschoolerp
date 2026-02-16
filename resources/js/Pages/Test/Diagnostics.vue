<template>
    <TenantLayout school-name="Diagnostics" :breadcrumb-items="breadcrumbs">
        <div class="space-y-6">
            <Card>
                <template #title>System Diagnostics</template>
                <template #content>
                    <div class="space-y-4">
                        <Button label="Test Success Toast" @click="testSuccess" />
                        <Button label="Test Error Toast" severity="danger" @click="testError" />
                        <Button label="Test Warning Toast" severity="warning" @click="testWarning" />
                        <Button label="Test Info Toast" severity="info" @click="testInfo" />
                        <Button label="Log User Data" severity="secondary" @click="logUserData" />
                        <Button label="Log Page Props" severity="secondary" @click="logPageProps" />
                        <Button label="Test API Call" severity="help" @click="testApiCall" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Console Output</template>
                <template #content>
                    <p class="text-sm text-gray-600">
                        Check browser console (F12) to see test results
                    </p>
                    <div class="mt-4 bg-gray-100 p-4 rounded font-mono text-sm">
                        <div>Last test: {{ lastTest }}</div>
                        <div>Test count: {{ testCount }}</div>
                    </div>
                </template>
            </Card>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import axios from 'axios';

console.log('🧪 Diagnostics Page: Loaded');

const page = usePage();
const toast = useToast();
const lastTest = ref('None');
const testCount = ref(0);

const breadcrumbs = ref([
    { label: 'Home', route: '/dashboard' },
    { label: 'Diagnostics' }
]);

const testSuccess = () => {
    console.log('🧪 Test: Success Toast');
    lastTest.value = 'Success Toast';
    testCount.value++;
    toast.success('Success toast is working! ✅');
};

const testError = () => {
    console.log('🧪 Test: Error Toast');
    lastTest.value = 'Error Toast';
    testCount.value++;
    toast.error('Error toast is working! ❌');
};

const testWarning = () => {
    console.log('🧪 Test: Warning Toast');
    lastTest.value = 'Warning Toast';
    testCount.value++;
    toast.warning('Warning toast is working! ⚠️');
};

const testInfo = () => {
    console.log('🧪 Test: Info Toast');
    lastTest.value = 'Info Toast';
    testCount.value++;
    toast.info('Info toast is working! ℹ️');
};

const logUserData = () => {
    console.log('🧪 Test: User Data');
    console.log('👤 User:', page.props.auth?.user);
    lastTest.value = 'User Data (check console)';
    testCount.value++;
    toast.info('User data logged to console');
};

const logPageProps = () => {
    console.log('🧪 Test: Page Props');
    console.log('📄 Props:', page.props);
    lastTest.value = 'Page Props (check console)';
    testCount.value++;
    toast.info('Page props logged to console');
};

const testApiCall = async () => {
    console.log('🧪 Test: API Call');
    lastTest.value = 'API Call (check console)';
    testCount.value++;
    
    try {
        console.log('🌐 Calling API: /notifications/unread');
        const response = await axios.get('/notifications/unread');
        console.log('✅ API Response:', response.data);
        toast.success('API call successful! Check console for details.');
    } catch (error) {
        console.error('❌ API Error:', error);
        toast.error('API call failed! Check console for details.');
    }
};
</script>