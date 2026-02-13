# Paginas

El sistema de paginas conecta slugs de WordPress con islas React.

## PageManager::reactPage()

Forma recomendada de registrar paginas.

```php
PageManager::reactPage(string $slug, string $islandName, array|callable $props = []);
```

### Parametros

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| `$slug` | `string` | Slug de la pagina en WordPress |
| `$islandName` | `string` | Nombre de la isla React |
| `$props` | `array\|callable` | Props estaticos o callback |

### Ejemplos

```php
use Glory\Manager\PageManager;

// Props estaticos
PageManager::reactPage('home', 'BienvenidaIsland', [
    'titulo' => 'Bienvenido a Glory React'
]);

// Sin props
PageManager::reactPage('contacto', 'ContactoIsland');

// Props dinamicos (callback recibe el page ID)
PageManager::reactPage('perfil', 'PerfilIsland', function($pageId) {
    return [
        'usuario' => wp_get_current_user()->display_name,
        'esAdmin' => current_user_can('manage_options'),
    ];
});
```

## PageManager::define()

Para paginas con logica PHP compleja:

```php
PageManager::define('editor', 'editor');
```

Requiere un archivo template en `App/Templates/pages/`.

## PageManager::registerReactFullPages()

Marca paginas `define()` como "full React" (sin sidebar/header PHP):

```php
PageManager::registerReactFullPages(['editor']);
```

## Modo de contenido

```php
PageManager::setDefaultContentMode('code');
// 'code' = contenido definido en PHP (default)
// 'wp'   = contenido del editor de WordPress
```

## Archivo de config

Todas las paginas se registran en `App/Config/pages.php`.

## Clases internas

| Clase | Responsabilidad | Lineas |
|-------|----------------|--------|
| `PageManager` | API publica, registro de paginas | ~95 |
| `PageDefinition` | Estructura de datos de una pagina | ~246 |
| `PageProcessor` | Procesa y resuelve paginas activas | ~215 |
| `PageReconciler` | Sincroniza definiciones PHP con WordPress | ~115 |
| `PageSeoDefaults` | SEO por defecto para paginas React | ~60 |
| `PageTemplateInterceptor` | Intercepta el template loader de WP | ~80 |
