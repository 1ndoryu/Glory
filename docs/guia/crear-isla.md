# Crear una Isla

Una isla es un componente React que se monta en un contenedor HTML de WordPress.

## Con CLI (recomendado)

```bash
npx glory create island MiSeccion
```

Genera tres cosas:

1. `App/React/islands/MiSeccionIsland.tsx` — Componente
2. `App/React/styles/miSeccion.css` — Estilos
3. Registro automatico en `App/React/appIslands.tsx`

## Archivo generado

```tsx
import '../styles/miSeccion.css';

interface MiSeccionIslandProps {
    titulo?: string;
}

export function MiSeccionIsland({
    titulo = 'MiSeccion'
}: MiSeccionIslandProps): JSX.Element {
    return (
        <div id="seccionMiSeccion" className="contenedorMiSeccion">
            <h1>{titulo}</h1>
        </div>
    );
}

export default MiSeccionIsland;
```

## Manualmente

Si prefieres crear la isla a mano:

### 1. Crear el componente

```tsx
// App/React/islands/ProductosIsland.tsx

import '../styles/productos.css';

interface ProductosIslandProps {
    categoria?: string;
}

export function ProductosIsland({
    categoria
}: ProductosIslandProps): JSX.Element {
    return (
        <div id="seccionProductos" className="contenedorProductos">
            <h1>Productos {categoria && `— ${categoria}`}</h1>
        </div>
    );
}

export default ProductosIsland;
```

### 2. Crear los estilos

```css
/* App/React/styles/productos.css */

.contenedorProductos {
    padding: var(--espaciado-medio, 2rem);
}
```

### 3. Registrar en appIslands.tsx

```tsx
import { ProductosIsland } from './islands/ProductosIsland';

export const appIslands = {
    // ...islas existentes,
    ProductosIsland: ProductosIsland as React.ComponentType<Record<string, unknown>>,
};
```

## Convenciones

- Nombre del componente: `PascalCase` + sufijo `Island`
- Nombre del archivo: `{Nombre}Island.tsx`
- Nombre CSS: `camelCase.css`
- Contenedor: `id="seccion{Nombre}"`, `className="contenedor{Nombre}"`
- Cada isla debe tener un `id` unico en su contenedor principal

## Usando datos de WordPress

```tsx
import { useGloryContent } from '@/hooks';
import type { WPPost } from '@/types';

export function BlogIsland(): JSX.Element {
    const { data: posts, isLoading } = useGloryContent<WPPost>('blog');

    if (isLoading) return <p>Cargando...</p>;

    return (
        <div id="seccionBlog">
            {posts.map(post => (
                <article key={post.id}>
                    <h2>{post.title}</h2>
                    <p>{post.excerpt}</p>
                </article>
            ))}
        </div>
    );
}
```
