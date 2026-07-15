import axios from 'axios';

// Single shared HTTP client for the SPA. Sanctum's cookie flow requires
// credentials on every request and the XSRF-TOKEN cookie echoed back as a
// header, which axios does automatically with `withXSRFToken`.
export const http = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/**
 * Map an axios error onto a field => messages record suitable for forms.
 * Laravel returns 422 with an `errors` bag; anything else becomes a
 * form-level message under the `form` key.
 */
export function validationErrors(error: unknown): Record<string, string[]> {
    if (axios.isAxiosError(error)) {
        const data = error.response?.data as
            { errors?: Record<string, string[]>; message?: string } | undefined;

        if (error.response?.status === 422 && data?.errors) {
            return data.errors;
        }

        if (data?.message) {
            return { form: [data.message] };
        }
    }

    return { form: ['Request failed. Please try again.'] };
}
