import { ref } from 'vue'

const notifications = ref([])
let notificationId = 0

export function useNotification() {
    const show = (message, type = 'success', duration = 3000) => {
        const id = notificationId++
        const notification = {
            id,
            message,
            type, // success, error, info, warning
        }

        notifications.value.push(notification)

        if (duration > 0) {
            setTimeout(() => {
                remove(id)
            }, duration)
        }

        return id
    }

    const remove = (id) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    const success = (message, duration = 3000) => show(message, 'success', duration)
    const error = (message, duration = 5000) => show(message, 'error', duration)
    const info = (message, duration = 3000) => show(message, 'info', duration)
    const warning = (message, duration = 4000) => show(message, 'warning', duration)

    return {
        notifications,
        show,
        remove,
        success,
        error,
        info,
        warning,
    }
}
