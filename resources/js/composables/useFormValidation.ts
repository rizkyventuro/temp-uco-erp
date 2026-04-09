import { ref } from 'vue';

export function useFormValidation() {
    const errors = ref<Record<string, string>>({});

    const setError = (field: string, message: string) => {
        errors.value[field] = message;
    };

    const clearError = (field: string) => {
        delete errors.value[field];
    };

    const clearAllErrors = () => {
        errors.value = {};
    };

    const hasError = (field: string) => {
        return !!errors.value[field];
    };

    const getError = (field: string) => {
        return errors.value[field];
    };

    return {
        errors,
        setError,
        clearError,
        clearAllErrors,
        hasError,
        getError,
    };
}
