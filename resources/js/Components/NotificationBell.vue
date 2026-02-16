<template>
    <div class="relative">
        <!-- Bell Icon with Badge -->
        <button 
            @click="togglePanel" 
            class="relative p-2 text-gray-600 hover:text-gray-900 rounded-full hover:bg-gray-100 transition"
        >
            <Bell :size="20" />
            <Badge 
                v-if="unreadCount > 0" 
                :value="unreadCount" 
                severity="danger" 
                class="absolute -top-1 -right-1"
            />
        </button>

        <!-- Notification Panel -->
        <OverlayPanel 
            ref="notificationPanel" 
            :style="{ width: '400px', maxHeight: '600px' }"
            @show="loadNotifications"
        >
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex justify-between items-center pb-3 border-b mb-3">
                    <h3 class="text-lg font-semibold">Notifications</h3>
                    <Button 
                        v-if="unreadCount > 0"
                        label="Mark all read" 
                        text 
                        size="small"
                        @click="markAllAsRead"
                    />
                </div>

                <!-- Notifications List -->
                <div v-if="loading" class="flex justify-center py-8">
                    <ProgressSpinner style="width: 50px; height: 50px" />
                </div>

                <div v-else-if="notifications.length === 0" class="text-center py-8 text-gray-500">
                    <Bell :size="48" class="mx-auto mb-3 text-gray-300" />
                    <p>No notifications</p>
                </div>

                <div v-else class="space-y-2 overflow-y-auto" style="max-height: 400px">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer transition',
                            notification.read_at ? 'bg-white hover:bg-gray-50' : 'bg-blue-50 hover:bg-blue-100'
                        ]"
                    >
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <component 
                                    :is="getNotificationIcon(notification.type)" 
                                    :size="20" 
                                    :class="getNotificationColor(notification.type)"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['text-sm font-medium', notification.read_at ? 'text-gray-900' : 'text-gray-900 font-semibold']">
                                    {{ notification.title }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                    {{ notification.message }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ formatTimeAgo(notification.created_at) }}
                                </p>
                            </div>
                            <div v-if="!notification.read_at" class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t mt-3">
                    <Link 
                        :href="route('notifications.index')" 
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium block text-center"
                    >
                        View All Notifications
                    </Link>
                </div>
            </div>
        </OverlayPanel>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useNotificationStore } from '@/Stores/notificationStore';
import OverlayPanel from 'primevue/overlaypanel';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import { Bell, CheckCircle, XCircle, AlertCircle, Info, Package, Box } from 'lucide-vue-next';
import axios from 'axios';

const notificationPanel = ref();
const notificationStore = useNotificationStore();
const loading = ref(false);

const notifications = computed(() => notificationStore.notifications);
const unreadCount = computed(() => notificationStore.unreadCount);

const togglePanel = (event) => {
    notificationPanel.value.toggle(event);
};

const loadNotifications = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('notifications.unread'));
        notificationStore.setNotifications(response.data.notifications);
        notificationStore.setUnreadCount(response.data.unread_count);
    } catch (error) {
        console.error('Failed to load notifications:', error);
    } finally {
        loading.value = false;
    }
};

const handleNotificationClick = async (notification) => {
    // Mark as read if unread
    if (!notification.read_at) {
        await axios.post(route('notifications.read', notification.id));
        notificationStore.markAsRead(notification.id);
    }
    
    // Navigate to action URL if exists
    if (notification.action_url) {
        router.visit(notification.action_url);
    }
    
    notificationPanel.value.hide();
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.mark-all-read'));
        notificationStore.markAllAsRead();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
};

const getNotificationIcon = (type) => {
    const icons = {
        approval_request: AlertCircle,
        approval_completed: CheckCircle,
        approval_rejected: XCircle,
        asset_alert: Box,
        po_created: Package,
        system_info: Info,
    };
    return icons[type] || Bell;
};

const getNotificationColor = (type) => {
    const colors = {
        approval_request: 'text-orange-500',
        approval_completed: 'text-green-500',
        approval_rejected: 'text-red-500',
        asset_alert: 'text-blue-500',
        po_created: 'text-purple-500',
        system_info: 'text-gray-500',
    };
    return colors[type] || 'text-gray-500';
};

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`;
    
    return date.toLocaleDateString();
};
</script>