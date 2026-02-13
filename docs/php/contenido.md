# Contenido

El sistema de contenido por defecto sincroniza datos definidos en PHP con WordPress.

## DefaultContentManager

Crea automaticamente paginas, posts y terminos cuando se activa el tema.

```php
// App/Content/defaultContent.php

// El contenido se define declarativamente
// y se sincroniza al activar el tema o al resetear
```

## Flujo

1. Defines contenido en `App/Content/defaultContent.php`
2. Al activar el tema, `DefaultContentManager` verifica que existe
3. Si no existe, lo crea en WordPress
4. Si existe, no lo sobrescribe

## ReactContentProvider

Inyecta contenido de WordPress en `window.__GLORY_CONTENT__` para React:

```
WordPress DB → ReactContentProvider → JSON → window.__GLORY_CONTENT__ → useGloryContent()
```

## Custom Post Types

```php
// App/Content/postType.php
use Glory\Manager\PostTypeManager;

// PostTypeManager permite registrar CPTs
```

## Activar

```php
// App/Config/control.php
GloryFeatures::enable('defaultContentManager');
GloryFeatures::enable('postTypeManager');
```

## Clases internas

| Clase | Responsabilidad |
|-------|----------------|
| `DefaultContentManager` | Orchestrador de sincronizacion |
| `DefaultContentRegistry` | Registro de definiciones |
| `DefaultContentRepository` | Lectura/escritura en WP DB |
| `DefaultContentSynchronizer` | Logica de sync |
| `ReactContentProvider` | Serializa datos para React |
| `PostTypeManager` | Registro de custom post types |
