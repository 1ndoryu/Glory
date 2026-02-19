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
- 🗄️ Schema System (tipado end-to-end)
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

- Core: `GloryFeatures`, `GloryConfig`, `Setup`, `GloryLogger`, `SchemaRegistry`.
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
npx glory create table MiTabla
```

### Comandos de schema

```bash
npx glory schema:generate    # Genera Cols + DTOs + schema.ts desde los schemas
npx glory schema:validate    # Detecta strings hardcodeados en PHP que deberían usar Cols
```

### Comandos de proyecto

```bash
npx glory setup --tailwind
npx glory new mi-proyecto --shadcn
```

---

## 🗄️ Schema System (tipado end-to-end)

Glory incluye un sistema de schemas que es la **única fuente de verdad** para estructura de base de datos. Elimina strings hardcodeados (nombres de columna, nombres de tabla y valores enum) y genera constantes PHP, DTOs tipados e interfaces TypeScript automáticamente.

### Arquitectura

```text
App/Config/Schema/SamplesSchema.php  ← Source of truth (declarativo)
        ↓  npx glory schema:generate
_generated/SamplesCols.php           ← Tabla + constantes de columna
_generated/SamplesEnums.php          ← Constantes de valores enum (check)
_generated/SamplesDTO.php            ← DTO tipado con desdeRow() + aArrayDB()
App/React/types/_generated/schema.ts ← Interfaces TS + Cols + Enums mirror
        ↓  Runtime
SchemaRegistry                       ← Carga schemas, valida en modo estricto
PostgresService                      ← Valida queries contra schemas registrados
```

### Tres tipos de constantes generadas

| Clase | Ejemplo | Cubre |
|---|---|---|
| `SamplesCols` | `SamplesCols::TITULO`, `SamplesCols::TABLA` | Nombres de columna y tabla |
| `SamplesEnums` | `SamplesEnums::ESTADO_ACTIVO`, `LikesEnums::REACCION_ENCANTA` | Valores permitidos (check) |
| `SamplesDTO` | `SamplesDTO::desdeRow($row)` | Mapeo tipado de filas BD |

### Definir un schema

```php
class SamplesSchema extends TableSchema
{
    public function tabla(): string { return 'samples'; }

    public function columnas(): array
    {
        return [
            'id'     => ['tipo' => 'int', 'pk' => true],
            'titulo' => ['tipo' => 'string', 'max' => 200],
            'estado' => ['tipo' => 'string', 'check' => ['procesando', 'activo', 'eliminado']],
            'tipo'   => ['tipo' => 'string', 'check' => ['loop', 'oneshot', 'fx', 'vocal']],
            'bpm'    => ['tipo' => 'int', 'nullable' => true],
        ];
    }
}
```

### Usar constantes de columna (Cols)

```php
use App\Config\Schema\_generated\SamplesCols;

/* Antes (string frágil, rompe silencioso si se renombra): */
$titulo = $row['titulo'];
$sql = "SELECT titulo FROM samples WHERE estado = 'activo'";

/* Ahora (refactor-safe, autocompletado): */
$titulo = $row[SamplesCols::TITULO];
$sql = "SELECT " . SamplesCols::TITULO . " FROM " . SamplesCols::TABLA . " WHERE " . SamplesCols::ESTADO . " = :estado";
```

### Usar constantes de enum (Enums)

Los valores `check` de cada columna se generan como constantes en una clase `{Tabla}Enums`. Esto elimina strings literales como `'activo'`, `'sample'`, `'encanta'` de todos los controladores.

```php
use App\Config\Schema\_generated\SamplesEnums;
use App\Config\Schema\_generated\LikesEnums;

/* Antes (strings dispersos y frágiles): */
$where[] = "s.estado = 'activo'";
$sql = "SELECT * FROM likes WHERE tipo = 'sample' AND reaccion = 'encanta'";

/* Ahora (typo-safe, autocompletado, error de compilación si el valor desaparece): */
$where[] = "s." . SamplesCols::ESTADO . " = '" . SamplesEnums::ESTADO_ACTIVO . "'";

