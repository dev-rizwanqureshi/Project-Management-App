export type TimestampedModel = {
    id: number;
    created_at: string;
    updated_at: string;
};

export type Workspace = TimestampedModel & {
    name: string;
    slug: string;
    owner_id: number;
};

export type Board = TimestampedModel & {
    workspace_id: number;
    name: string;
    description?: string | null;
};

export type Card = TimestampedModel & {
    board_id: number;
    title: string;
    description?: string | null;
    position: number;
};
