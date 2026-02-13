# Arquitectura

## Principio fundamental

```
WordPress (CMS)  ──datos──>  PHP Bridge  ──tipado──>  React (UI)
```

- **WordPress** almacena contenido, usuarios, media, opciones
- **PHP Bridge** registra paginas, sirve datos JSON, maneja SEO server-side
- **React** renderiza absolutamente todo el frontend

No hay templates PHP, no hay rendering PHP, no hay modo hibrido.

## Capas

### 1. WordPress (CMS)

WordPress funciona como headless CMS:
- Admin panel para gestionar contenido
- Media Library para imagenes
- REST API nativa + endpoints custom Glory
- Hooks y filters del framework

### 2. PHP Bridge (Glory/src/)

Codigo PHP minimo que hace lo que WordPress **obliga**:

| Clase | Responsabilidad |
|-------|----------------|
| `PageManager` | Registrar paginas como islas React |
| `ReactIslands` | Inyectar contenedores `data-island` en el HTML |
| `ReactContentProvider` | Serializar datos WP → `window.__GLORY_CONTENT__` |
| `AssetManager` | Encolar scripts/estilos de Vite |
| `MenuManager` | Registrar menus de WordPress |
| `SeoFrontendRenderer` | Meta tags, Open Graph, JSON-LD |
| `GloryFeatures` | Sistema de feature flags |

### 3. React (Glory/assets/react/ + App/React/)

El framework React tiene dos niveles:

**Core (Glory)** — Motor del framework:
- `IslandRegistry` — Registro tipado de islas
- `GloryProvider` — Context global con datos de WP
- `hydration.tsx` — Motor de montaje/hidratacion
- `ErrorBoundary` — Error boundary por isla
- Hooks framework (`useGloryContent`, `useGloryContext`...)

**App (Tu codigo)** — Proyecto del usuario:
- Islas (paginas/secciones)
- Componentes reutilizables
- Hooks personalizados
- Tipos del proyecto

## Inyeccion de datos

PHP inyecta dos globals antes de que React se monte:

### window.GLORY\_CONTEXT

Contexto global: URLs, nonce, locale, opciones del tema.

```typescript
interface GloryContext {
    siteUrl?: string;
    themeUrl?: string;
    restUrl?: string;
    nonce?: string;
    isAdmin?: boolean;
    locale?: string;
    options?: Record<string, unknown>;
}
```

### window.\_\_GLORY\_CONTENT\_\_

Contenido de WordPress indexado por clave:

```typescript
type GloryContentMap = Record<string, WPPost[]>;
```

Ejemplo: `{ blog: [...posts], portfolio: [...projects] }`

## Diagrama de montaje

```
1. WordPress sirve HTML con <div data-island="MiIsla" data-props="{...}">

2. Vite carga main.tsx

3. main.tsx:
   ├── Registra islas en IslandRegistry
   └── Llama initializeIslands()

4. hydration.tsx:
   ├── Busca todos los [data-island] en el DOM
   ├── Resuelve componente via IslandRegistry
   ├── Parsea data-props (JSON)
   └── Por cada isla:
       StrictMode
         └── GloryProvider (context + content)
               └── AppProvider? (tu provider personalizado)
                     └── ErrorBoundary (per-island)
                           └── Suspense? (solo lazy)
                                 └── DevOverlay? (solo dev)
                                       └── Componente

5. Monta con:
   - createRoot()  → CSR (default)
   - hydrateRoot() → SSG (data-hydrate="true")
```
