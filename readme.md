# ⚙️ Glory Framework

Framework para trabajar WordPress + React + TypeScript con una experiencia de desarrollo enfocada en:

- 🧩 arquitectura por islas,
- 🧠 tipado fuerte,
- 🔌 integración limpia con WordPress,
- 🛠️ herramientas de scaffolding y setup.

---

## 🧭 Índice

- ✨ Qué incluye Glory
- 🚀 Quick start
- 🧱 Arquitectura
- 🔄 Flujo completo de una página
- 📁 Estructura del framework
- 🪝 Hooks y utilidades principales
- 🧠 Managers, servicios y herramientas internas
- 🧰 CLI y generación de código
- 🎛️ Feature flags
- 📦 Scripts de desarrollo
- ✅ Casos de uso y buenas prácticas
- 🧯 Troubleshooting

---

## ✨ Qué incluye Glory

- **Motor de islas React** con montaje/hidratación automática.
- **Bridge PHP** para páginas, REST API y SEO server-side.
- **Core React** con provider global, error boundaries y registry tipado.
- **Hooks** para contenido, opciones y consumo de API.
- **CLI** para crear islas, páginas, componentes y hooks.
- **Instalador** para bootstrap de proyecto.

---

## 🚀 Quick start

```bash
cd wp-content/themes
git clone https://github.com/1ndoryu/glorytemplate.git mi-proyecto
cd mi-proyecto

node Glory/cli/glory.mjs setup
npm run dev
```

Con Tailwind + shadcn desde el inicio:

```bash
node Glory/cli/glory.mjs setup --tailwind --shadcn
```

---

## 🧱 Arquitectura

```text
WordPress (admin, contenido, media)
  ↓
Glory PHP Bridge (registro de páginas + API + SEO)
  ↓
Glory React Core (islas, hooks, provider, hydration)
  ↓
UI del proyecto (App/React)
```

### Capas y responsabilidades

| Capa | Responsabilidad |
|---|---|
| WordPress | Admin, contenido, media, usuarios |
| PHP Bridge (`Glory/src`) | Registro de páginas, endpoints, SEO server-side |
| React Core (`Glory/assets/react`) | Runtime React, hooks base, tipado compartido |
| Proyecto (`App/`) | Islas y lógica específica del sitio |

---

## 🔄 Flujo completo de una página

### 1) Registrar página en PHP

```php
PageManager::reactPage('contacto', 'ContactoIsland', [
    'titulo' => 'Contacto'
]);
```

### 2) Crear isla

```bash
npx glory create island Contacto
```

### 3) Registrar isla

El CLI puede registrarla automáticamente en `App/React/appIslands.tsx`.

### 4) Render en runtime

1. PHP imprime contenedor con `data-island` y `data-props`.
2. `main.tsx` busca islas en el DOM.
3. `IslandRegistry` resuelve el componente.
4. `hydration.tsx` monta u opera hidrata.
5. Wrappers aplicados: `StrictMode` → `GloryProvider` → `ErrorBoundary`.

---

## 📁 Estructura del framework

```text
Glory/
├── src/                          # Bridge PHP
│   ├── Core/                     # Setup, features, bootstrap
│   ├── Manager/                  # Page/Menu/Asset managers
│   ├── Api/                      # Controllers REST
│   ├── Seo/                      # Meta tags, OG, JSON-LD
│   ├── Services/                 # servicios de dominio
│   └── Utility/                  # utilidades compartidas
│
├── assets/react/
│   ├── src/core/                 # registry, hydration, provider, error boundary
│   ├── src/hooks/                # hooks framework
│   ├── src/types/                # tipos WP + Glory
│   ├── src/pageBuilder/          # page builder visual
│   ├── src/components/ui/        # componentes UI opt-in
│   └── scripts/                  # prerender y scripts build
│
├── cli/                          # create/setup/new
└── Config/                       # configuración interna
```

---

## 🪝 Hooks y utilidades principales

### `useGloryContent<T>()`

Lee contenido inyectado por WordPress con tipado y validación base.

```tsx
const { data, isLoading, error } = useGloryContent<WPPost>('blog');
```

### `useGloryContext()`

Accede a `siteUrl`, `nonce`, `isAdmin`, `locale`, etc.

### `useWordPressApi<T>()`

