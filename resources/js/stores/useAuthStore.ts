import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import type { User } from '@/types/auth';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const isAuthenticated = computed(() => user.value !== null);

    const setUser = (nextUser: User | null) => {
        user.value = nextUser;
    };

    const fetchUser = async () => user.value;

    const logout = async () => {
        user.value = null;
    };

    return {
        user,
        isAuthenticated,
        setUser,
        fetchUser,
        logout,
    };
});
