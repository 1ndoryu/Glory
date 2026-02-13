/*
 * Tipos base de WordPress para uso en React.
 * Mapean las estructuras de datos que WP expone via REST API y PHP bridge.
 */

export interface WPMedia {
    id: number;
    url: string;
    alt: string;
    title?: string;
    caption?: string;
    width?: number;
    height?: number;
    mimeType?: string;
    sizes?: Record<string, { url: string; width: number; height: number }>;
}

export interface WPTerm {
    id: number;
    name: string;
    slug: string;
    description?: string;
    count?: number;
    parent?: number;
}

export interface WPCategory extends WPTerm {
    parent?: number;
}

export interface WPTag extends WPTerm {}

export interface WPUser {
    id: number;
    name: string;
    slug: string;
    avatar?: string;
    description?: string;
    url?: string;
}

export interface WPPost {
    id: number;
    slug: string;
    title: string;
    excerpt: string;
    content: string;
    date: string;
    dateFormatted: string;
    modified?: string;
    author: string;
    featuredImage: WPMedia | null;
    permalink: string;
    categories: WPCategory[];
    tags: WPTag[];
    meta: Record<string, unknown>;
    readTime: string;
    status?: 'publish' | 'draft' | 'pending' | 'private' | 'trash';
    type?: string;
}

export interface WPPage extends WPPost {
    template?: string;
    menuOrder?: number;
    parent?: number;
}

export interface WPMenuItem {
    id: number;
    title: string;
    url: string;
    target?: string;
    classes?: string[];
    description?: string;
    parent?: number;
    order?: number;
    objectType?: string;
    objectId?: number;
    children?: WPMenuItem[];
}

export interface WPMenu {
    id: number;
    name: string;
    slug: string;
    items: WPMenuItem[];
}

export interface WPTaxonomy {
    name: string;
    slug: string;
    description: string;
    types: string[];
    hierarchical: boolean;
    restBase: string;
}

export interface WPPagination {
    total: number;
    totalPages: number;
    currentPage: number;
    perPage: number;
}

export interface WPRestResponse<T> {
    data: T;
    pagination?: WPPagination;
}

export interface WPError {
    code: string;
    message: string;
    data?: {
        status: number;
        [key: string]: unknown;
    };
}
