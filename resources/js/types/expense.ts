export interface Wallet {
    id: number;
    name: string;
    type: 'cash' | 'bank' | 'card' | 'crypto' | 'other';
    currency: string;
    balance: number;
    color: string;
    icon: string;
    is_default: boolean;
    is_active: boolean;
    transactions_count?: number;
}

export interface Category {
    id: number;
    name: string;
    type: 'expense' | 'income' | 'both';
    color: string;
    icon: string;
    is_default: boolean;
}

export interface Merchant {
    id: number;
    name: string;
    category_id: number | null;
    logo: string | null;
}

export interface Transaction {
    id: number;
    type: 'expense' | 'income' | 'transfer';
    amount: number;
    currency: string;
    description: string | null;
    notes: string | null;
    location: string | null;
    date: string;
    ai_parsed: boolean;
    is_recurring: boolean;
    wallet_id: number;
    category_id: number | null;
    merchant_id: number | null;
    trip_id: number | null;
    entity_id: number | null;
    created_at?: string;
    wallet?: Wallet;
    category?: Category;
    merchant?: Merchant;
    entity?: Pick<Entity, 'id' | 'name' | 'color' | 'emoji'>;
}

export interface CategoryGroup extends Category {
    transaction_count: number;
    expense_amount: number;
    income_amount: number;
}

export interface MerchantGroup {
    id: number;
    name: string;
    logo: string | null;
    category_id: number | null;
    category?: { id: number; name: string; color: string };
    transaction_count: number;
    expense_amount: number;
    income_amount: number;
}

export type EntityType =
    | 'vehicle'
    | 'office'
    | 'team'
    | 'house'
    | 'children'
    | 'property'
    | 'other';

export interface Entity {
    id: number;
    name: string;
    type: EntityType;
    color: string;
    emoji: string | null;
    description: string | null;
    transactions_count?: number;
    total_spent?: number;
    tx_count?: number;
}

export interface DashboardStats {
    totalExpenses: number;
    totalIncome: number;
    balance: number;
}