Fetch tipado con soporte de nonce, cache y control de errores.

### `useGloryOptions()`

Lee opciones del tema desde contexto compartido.

### `useIslandProps<T>()`

Tipa props de la isla actual con DX consistente.

---

## 🧠 Managers, servicios y herramientas internas

Resumen de las piezas más útiles del core PHP de Glory:

### Managers (registro y orquestación)

- `PageManager`, `PageProcessor`, `PageReconciler`: registro, validación y sincronización de páginas React.
- `AssetManager`: registro y carga ordenada de assets.
- `MenuManager`, `MenuSync`: normalización y sincronización de menús.
- `PostTypeManager`: registro de CPTs y soporte asociado.
- `DefaultContentManager`: contenido inicial controlado por configuración.

### Services (lógica de dominio)

- `ReactIslands`, `ReactContentProvider`, `ReactAssetLoader`: puente entre WordPress y runtime React.
- `DefaultContentSynchronizer`: sincroniza contenido base y metadatos.
- `TokenManager`: manejo de tokens/nonce y utilidades de seguridad.
- `QueryProfiler`, `PerformanceProfiler`, `HttpProfiler`: diagnóstico de rendimiento y consultas.
- `Stripe/*`: checkout, cliente API y verificación de webhooks.
- `Sync/*`: utilidades para sincronizar posts, términos y medios.

### Core, API, SEO y Tools

- Core: `GloryFeatures`, `GloryConfig`, `Setup`, `GloryLogger`.
- API: `ImagesController`, `NewsletterController`, `PageBlocksController`, `MCPController`.
- SEO: `MetaTagRenderer`, `OpenGraphRenderer`, `JsonLdRenderer`, `SeoFrontendRenderer`.
- Tools: `GitCommandRunner`, `ManejadorGit` para soporte de flujos internos.

---

## 🧰 CLI y generación de código

### Comandos de scaffolding

```bash
npx glory create island MiSeccion
npx glory create page contacto
npx glory create component BotonPrimario
npx glory create hook useProductos
```

### Comandos de proyecto

```bash
npx glory setup --tailwind
npx glory new mi-proyecto --shadcn
```

---

## 🎛️ Feature flags

Configuradas en `App/Config/control.php`.

```php
GloryFeatures::enable('pageManager');
GloryFeatures::disable('tailwind');
GloryFeatures::disable('shadcnUI');
GloryFeatures::disable('stripe');
GloryFeatures::disable('queryProfiler');
```

### Flags habituales

- `tailwind`: utilidades CSS.
- `shadcnUI`: componentes UI.
- `stripe`: integración de pagos.
- `queryProfiler`: depuración SQL.

---

## 📦 Scripts de desarrollo

| Script | Acción |
|---|---|
| `npm run dev` | Vite dev server con HMR |
| `npm run build` | Build producción + prerender |
| `npm run build:fast` | Build rápido |
| `npm run lint` | ESLint estricto |
| `npm run lint:fix` | Correcciones automáticas |
| `npm run format` | Prettier |
| `npm run type-check` | Validación TS |

---

## ✅ Casos de uso y buenas prácticas

### Ideal para

- Sitios corporativos con frontend moderno.
- Landing pages con SEO y componentes dinámicos.
- Proyectos WordPress que quieren DX sólida en TypeScript.

### Recomendaciones

- Mantener lógica de interfaz en React/TS.
- Usar el CLI para reducir boilerplate y errores manuales.
- Trabajar por islas pequeñas y cohesionadas.
- Ejecutar `type-check` + `lint` como rutina diaria.

---

## 🧯 Troubleshooting

### Una isla no aparece

1. Verifica que esté en `App/React/islands/`.
2. Revisa registro en `App/React/appIslands.tsx`.
3. Revisa página en `App/Config/pages.php`.

### Build falla en prerender

- Revisa `assets/react/scripts/prerender.ts`.
- Comprueba islas que dependan de APIs exclusivas de navegador.
- Omite en prerender las islas no compatibles con SSR.

### Error de tipos o lint

- Ejecuta `npm run type-check` para tipado.
- Ejecuta `npm run lint` para reglas de calidad.

---

## 📚 Relación con el tema

Este framework se consume desde el tema principal:

- [../README.md](../README.md)
- [../glory-plan.md](../glory-plan.md)
