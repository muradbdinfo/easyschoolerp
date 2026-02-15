<template>
    <div class="min-h-screen bg-white">
        <!-- Navbar -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="text-2xl font-bold text-primary-600">
                            School ERP
                        </Link>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-8">
                        <Link href="/features" class="text-gray-600 hover:text-gray-900">Features</Link>
                        <Link href="/pricing" class="text-gray-600 hover:text-gray-900">Pricing</Link>
                        <Link href="/about" class="text-gray-600 hover:text-gray-900">About</Link>
                        <Link href="/contact" class="text-gray-600 hover:text-gray-900">Contact</Link>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <Link href="/login" class="text-gray-600 hover:text-gray-900">Login</Link>
                        <Button label="Start Free Trial" severity="primary" />
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="pt-16">
            <slot />
        </main>
        
        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">School ERP</h3>
                        <p class="text-gray-400">Modern school management made simple</p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Product</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/features">Features</a></li>
                            <li><a href="/pricing">Pricing</a></li>
                            <li><a href="/demo">Demo</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Company</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/about">About</a></li>
                            <li><a href="/contact">Contact</a></li>
                            <li><a href="/support">Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/privacy">Privacy</a></li>
                            <li><a href="/terms">Terms</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                    <p>&copy; 2024 School ERP. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
</script>