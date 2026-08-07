export type TimestampedModel = {
    id: number;
    created_at: string;
    updated_at: string;
};

export type Company = {
    id: number;
    name: string;
    slug: string;
    email?: string | null;
    phone?: string | null;
    website?: string | null;
    industry?: string | null;
    team_size?: string | null;
    address_line?: string | null;
    city?: string | null;
    state?: string | null;
    country?: string | null;
    postal_code?: string | null;
    timezone?: string | null;
    description?: string | null;
    logo?: string | null;
    trial_ends_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

export type CompanyMembership = {
    id: number;
    company_id: number;
    role: string;
    role_id?: number | null;
    status: string;
    joined_at?: string | null;
    left_at?: string | null;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    role: string;
    role_id?: number | null;
    created_at: string;
    company: Company | null;
    company_membership?: CompanyMembership | null;
    permissions?: string[];
};

export type Workspace = TimestampedModel & {
    name: string;
    slug: string;
    company_id: number;
    created_by: number;
};

export type Board = TimestampedModel & {
    workspace_id: number;
    name: string;
    description?: string | null;
};

export type Card = TimestampedModel & {
    list_id: number;
    title: string;
    description?: string | null;
    position: number;
};

export type ProjectBoardSummary = {
    id: number;
    name: string;
    description: string | null;
    background: string | null;
};

export type ProjectWorkspaceSummary = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    color: string | null;
    boards_count: number;
    boards: ProjectBoardSummary[];
};

export type ProjectContext = {
    company: {
        id: number;
        name: string;
        slug: string;
        logo: string | null;
    };
    workspaces: ProjectWorkspaceSummary[];
};
