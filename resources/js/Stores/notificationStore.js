import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
    console.log('🔔 Notification Store: Initialized');
    
    const notifications = ref([]);
    const unreadCount = ref(0);

    function setNotifications(data) {
        console.log('🔔 Store: Setting notifications:', data);
        notifications.value = data;
    }

    function setUnreadCount(count) {
        console.log('🔔 Store: Setting unread count:', count);
        unreadCount.value = count;
    }

    function markAsRead(notificationId) {
        console.log('🔔 Store: Marking notification as read:', notificationId);
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        console.log('🔔 Store: New unread count:', unreadCount.value);
    }

    function markAllAsRead() {
        console.log('🔔 Store: Marking all notifications as read');
        notifications.value.forEach(n => {
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
    }

    return {
        notifications,
        unreadCount,
        setNotifications,
        setUnreadCount,
        markAsRead,
        markAllAsRead,
    };
});