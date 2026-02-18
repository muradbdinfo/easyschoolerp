<template>
    <div class="min-h-screen bg-gradient-to-br from-primary-50 to-white flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <Card class="w-full max-w-md">
            <template #title>
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900">School ERP</h2>
                    <p class="text-gray-600 mt-2">Sign in to your account</p>
                </div>
            </template>
            <template #content>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <InputText 
                            v-model="form.email"
                            type="email"
                            placeholder="john@example.com"
                            class="w-full"
                            :invalid="!!form.errors.email"
                        />
                        <small class="text-red-500" v-if="form.errors.email">
                            {{ form.errors.email }}
                        </small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <Password 
                            v-model="form.password"
                            placeholder="Enter password"
                            :feedback="false"
                            toggleMask
                            class="w-full"
                            :invalid="!!form.errors.password"
                        />
                        <small class="text-red-500" v-if="form.errors.password">
                            {{ form.errors.password }}
                        </small>
                    </div>

                    <Button 
                        type="submit"
                        label="Sign In" 
                        class="w-full"
                        :loading="form.processing"
                    />
                </form>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login.post'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>