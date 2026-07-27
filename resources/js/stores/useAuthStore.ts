import { isAxiosError } from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

import http from '@/lib/axios';
import type { ApiErrorResponse } from '@/lib/axios';
import type { Company, User } from '@/types/models';

export type ValidationErrors = Record<string, string[]>;

export type LoginCredentials = {
    email: string;
    password: string;
    remember: boolean;
};

export type RegisterCompanyPayload = {
    company_name: string;
    company_email: string;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export type AuthActionResult = {
    success: boolean;
    message?: string;
    errors?: ValidationErrors;
};

type ProfileResponse = {
    user: User | null;
};

type RegisterResponse = {
    user: User;
    company: Company;
};

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const isLoading = ref(false);
    const isAuthenticated = computed(() => user.value !== null);

    const setUser = (nextUser: User | null) => {
        user.value = nextUser;
    };

    const apiFailure = (
        error: unknown,
        fallbackMessage: string,
    ): AuthActionResult => {
        if (isAxiosError<ApiErrorResponse>(error)) {
            return {
                success: false,
                message: error.response?.data.message ?? fallbackMessage,
                errors: error.response?.data.errors,
            };
        }

        return {
            success: false,
            message: fallbackMessage,
        };
    };

    const fetchProfile = async (): Promise<User | null> => {
        isLoading.value = true;

        try {
            const response = await http.get<ProfileResponse>(
                route('profile.show'),
            );
            user.value = response.data.user;

            return user.value;
        } catch {
            user.value = null;

            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const login = async (
        credentials: LoginCredentials,
    ): Promise<AuthActionResult> => {
        isLoading.value = true;

        try {
            await http.post(route('login.store'), credentials);
            await fetchProfile();

            return { success: true };
        } catch (error: unknown) {
            user.value = null;

            return apiFailure(error, 'Invalid credentials');
        } finally {
            isLoading.value = false;
        }
    };

    const registerCompany = async (
        payload: RegisterCompanyPayload,
    ): Promise<AuthActionResult> => {
        isLoading.value = true;

        try {
            const response = await http.post<RegisterResponse>(
                route('register.store'),
                payload,
            );

            user.value = response.data.user;
            await fetchProfile();

            return { success: true };
        } catch (error: unknown) {
            return apiFailure(error, 'Registration failed');
        } finally {
            isLoading.value = false;
        }
    };

    const logout = async (): Promise<void> => {
        await http.post(route('logout'));
        user.value = null;
    };

    return {
        user,
        isLoading,
        isAuthenticated,
        setUser,
        fetchProfile,
        login,
        registerCompany,
        logout,
    };
});
