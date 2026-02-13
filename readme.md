# Glory Framework

Framework TypeScript-first para WordPress. React es el UI, WordPress solo maneja datos.

## Filosofia

Glory NO es un framework PHP que soporta React. Glory ES un framework TypeScript/React que usa WordPress exclusivamente como CMS. PHP existe como puente minimo entre WordPress y React. Cero logica de frontend en PHP.

```
WordPress (CMS)  ──datos──>  PHP Bridge (minimo)  ──tipado──>  React (todo el UI)
     |                            |                                |
  Admin panel               REST API + SEO                  Islas + Hooks
  Contenido                 Registrar paginas               Validacion runtime
  Media Library             Servir JSON tipado              Zustand state
```

## Quick Start (5 minutos)

```bash
# 1. Clonar en wp-content/themes/
cd wp-content/themes
git clone --branch glory-react https://github.com/1ndoryu/glorytemplate.git mi-proyecto
cd mi-proyecto

# 2. Inicializar
node Glory/cli/glory.mjs setup

# 3. Crear primera isla
node Glory/cli/glory.mjs create island MiPagina

# 4. Desarrollo
npm run dev
```

O con flags:

```bash
node Glory/cli/glory.mjs setup --tailwind --shadcn
```

## Stack

| Capa | Tecnologia | Rol |
|------|-----------|-----|
| CMS | WordPress + PHP 8+ | Solo datos, admin, REST API |
| PHP Bridge | Glory/src/ | Registrar paginas, servir datos, SEO server-side |
| Frontend | React 18 + TypeScript 5.6 | TODO el UI |
| Build | Vite 6 + HMR | Dev server + produccion |
| Estilos | Tailwind CSS 4 (opt-in) | Feature flag |
| Componentes UI | shadcn/ui (opt-in) | Feature flag |
| Estado | Zustand | Estado global React |
| Linting | ESLint 9 + Prettier | Siempre activo |

## Arquitectura: React Islands

Cada pagina se registra en PHP y se renderiza como una isla React.

### 1. Registrar pagina (PHP)

```php
// App/Config/pages.php
PageManager::reactPage('contacto', 'ContactoIsland', [
    'titulo' => 'Contacto'
]);
```

### 2. Crear isla (CLI o manual)

```bash
npx glory create island Contacto
```

Genera:
- `App/React/islands/ContactoIsland.tsx` (componente tipado)
- `App/React/styles/contacto.css` (estilos)
- Registro en `App/React/appIslands.tsx`

### 3. El flujo

```
PHP renderiza:  <div data-island="ContactoIsland" data-props="{...}">
main.tsx:       IslandRegistry busca "ContactoIsland"
hydration.tsx:  Monta con createRoot o hydrateRoot
Wrappers:       StrictMode > GloryProvider > AppProvider > ErrorBoundary > Componente
```

## Arquitectura React (core/)

```
Glory/assets/react/src/
├── core/                      # Motor del framework
│   ├── IslandRegistry.ts      # Registro tipado de islas (estatico + lazy)
│   ├── GloryProvider.tsx      # Context global (contenido + contexto WP)
│   ├── hydration.tsx          # Logica de montaje/hidratacion
│   ├── ErrorBoundary.tsx      # Error boundary individual por isla
│   └── DevOverlay.tsx         # Overlay de debug en desarrollo
├── hooks/                     # Hooks del framework
│   ├── useGloryContent.ts     # Acceso tipado a contenido WP
│   ├── useGloryContext.ts     # Contexto global (siteUrl, nonce, etc)
│   ├── useGloryOptions.ts     # Opciones del tema
│   ├── useWordPressApi.ts     # Fetch wrapper con auth y cache
│   ├── useGloryMedia.ts      # Imagenes via REST API
│   └── useIslandProps.ts      # Props tipados de la isla actual
├── types/                     # Tipos compartidos WP + Glory
│   ├── wordpress.ts           # WPPost, WPMedia, WPUser, WPTerm...
│   ├── glory.ts               # GloryContext, GloryContentMap, IslandRegistry
│   ├── api.ts                 # Tipos de respuesta de /glory/v1/*
│   └── pageBuilder.ts         # BlockDefinition, PageLayout
├── components/ui/             # shadcn/ui (opt-in)
├── islands/                   # Islas de ejemplo
├── pageBuilder/               # Page Builder visual
└── main.tsx                   # Entry point
```

## Hooks del Framework

### useGloryContent<T>()

Acceso tipado al contenido inyectado por PHP.

```tsx
const { data, isLoading, error } = useGloryContent<WPPost>('blog');
```

