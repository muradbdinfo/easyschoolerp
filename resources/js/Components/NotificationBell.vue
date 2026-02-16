<template>
    <div class="relative">
        <!-- Bell Icon with Badge -->
        <button 
            @click="togglePanel" 
            class="relative p-2 text-gray-600 hover:text-gray-900 rounded-full hover:bg-gray-100 transition"
            aria-label="Notifications"
        >
            <Bell :size="20" />
            <span 
                v-if="unreadCount > 0" 
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
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
                    <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                    <Button 
                        v-if="unreadCount > 0"
                        label="Mark all read" 
                        text 
                        size="small"
                        @click="markAllAsRead"
                    />
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center py-8">
                    <ProgressSpinner style="width: 50px; height: 50px" />
                </div>

                <!-- Empty State -->
                <div v-else-if="notifications.length === 0" class="text-center py-12 text-gray-500">
                    <Bell :size="48" class="mx-auto mb-3 text-gray-300" />
                    <p class="font-medium">No notifications</p>
                    <p class="text-sm mt-1">You're all caught up!</p>
                </div>

                <!-- Notifications List -->
                <div v-else class="space-y-2 overflow-y-auto pr-2" style="max-height: 400px">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        :class="[
                            'p-3 rounded-lg cursor-pointer transition-all border',
                            notification.read_at 
                                ? 'bg-white hover:bg-gray-50 border-gray-100' 
                                : 'bg-blue-50 hover:bg-blue-100 border-blue-200'
                        ]"
                    >
                        <div class="flex gap-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <component 
                                    :is="getNotificationIcon(notification.type)" 
                                    :size="20" 
                                    :class="getNotificationColor(notification.type)"
                                />
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p :class="[
                                    'text-sm font-medium',
                                    notification.read_at ? 'text-gray-900' : 'text-gray-900 font-semibold'
                                ]">
                                    {{ notification.title }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                    {{ notification.message }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                    <Clock :size="12" />
                                    {{ formatTimeAgo(notification.created_at) }}
                                </p>
                            </div>
                            
                            <!-- Unread Indicator -->
                            <div v-if="!notification.read_at" class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t mt-3">
                    <Link 
                        :href="route('notifications.index')" 
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium block text-center py-2 hover:bg-gray-50 rounded transition"
                        @click="notificationPanel.hide()"
                    >
                        View All Notifications
                    </Link>
                </div>
            </div>
        </OverlayPanel>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import OverlayPanel from 'primevue/overlaypanel';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import { 
    Bell, 
    CheckCircle, 
    XCircle, 
    AlertCircle, 
    Info, 
    Package, 
    Box,
    Clock
} from 'lucide-vue-next';
import axios from 'axios';

const page = usePage();
const toast = useToast();
const notificationPanel = ref();
const loading = ref(false);
const notifications = ref([]);
let refreshInterval = null;

// Get unread count from shared Inertia data
const unreadCount = computed(() => page.props.auth?.user?.unread_notification_count || 0);

const togglePanel = (event) => {
    notificationPanel.value.toggle(event);
};

const loadNotifications = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('notifications.unread'));
        notifications.value = response.data.notifications;
        console.log('✅ Notifications loaded:', response.data.notifications.length);
    } catch (error) {
        console.error('❌ Failed to load notifications:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load notifications',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const handleNotificationClick = async (notification) => {
    console.log('🔔 Notification clicked:', notification.title);
    
    // Mark as read if unread
    if (!notification.read_at) {
        try {
            await axios.post(route('notifications.read', notification.id));
            notification.read_at = new Date().toISOString();
            
            // Reload to update unread count
            router.reload({ only: ['auth'] });
            console.log('✅ Notification marked as read');
        } catch (error) {
            console.error('❌ Failed to mark as read:', error);
        }
    }
    
    // Navigate if has action URL
    if (notification.action_url) {
        notificationPanel.value.hide();
        router.visit(notification.action_url);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.mark-all-read'));
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
        
        // Reload to update unread count
        router.reload({ only: ['auth'] });
        
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'All notifications marked as read',
            life: 3000
        });
        
        console.log('✅ All notifications marked as read');
    } catch (error) {
        console.error('❌ Failed to mark all as read:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to mark notifications as read',
            life: 3000
        });
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
    if (seconds < 3600) {
        const mins = Math.floor(seconds / 60);
        return `${mins} ${mins === 1 ? 'minute' : 'minutes'} ago`;
    }
    if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    }
    if (seconds < 604800) {
        const days = Math.floor(seconds / 86400);
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    }
    
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    });
};

// Auto-refresh notifications every 30 seconds
onMounted(() => {
    console.log('🔔 NotificationBell mounted');
    console.log('📊 Initial unread count:', unreadCount.value);
    
    refreshInterval = setInterval(() => {
        // Only refresh if panel is open
        if (notificationPanel.value?.visible) {
            loadNotifications();
        }
        
        // Refresh unread count (without opening panel)
        router.reload({ only: ['auth'], preserveScroll: true });
    }, 30000); // 30 seconds
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>