PostgresService::consultarUno(
    "SELECT * FROM likes WHERE tipo = :tipo AND reaccion = :reaccion",
    [
        'tipo'    => LikesEnums::TIPO_SAMPLE,
        'reaccion'=> LikesEnums::REACCION_ENCANTA,
    ]
);
```

### Enums disponibles (ejemplos)

```php
/* Samples */
SamplesEnums::ESTADO_PROCESANDO    // 'procesando'
SamplesEnums::ESTADO_ACTIVO        // 'activo'
SamplesEnums::ESTADO_ELIMINADO     // 'eliminado'
SamplesEnums::ESTADO_EN_SUPERVISION// 'en_supervision'
SamplesEnums::TIPO_LOOP            // 'loop'
SamplesEnums::TIPO_ONESHOT         // 'oneshot'

/* Likes */
LikesEnums::TIPO_SAMPLE            // 'sample'
LikesEnums::TIPO_COMENTARIO        // 'comentario'
LikesEnums::REACCION_LIKE          // 'like'
LikesEnums::REACCION_ENCANTA       // 'encanta'

/* Usuarios */
UsuariosExtEnums::PLAN_FREE        // 'free'
UsuariosExtEnums::PLAN_PRO         // 'pro'
UsuariosExtEnums::ROL_ADMIN        // 'admin'
```

### Validación automática

- En modo estricto (`WP_DEBUG`), `SchemaRegistry` lanza `SchemaException` si se intenta acceder a una tabla sin schema.
- `npx glory schema:validate` escanea PHP buscando `$row['xxx']` que no coincidan con ningún schema.
- Si se añade o elimina un valor `check` en el schema y se regenera, TypeScript genera un error donde el tipo union no cubra el nuevo valor.

Para documentación detallada, ver [Glory/docs/php/schema-system.md](docs/php/schema-system.md) y [Glory/docs/cli/schema-generate.md](docs/cli/schema-generate.md).

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
- Usar `<GloryLink>` en lugar de `<a href>` para navegación interna SPA.

---

## 🔀 Navegación SPA entre islas

Glory incluye navegación client-side nativa entre páginas React. Los clicks en enlaces internos se interceptan y la isla correspondiente se renderiza sin recarga completa del navegador.

### Cómo funciona

1. PHP inyecta `window.__GLORY_ROUTES__` con el mapa de todas las `reactPage()` definidas.
2. `hydration.tsx` detecta las rutas y activa modo SPA con un `PageRenderer` como root.
3. Al hacer click en un `<GloryLink>`, el store Zustand actualiza la ruta y el `PageRenderer` monta la isla correspondiente.
4. El historial del navegador se actualiza con `pushState`, soportando botones atrás/adelante.

### Componentes

| Componente | Ubicación | Propósito |
|---|---|---|
| `GloryLink` | `core/router/GloryLink.tsx` | Reemplazo de `<a>` con navegación SPA |
| `PageRenderer` | `core/router/PageRenderer.tsx` | Renderiza la isla según la ruta actual |
| `navigationStore` | `core/router/navigationStore.ts` | Store Zustand con estado de navegación |
| `useNavigation` | `hooks/useNavigation.ts` | Hook público para navegar programáticamente |

### Uso en componentes

```tsx
import { GloryLink } from '@/core/router';

// Enlace con navegación SPA
<GloryLink href="/servicios/">Ver servicios</GloryLink>

// Forzar recarga completa
<GloryLink href="/admin/" forceReload>Admin</GloryLink>
```

### Navegación programática

```tsx
import { useNavigation } from '@/hooks';

function MiComponente() {
    const { navegar, rutaActual, esRutaActiva } = useNavigation();

    return (
        <button onClick={() => navegar('/contacto/')}>
            Ir a contacto
        </button>
    );
}
```

### Comportamiento

- **Enlaces internos** registrados en `pages.php`: navegación SPA sin recarga.
- **Enlaces externos** o no registrados: navegación normal del navegador.
- **Teclas modificadoras** (Ctrl+click, Cmd+click): abren en nueva pestaña (comportamiento nativo).
- **Historial**: soporta botón atrás/adelante del navegador.

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
