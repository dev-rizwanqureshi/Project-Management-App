export type AppNotification = {
    id: string;
    title: string;
    body: string | null;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

export type NotificationSummary = {
    recent: AppNotification[];
    unread_count: number;
};
