# Feature Flags (PHP)

Referencia tecnica del sistema de feature flags.

## GloryFeatures

Clase central en `Glory/src/Core/GloryFeatures.php`.

### API

```php
// Activar/desactivar
GloryFeatures::enable('nombreFeature');
GloryFeatures::disable('nombreFeature');

// Consultar
GloryFeatures::isActive('nombreFeature'); // bool
```

### Donde configurar

```php
// App/Config/control.php
use Glory\Core\GloryFeatures;

GloryFeatures::enable('pageManager');
GloryFeatures::disable('tailwind');
```

### Lista completa

| Flag | Categoria | Default |
|------|-----------|---------|
| `assetManager` | Core | Activo |
| `opcionManagerSync` | Core | Activo |
| `syncManager` | Core | Activo |
| `gloryLogger` | Core | Activo |
| `pageManager` | Core | Activo |
| `postTypeManager` | Core | Activo |
| `scheduleManager` | Core | Activo |
| `defaultContentManager` | Core | Activo |
| `postThumbnails` | Core | Activo |
| `menu` | Core | Desactivado |
| `tailwind` | Frontend | Desactivado |
| `shadcnUI` | Frontend | Desactivado |
| `amazonProduct` | Plugin | Desactivado |
| `queryProfiler` | Debug | Desactivado |

### Uso condicional en PHP

```php
if (GloryFeatures::isActive('tailwind')) {
    // Cargar assets de Tailwind
}

if (GloryFeatures::isActive('queryProfiler')) {
    QueryProfiler::start();
}
```

### Modificacion via CLI

```bash
node Glory/cli/glory.mjs setup --tailwind      # Activa tailwind
node Glory/cli/glory.mjs setup --shadcn         # Activa shadcnUI + tailwind
```

El CLI modifica `control.php` directamente, reemplazando `disable` por `enable`.
