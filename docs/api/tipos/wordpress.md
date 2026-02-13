# Tipos WordPress

Tipos base que mapean las estructuras de datos de la REST API de WordPress.

## WPPost

```typescript
interface WPPost {
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
```

## WPPage

```typescript
interface WPPage extends WPPost {
    template?: string;
    menuOrder?: number;
    parent?: number;
}
```

## WPMedia

```typescript
interface WPMedia {
    id: number;
    url: string;
    alt: string;
    title?: string;
    caption?: string;
    width?: number;
    height?: number;
    mimeType?: string;
    sizes?: Record<string, {
        url: string;
        width: number;
        height: number;
    }>;
}
```

## WPCategory

```typescript
interface WPCategory extends WPTerm {
    parent?: number;
}
```

## WPTag

```typescript
interface WPTag extends WPTerm {}
```

## WPTerm

```typescript
interface WPTerm {
    id: number;
    name: string;
    slug: string;
    description?: string;
    count?: number;
    parent?: number;
}
```

## WPUser

```typescript
interface WPUser {
    id: number;
    name: string;
    slug: string;
    avatar?: string;
    description?: string;
    url?: string;
}
```

## WPMenu

```typescript
interface WPMenu {
    id: number;
    name: string;
    slug: string;
    items: WPMenuItem[];
}
```

## WPMenuItem

```typescript
interface WPMenuItem {
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
```

## WPTaxonomy

```typescript
interface WPTaxonomy {
    name: string;
    slug: string;
    description: string;
    types: string[];
    hierarchical: boolean;
    restBase: string;
}
```

## WPPagination

```typescript
interface WPPagination {
    total: number;
    totalPages: number;
    currentPage: number;
    perPage: number;
}
```

## WPRestResponse

```typescript
interface WPRestResponse<T> {
    data: T;
    pagination?: WPPagination;
}
```

## WPError

```typescript
interface WPError {
    code: string;
    message: string;
    data?: {
        status: number;
        [key: string]: unknown;
    };
}
```
