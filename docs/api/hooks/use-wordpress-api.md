# useWordPressApi

Fetch wrapper con autenticacion, cache y tipado para la REST API de WordPress.

## Firma

```typescript
function useWordPressApi<T = unknown>(
    endpoint: string,
    options?: ApiRequestOptions
): UseWordPressApiResult<T>
```

## Parametros

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `endpoint` | `string` | Ruta relativa o URL absoluta |
| `options` | `ApiRequestOptions` | Opciones de la peticion |

```typescript
interface ApiRequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
    body?: Record<string, unknown>;
    headers?: Record<string, string>;
    cache?: boolean;       // Cache en memoria (default: true para GET)
    cacheTtl?: number;     // TTL en ms (default: 30000)
    signal?: AbortSignal;  // Cancelacion manual
}
```

## Retorno

```typescript
interface UseWordPressApiResult<T> {
    data: T | null;
    isLoading: boolean;
    error: string | null;
    refetch: () => void;   // Forzar recarga
}
```

## Uso basico

```tsx
import { useWordPressApi } from '@/hooks';
import type { WPPost } from '@/types';

function MiIsla(): JSX.Element {
    const { data: posts, isLoading } = useWordPressApi<WPPost[]>('/wp/v2/posts');

    if (isLoading) return <p>Cargando...</p>;

    return (
        <ul>
            {posts?.map(post => (
                <li key={post.id}>{post.title}</li>
            ))}
        </ul>
    );
}
```

## POST

```tsx
const { data, error } = useWordPressApi<NewsletterResponse>(
    '/glory/v1/newsletter',
    {
        method: 'POST',
        body: { email: 'user@ejemplo.com' },
        cache: false,
    }
);
```

## Cache

- **GET** requests se cachean por defecto (30s TTL)
- La cache es en memoria (se pierde al recargar)
- La clave de cache incluye: method + endpoint + body

### Limpiar cache

```typescript
import { clearApiCache, invalidateApiCache } from '@/hooks/useWordPressApi';

// Limpiar todo
clearApiCache();

// Invalidar endpoint especifico
invalidateApiCache('/glory/v1/images');
```

## Autenticacion

El nonce se incluye automaticamente como header `X-WP-Nonce`. Se lee de:

1. `window.GLORY_CONTEXT.nonce`
2. `window.wpApiSettings.nonce` (fallback WP core)

## Cancelacion

Peticiones anteriores se cancelan automaticamente cuando cambian los parametros. Tambien puedes pasar tu propio `AbortSignal`:

```tsx
const controller = new AbortController();

const { data } = useWordPressApi('/ruta', {
    signal: controller.signal,
});

// Cancelar manualmente
controller.abort();
```

## Errores

El hook parsea errores de la REST API de WordPress:

```typescript
// Respuesta WP: { code: "rest_forbidden", message: "No permitido", data: { status: 403 } }
// error → "No permitido"

// Respuesta no-JSON:
// error → "HTTP 500: Internal Server Error"
```
