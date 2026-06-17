export type TaskCategory = {
    public_id: string;
    name: string;
    color: string | null;
    icon: string | null;
};

export type DailyQuestTask = {
    public_id: string;
    name: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    points: number;
    recurrence_type: string;
    recurrence_days: string[] | null;
    recurrence_starts_at: string | null;
    recurrence_ends_at: string | null;
    recurrence_summary: string;
    is_active: boolean;
    deleted_at: string | null;
    category: TaskCategory | null;
};

export type TaskInstance = {
    public_id: string;
    scheduled_date: string;
    completed_at: string | null;
    points_awarded: number | null;
    notes: string | null;
    task: DailyQuestTask | null;
};

export type TodayStats = {
    date: string | null;
    total_tasks: number;
    completed_tasks: number;
    points_earned: number;
    completion_rate: number;
};
