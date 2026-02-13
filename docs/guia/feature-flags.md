# Feature Flags

Glory usa un sistema de feature flags para activar/desactivar funcionalidades sin tocar codigo.

## Configuracion

Los flags se definen en `App/Config/control.php`:

```php
<?php
use Glory\Core\GloryFeatures;

// Core (siempre activos)
GloryFeatures::enable('assetManager');
GloryFeatures::enable('pageManager');
GloryFeatures::enable('defaultContentManager');

// Frontend opt-in
GloryFeatures::disable('tailwind');
GloryFeatures::disable('shadcnUI');

// Plugins opcionales
GloryFeatures::disable('amazonProduct');
GloryFeatures::disable('queryProfiler');
```

## Flags disponibles

### Core

| Flag | Descripcion | Default |
|------|------------|---------|
| `assetManager` | Gestion de scripts/estilos | Activo |
| `pageManager` | Registro de paginas React | Activo |
| `opcionManagerSync` | Sincronizacion de opciones | Activo |
| `syncManager` | Sync de contenido | Activo |
| `defaultContentManager` | Contenido por defecto | Activo |
| `postTypeManager` | Custom post types | Activo |
| `scheduleManager` | Tareas programadas | Activo |
| `gloryLogger` | Logger interno | Activo |
| `postThumbnails` | Soporte de thumbnails | Activo |
| `menu` | Gestion de menus | Desactivado |

### Frontend

| Flag | Descripcion | Default |
|------|------------|---------|
| `tailwind` | Tailwind CSS v4 | Desactivado |
| `shadcnUI` | shadcn/ui (requiere tailwind) | Desactivado |

### Plugins

| Flag | Descripcion | Default |
|------|------------|---------|
| `amazonProduct` | Plugin Amazon products | Desactivado |
| `queryProfiler` | Profiler de queries SQL | Desactivado |

## Uso en PHP

```php
if (GloryFeatures::isActive('tailwind')) {
    // Logica condicional
}
```

## Activacion via CLI

```bash
node Glory/cli/glory.mjs setup --tailwind
node Glory/cli/glory.mjs setup --shadcn     # Activa shadcn + tailwind
```

::: tip shadcn implica Tailwind
Activar `--shadcn` automaticamente activa `--tailwind` porque shadcn/ui depende de Tailwind CSS.
:::