- Lee de `window.__GLORY_CONTENT__` via GloryProvider
- Validacion runtime basica (id, slug)
- Fallback automatico si no hay provider

### useGloryContext()

Contexto global de WordPress.

```tsx
const { siteUrl, nonce, isAdmin, locale } = useGloryContext();
```

### useWordPressApi<T>()

Fetch wrapper con autenticacion, tipos y cache.

```tsx
const { data, isLoading, error, refetch } = useWordPressApi<ImageListResponse>('/glory/v1/images');
```

- Autenticacion via nonce (X-WP-Nonce)
- Cache en memoria con TTL configurable
- Cancelacion automatica de peticiones anteriores

### useGloryOptions()

Opciones del tema con acceso tipado.

```tsx
const { options, get, has } = useGloryOptions();
const color = get('colorPrimario', '#3b82f6');
```

### useGloryMedia(alias)

Imagenes via REST API.

```tsx
const { url, alt, isLoading } = useGloryMedia('logo');
```

### useIslandProps<T>()

Props tipados de la isla actual.

```tsx
interface MiIslaProps { titulo: string; items: Item[] }
const props = useIslandProps<MiIslaProps>(rawProps);
```

## IslandRegistry

Registro tipado que soporta carga estatica y lazy.

```tsx
// Carga estatica (incluida en el bundle)
islandRegistry.register('MiIsla', MiIslaComponent);

// Carga lazy (import dinamico, solo cuando aparece en el DOM)
islandRegistry.registerLazy('PesadaIsla', () => import('./islands/PesadaIsla'));

// Batch desde mapa
islandRegistry.registerAll(appIslands);
```

Las islas lazy se envuelven automaticamente en `Suspense` con fallback de carga.

## Error Boundaries

Cada isla tiene su propio error boundary. Si una isla falla, las demas siguen funcionando.

- En desarrollo: muestra error detallado con boton de reintentar
- En produccion: muestra "Contenido no disponible"
- Fallback personalizable via props

## DevOverlay

En modo desarrollo (`import.meta.env.DEV`), cada isla muestra un badge con:
- Nombre de la isla
- Conteo de renders
- Tooltip con props disponibles

## GloryProvider

Context global que envuelve automaticamente cada isla. Provee:
- `context`: GloryContext (siteUrl, nonce, isAdmin, locale, options...)
- `content`: GloryContentMap (contenido de WordPress)

Los hooks leen del provider cuando esta disponible, con fallback a `window` globals para compatibilidad.

## Feature Flags

```php
// App/Config/control.php
GloryFeatures::enable('pageManager');     // Core
GloryFeatures::enable('tailwind');        // Tailwind CSS
GloryFeatures::enable('shadcnUI');        // shadcn/ui (requiere tailwind)
GloryFeatures::disable('stripe');         // Stripe optional
GloryFeatures::disable('queryProfiler'); // Debug SQL
```

## CLI

```bash
# Scaffolding
npx glory create island MiSeccion       # Isla (.tsx + .css + registro)
npx glory create page contacto          # Isla + registro PHP
npx glory create component BotonPrimario # Componente
npx glory create hook useProductos      # Hook

# Proyecto
npx glory setup --tailwind              # Inicializar proyecto
npx glory new mi-proyecto --shadcn      # Crear proyecto nuevo
```

## PHP Bridge (Glory/src/)

PHP solo hace lo que WordPress OBLIGA:

| Responsabilidad | Clase |
|----------------|-------|
| Registrar paginas | PageManager, PageDefinition |
| SEO server-side | MetaTagRenderer, OpenGraphRenderer, JsonLdRenderer |
| Servir datos JSON | REST API Controllers |
| Gestionar assets | AssetManager |
| Registrar menus | MenuManager, MenuSync |
| Opciones del tema | OpcionManager, OpcionRegistry |
| Contenido default | DefaultContentManager |

Todos los archivos PHP bajo 300 lineas (SRP estricto).

## Scripts npm

```bash
npm run dev           # Vite HMR
npm run build         # Produccion
npm run lint          # ESLint
npm run lint:fix      # ESLint auto-fix
npm run format        # Prettier
npm run type-check    # TypeScript
npm run install:all   # Todas las deps
```

## Principios

1. **TypeScript es el lenguaje.** Si puedes hacerlo en TS, hazlo en TS.
2. **PHP solo para lo que WordPress obliga.** Hooks, filters, REST, SEO.
3. **Cada archivo < 300 lineas.** SRP estricto.
4. **Cero `any` en TypeScript.** ESLint lo reporta.
5. **Islas independientes.** Una rota no tumba las demas.
6. **Feature flags para todo lo opcional.** Tailwind, shadcn, Stripe.
