export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Retro {
    id: number;
    title: string;
    phase: 'draft' | 'collect' | 'vote' | 'discuss' | 'done';
    is_anonymous: boolean;
    votes_per_user: number;
    is_facilitator: boolean;
    invite_code: string | null;
    participants_count: number;
    cards_count: number;
    created_at: string;
}

export interface Card {
    id: number;
    retro_column_id: number;
    body: string;
    votes_count: number;
    voted_by_me?: boolean;
    is_mine: boolean;
    author_name: string | null;
}

export interface BoardColumn {
    id: number;
    title: string;
    color: string;
    cards: Card[];
}

export interface RetroBoard {
    retro: Retro;
    columns: BoardColumn[];
    participants: { id: number; name: string }[];
    action_items: ActionItem[];
}

export interface ActionItem {
    id: number;
    body: string;
    status: 'open' | 'in_progress' | 'done';
    assignee: { id: number; name: string } | null;
}

/** Laravel оборачивает одиночные JSON-ресурсы в { data: ... } */
export interface ApiResource<T> {
    data: T;
}
