# Hooks Personalizados

Los hooks encapsulan logica reutilizable. Glory provee un generador para empezar rapido.

## Con CLI

```bash
npx glory create hook useProductos
```

Genera `App/React/hooks/useProductos.ts`:

```typescript
import { useState, useEffect } from 'react';

interface ProductosResult {
    data: unknown;
    isLoading: boolean;
    error: string | null;
}

export function useProductos(): ProductosResult {
    const [data, setData] = useState<unknown>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        // TO-DO: implementar logica
        setData(null);
        setIsLoading(false);
    }, []);

    return { data, isLoading, error };
}
```

## Prefijo automatico

Si el nombre no empieza con `use`, se agrega automaticamente:

| Input | Hook generado |
|-------|---------------|
| `useProductos` | `useProductos` |
| `Productos` | `useProductos` |
| `carrito` | `useCarrito` |

## Ejemplo real

```typescript
import { useWordPressApi } from '@/hooks';
import type { WPPost } from '@/types';

interface UseProductosResult {
    productos: WPPost[];
    isLoading: boolean;
    error: string | null;
}

export function useProductos(categoria?: string): UseProductosResult {
    const endpoint = categoria
        ? `/wp/v2/posts?categories=${categoria}`
        : '/wp/v2/posts';

    const { data, isLoading, error } = useWordPressApi<WPPost[]>(endpoint);

    return {
        productos: data ?? [],
        isLoading,
        error,
    };
}
```

## Convenciones

- **Prefijo:** Siempre `use` (para que React lo reconozca como hook)
- **Ubicacion:** `App/React/hooks/`
- **Maximo:** 120 lineas (si crece mas, divide)
- **Tipado:** Siempre definir interface de retorno
- **Estado:** Maximo 3 `useState` por hook
