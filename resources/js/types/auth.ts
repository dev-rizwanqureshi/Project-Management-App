import type { User as ModelUser } from './models';

export type User = {
    [Property in keyof ModelUser]: ModelUser[Property];
} & {
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    permissions: string[];
};

export type Admin = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'support_staff' | string;
    admin_role_id?: number | null;
    created_at?: string | null;
    updated_at?: string | null;
    [key: string]: unknown;
};

export type AdminAuth = {
    admin: Admin | null;
    permissions: string[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
