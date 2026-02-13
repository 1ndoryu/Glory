/*
 * Tipos para el Page Builder basado en @measured/puck
 */

export interface BlockDefinition {
    type: string;
    label: string;
    icon?: string;
    category?: string;
    defaultProps: Record<string, unknown>;
    fields: BlockField[];
}

export interface BlockField {
    name: string;
    label: string;
    type: 'text' | 'textarea' | 'number' | 'select' | 'toggle' | 'image' | 'color' | 'rich-text';
    required?: boolean;
    defaultValue?: unknown;
    options?: { label: string; value: string }[];
    placeholder?: string;
}

export interface BlockInstance {
    id: string;
    type: string;
    props: Record<string, unknown>;
}

export interface PageLayout {
    id: string;
    name: string;
    blocks: BlockInstance[];
    meta?: Record<string, unknown>;
}
