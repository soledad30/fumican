import { ref } from 'vue';

/**
 * Maneja errores de validación Laravel (422) debajo de cada input.
 * Espera: { message: "...", errors: { campo: ["mensaje"] } }
 */
export function useFormErrors() {
    const formErrors = ref({});

    function clearErrors() {
        formErrors.value = {};
    }

    function setErrors(errors = {}) {
        formErrors.value = errors ?? {};
    }

    function firstErrorMessage(errors = formErrors.value) {
        const values = Object.values(errors ?? {}).flat().filter(Boolean);
        return values[0] ?? null;
    }

    function fromAxios(error) {
        if (error?.response?.status === 422) {
            setErrors(error.response.data?.errors ?? {});
            return error.response.data?.message ?? firstErrorMessage(error.response.data?.errors);
        }

        return error?.response?.data?.message ?? null;
    }

    function get(field) {
        const error = formErrors.value?.[field];
        if (Array.isArray(error)) {
            return error[0] ?? '';
        }

        return error ?? '';
    }

    function has(field) {
        return Boolean(get(field));
    }

    return {
        formErrors,
        clearErrors,
        setErrors,
        fromAxios,
        firstErrorMessage,
        get,
        has,
    };
}
