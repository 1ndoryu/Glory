# useGloryContent

Acceso tipado al contenido inyectado por PHP en `window.__GLORY_CONTENT__`.

## Firma

```typescript
function useGloryContent<T extends WPPost = WPPost>(
    key: string
): UseGloryContentResult<T>
```

## Parametros

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `key` | `string` | Clave del contenido registrado en PHP |

## Retorno

```typescript
interface UseGloryContentResult<T> {
    data: T[];          // Array de items tipados
    isLoading: boolean; // true durante la lectura inicial
    error: string | null; // Mensaje de error si falla
}
```

## Uso basico

```tsx
import { useGloryContent } from '@/hooks';
import type { WPPost } from '@/types';

function BlogIsland(): JSX.Element {
    const { data: posts, isLoading, error } = useGloryContent<WPPost>('blog');

    if (isLoading) return <p>Cargando...</p>;
    if (error) return <p>Error: {error}</p>;

    return (
        <ul>
            {posts.map(post => (
                <li key={post.id}>{post.title}</li>
            ))}
        </ul>
    );
}
```

## Con tipo personalizado

```tsx
interface Producto extends WPPost {
    meta: {
        precio: number;
        sku: string;
    };
}

const { data: productos } = useGloryContent<Producto>('productos');
```

## Fuente de datos

1. Lee del `GloryProvider` si esta disponible
2. Fallback a `window.__GLORY_CONTENT__` si no hay provider

## Validacion runtime

El hook valida cada item antes de devolverlo:

- `item.id` debe ser `number`
- `item.slug` debe ser `string`

Items que no pasen la validacion se filtran. En desarrollo, se muestra un warning en consola.

## Errores comunes

| Error | Causa |
|-------|-------|
| `"Glory content no disponible"` | `window.__GLORY_CONTENT__` no existe |
| `"Clave X no encontrada"` | La clave no esta registrada en PHP |
| `"No es un array"` | El contenido de la clave no es un array de posts |
