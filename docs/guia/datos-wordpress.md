# Datos de WordPress

Glory provee multiples formas de acceder a datos de WordPress desde React.

## Contenido inyectado (recomendado)

PHP inyecta datos en `window.__GLORY_CONTENT__` antes de que React se monte. Es la forma mas rapida — sin peticiones HTTP.

```tsx
import { useGloryContent } from '@/hooks';
import type { WPPost } from '@/types';

export function BlogIsland(): JSX.Element {
    const { data: posts, isLoading, error } = useGloryContent<WPPost>('blog');

    if (isLoading) return <p>Cargando...</p>;
    if (error) return <p>Error: {error}</p>;

    return (
        <div id="seccionBlog">
            {posts.map((post) => (
                <article key={post.id}>
                    <h2>{post.title}</h2>
                    <p>{post.excerpt}</p>
                </article>
            ))}
        </div>
    );
}
```

El contenido se registra en PHP con `ReactContentProvider`:

```php
// El contenido se inyecta automaticamente segun la pagina
```

## REST API

Para datos que necesitas cargar bajo demanda:

```tsx
import { useWordPressApi } from '@/hooks';
import type { WPPost } from '@/types';

export function BusquedaIsland(): JSX.Element {
    const { data, isLoading } = useWordPressApi<WPPost[]>(
        '/wp/v2/posts?search=react'
    );

    return (
        <div id="seccionBusqueda">
            {data?.map(post => (
                <p key={post.id}>{post.title}</p>
            ))}
        </div>
    );
}
```

### Endpoints disponibles

| Endpoint | Descripcion |
|----------|-------------|
| `/wp/v2/posts` | Posts de WordPress |
| `/wp/v2/pages` | Paginas |
| `/wp/v2/media` | Media Library |
| `/wp/v2/categories` | Categorias |
| `/wp/v2/tags` | Tags |
| `/glory/v1/images` | Imagenes del tema |
| `/glory/v1/images/url?alias=...` | URL de imagen por alias |
| `/glory/v1/images/aliases` | Todos los alias de imagenes |
| `/glory/v1/page-blocks/{id}` | Bloques de una pagina |
| `/glory/v1/newsletter` | Suscripcion newsletter |
| `/glory/v1/mcp/token` | Token MCP |

## Contexto global

Datos que estan siempre disponibles (URLs, nonce, locale):

```tsx
import { useGloryContext } from '@/hooks';

export function MiIsla(): JSX.Element {
    const { siteUrl, nonce, isAdmin, locale } = useGloryContext();

    return <p>Sitio: {siteUrl} | Locale: {locale}</p>;
}
```

## Opciones del tema

```tsx
import { useGloryOptions } from '@/hooks';

export function MiIsla(): JSX.Element {
    const { get } = useGloryOptions();
    const colorPrimario = get('colorPrimario', '#3b82f6');

    return <div style={{ color: colorPrimario }}>Hola</div>;
}
```

## Imagenes por alias

```tsx
import { useGloryMedia } from '@/hooks';

export function LogoIsland(): JSX.Element {
    const { url, alt, isLoading } = useGloryMedia('logo');

    if (isLoading || !url) return null;
    return <img src={url} alt={alt ?? 'Logo'} />;
}
```

## Tipos disponibles

Glory incluye tipos para todas las estructuras de WordPress:

```typescript
import type {
    WPPost,        // Post con titulo, excerpt, content, etc
    WPPage,        // Pagina (extends WPPost)
    WPMedia,       // Imagen/video
    WPCategory,    // Categoria
    WPTag,         // Tag
    WPUser,        // Usuario
    WPMenu,        // Menu
    WPMenuItem,    // Item de menu
} from '@/types';
```
