# Tipos API

Tipos para las respuestas de la REST API de Glory (`/glory/v1/*`).

## ImageListResponse

`GET /glory/v1/images`

```typescript
interface ImageListResponse {
    images: WPMedia[];
    total: number;
}
```

## ImageUrlResponse

`GET /glory/v1/images/url?alias=...`

```typescript
interface ImageUrlResponse {
    url: string;
    alt: string;
}
```

## RandomImageResponse

`GET /glory/v1/images/random`

```typescript
interface RandomImageResponse {
    image: WPMedia;
}
```

## ImageAliasesResponse

`GET /glory/v1/images/aliases`

```typescript
interface ImageAliasesResponse {
    aliases: Record<string, string>;
}
```

## PageBlock / PageBlocksResponse

`GET /glory/v1/page-blocks/{page_id}`

```typescript
interface PageBlock {
    id: string;
    type: string;
    data: Record<string, unknown>;
    order: number;
}

interface PageBlocksResponse {
    blocks: PageBlock[];
    pageId: number;
}
```

## NewsletterSubscribeRequest / Response

`POST /glory/v1/newsletter`

```typescript
interface NewsletterSubscribeRequest {
    email: string;
    name?: string;
}

interface NewsletterSubscribeResponse {
    success: boolean;
    message: string;
}
```

## MCPTokenResponse

`GET/POST/DELETE /glory/v1/mcp/token`

```typescript
interface MCPTokenResponse {
    token: string;
    expiresAt: string;
}
```

## MCPConfigResponse

`GET /glory/v1/mcp/config`

```typescript
interface MCPConfigResponse {
    enabled: boolean;
    endpoints: string[];
}
```

## GloryApiResponse

Tipo generico para todas las respuestas de la API.

```typescript
interface GloryApiResponse<T = unknown> {
    success: boolean;
    data?: T;
    message?: string;
    error?: string;
    code?: string;
}
```

## ApiRequestOptions

Opciones para `useWordPressApi`.

```typescript
interface ApiRequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    body?: Record<string, unknown>;
    headers?: Record<string, string>;
    cache?: boolean;
    cacheTtl?: number;
    signal?: AbortSignal;
}
```
