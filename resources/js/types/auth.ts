export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    permissions?: string[];
    role?: string | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: string[];
    role?: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
