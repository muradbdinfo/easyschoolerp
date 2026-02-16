import { useToast as usePrimeToast } from 'primevue/usetoast';

export function useToast() {
    const toast = usePrimeToast();

    const success = (message, title = 'Success') => {
        toast.add({
            severity: 'success',
            summary: title,
            detail: message,
            life: 5000,
        });
    };

    const error = (message, title = 'Error') => {
        toast.add({
            severity: 'error',
            summary: title,
            detail: message,
            life: 5000,
        });
    };

    const warning = (message, title = 'Warning') => {
        toast.add({
            severity: 'warn',
            summary: title,
            detail: message,
            life: 5000,
        });
    };

    const info = (message, title = 'Info') => {
        toast.add({
            severity: 'info',
            summary: title,
            detail: message,
            life: 5000,
        });
    };

    return {
        success,
        error,
        warning,
        info,
    };
}