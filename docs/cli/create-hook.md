# create hook

Crea un hook personalizado en `App/React/hooks/`.

## Uso

```bash
npx glory create hook <nombre>
```

## Que genera

```
App/React/hooks/{hookName}.ts
```

## Prefijo automatico

Si el nombre no empieza con `use`, se agrega:

| Input | Hook |
|-------|------|
| `useProductos` | `useProductos` |
| `Productos` | `useProductos` |
| `carrito` | `useCarrito` |

## Ejemplo

```bash
npx glory create hook useProductos
```

### useProductos.ts

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

## Comportamiento

- Si el hook ya existe, muestra error
- Crea el directorio `hooks/` si no existe
- Genera extension `.ts` (no `.tsx`)
