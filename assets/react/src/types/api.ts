/*
 * Tipos para las respuestas de la REST API de Glory (/glory/v1/*)
 */

import type { WPMedia } from './wordpress';

/* GET /glory/v1/images */
export interface ImageListResponse {
    images: WPMedia[];
    total: number;
}

/* GET /glory/v1/images/url */
export interface ImageUrlResponse {
    url: string;
    alt: string;
}

/* GET /glory/v1/images/random */
export interface RandomImageResponse {
    image: WPMedia;
}

/* GET /glory/v1/images/aliases */
export interface ImageAliasesResponse {
    aliases: Record<string, string>;
}

/* GET/POST /glory/v1/page-blocks/{page_id} */
export interface PageBlock {
    id: string;
    type: string;
    data: Record<string, unknown>;
    order: number;
}

export interface PageBlocksResponse {
    blocks: PageBlock[];
    pageId: number;
}

/* POST /glory/v1/newsletter */
export interface NewsletterSubscribeRequest {
    email: string;
    name?: string;
}

export interface NewsletterSubscribeResponse {
    success: boolean;
    message: string;
}

/* GET/POST/DELETE /glory/v1/mcp/token */
export interface MCPTokenResponse {
    token: string;
    expiresAt: string;
}

/* GET /glory/v1/mcp/config */
export interface MCPConfigResponse {
    enabled: boolean;
    endpoints: string[];
}

/*
 * Tipo generico para respuestas de la API Glory.
 * Todas las respuestas siguen este patron.
 */
export interface GloryApiResponse<T = unknown> {
    success: boolean;
    data?: T;
    message?: string;
    error?: string;
    code?: string;
}

/*
 * Opciones para el hook useWordPressApi
 */
export interface ApiRequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    body?: Record<string, unknown>;
    headers?: Record<string, string>;
    cache?: boolean;
    cacheTtl?: number;
    signal?: AbortSignal;
}
