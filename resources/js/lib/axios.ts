import { router } from '@inertiajs/vue3';
import axios from 'axios';
import type { AxiosError, AxiosRequestConfig } from 'axios';
import { route } from 'ziggy-js';

type RouteName = Parameters<typeof route>[0];
type RouteParams = Parameters<typeof route>[1];

export type ApiErrorResponse = {
    message?: string;
    errors?: Record<string, string[]>;
};

let sessionExpiredHandler: (() => void) | null = null;

export const setSessionExpiredHandler = (handler: () => void) => {
    sessionExpiredHandler = handler;
};

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

const http = axios.create({
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

http.interceptors.response.use(
    (response) => response,
    (error: AxiosError<ApiErrorResponse>) => {
        const status = error.response?.status;

        if (status === 401 || status === 419) {
            sessionExpiredHandler?.();

            if (window.location.pathname !== route('login', undefined, false)) {
                router.visit(route('login'));
            }
        }

        return Promise.reject(error);
    },
);

export const routeUrl = (
    name: RouteName,
    params?: RouteParams,
    absolute = false,
) => route(name, params, absolute);

export const getRoute = <TResponse>(
    name: RouteName,
    params?: RouteParams,
    config?: AxiosRequestConfig,
) => http.get<TResponse>(routeUrl(name, params), config);

export default http;
