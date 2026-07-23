import axios from 'axios';
import type { AxiosError, AxiosRequestConfig } from 'axios';
import { route } from 'ziggy-js';

type RouteName = Parameters<typeof route>[0];
type RouteParams = Parameters<typeof route>[1];

export type ApiErrorResponse = {
    message?: string;
    errors?: Record<string, string[]>;
};

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
    (error: AxiosError<ApiErrorResponse>) => Promise.reject(error),
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
