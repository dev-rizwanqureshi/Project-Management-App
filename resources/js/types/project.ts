export type ProjectPerson = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
};

export type TicketListSummary = {
    id: number;
    name: string;
};

export type TicketLabel = {
    id: number;
    name: string | null;
    color: string;
};

export type TicketDetail = {
    id: number;
    key: string;
    title: string;
    description: string | null;
    start_date: string | null;
    due_date: string | null;
    is_completed: boolean;
    created_at: string | null;
    updated_at: string | null;
    list: TicketListSummary;
    creator: ProjectPerson | null;
    assignees: ProjectPerson[];
    labels: TicketLabel[];
    checklists: {
        id: number;
        title: string;
        items: {
            id: number;
            title: string;
            is_completed: boolean;
        }[];
    }[];
    attachments: {
        id: number;
        file_name: string;
        file_type: string | null;
        file_size: number | null;
        download_url: string;
        created_at: string | null;
        user: ProjectPerson | null;
    }[];
    comments: {
        id: number;
        body: string;
        created_at: string | null;
        user: ProjectPerson | null;
    }[];
    activity: {
        id: number;
        action: string;
        description: string | null;
        created_at: string | null;
        user: ProjectPerson | null;
    }[];
};

export type TicketBoardContext = {
    id: number;
    name: string;
    workspace: {
        id: number;
        name: string;
    };
    lists: TicketListSummary[];
};
