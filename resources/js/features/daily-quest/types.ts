export type TaskCategory = {
    id: string;
    name: string;
    color: string | null;
    icon: string | null;
    tasks_count?: number;
    created_at?: string | null;
    updated_at?: string | null;
};

export type RecurrenceType =
    | 'daily'
    | 'specific_days'
    | 'one_time'
    | 'x_days'
    | 'date_range';

export type TaskStatusTab = 'active' | 'paused' | 'archived';

export type DailyQuestTask = {
    id: string;
    name: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    points: number;
    recurrence_type: RecurrenceType;
    recurrence_days: string[] | null;
    recurrence_starts_at: string | null;
    recurrence_ends_at: string | null;
    recurrence_summary: string;
    is_active: boolean;
    deleted_at: string | null;
    category: TaskCategory | null;
    created_at?: string | null;
    updated_at?: string | null;
};

export type TaskInstance = {
    id: string;
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

export type HistoryDaySummary = {
    date: string | null;
    total_tasks: number;
    completed_tasks: number;
    points_earned: number;
    completion_rate: number;
};

export type HistoryDay = {
    summary: HistoryDaySummary;
    instances: TaskInstance[];
};

export type HistoryFilters = {
    task_id?: string;
    category_id?: string;
    from?: string;
    to?: string;
};

export type DashboardStats = {
    streak: {
        current: number;
        longest: number;
    };
    points: {
        total: number;
        today?: number;
    };
    today?: {
        date: string;
        total_tasks: number;
        completed_tasks: number;
    };
    weekly_completion_rate: number;
    weekly_chart: Array<{
        date: string;
        total_tasks: number;
        completed_tasks: number;
        points_earned: number;
    }>;
};

export type ProfileStats = {
    streak: {
        current: number;
        longest: number;
    };
    points: {
        total: number;
    };
    tasks: {
        completed: number;
        active: number;
    };
    categories: {
        total: number;
    };
    weekly_completion_rate: number;
};
