# SEO

Glory maneja SEO server-side via PHP. Los meta tags se renderizan en el HTML antes de que React se monte.

## Arquitectura

```
SeoFrontendRenderer (fachada)
├── MetaTagRenderer     ← Meta tags basicos (title, description, robots)
├── OpenGraphRenderer   ← Open Graph (og:title, og:image, og:description)
└── JsonLdRenderer      ← JSON-LD structured data
```

## SeoMetabox

El plugin de admin agrega un metabox para editar SEO por pagina:

- Titulo SEO
- Descripcion
- Imagen Open Graph
- Robots (index/noindex)

## Meta tags automaticos

Para paginas React, Glory genera meta tags automaticos via `PageSeoDefaults`:

```php
// Los defaults se basan en el titulo y slug de la pagina
// Se pueden sobreescribir via el metabox de admin
```

## En React

El SEO se maneja server-side (PHP), no en React. No necesitas `react-helmet` ni similar.

Si necesitas SEO dinamico desde React:

```tsx
// Cambiar titulo en el cliente
useEffect(() => {
    document.title = `${post.title} — Mi Sitio`;
}, [post.title]);
```

## Clases internas

| Clase | Responsabilidad | Lineas |
|-------|----------------|--------|
| `SeoFrontendRenderer` | Fachada, coordina renderers | ~45 |
| `MetaTagRenderer` | Title, description, robots, canonical | ~154 |
| `OpenGraphRenderer` | og:title, og:image, og:type, twitter cards | ~74 |
| `JsonLdRenderer` | Schema.org JSON-LD (Article, WebPage, etc) | ~279 |
| `SeoMetabox` | Metabox en admin para editar SEO | ~variable |
| `PageSeoDefaults` | Defaults SEO para paginas React | ~60 |
