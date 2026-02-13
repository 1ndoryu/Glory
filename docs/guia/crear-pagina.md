# Crear una Pagina

Una pagina en Glory es una isla React registrada en WordPress. El CLI crea la isla **y** la registra en PHP.

## Con CLI

```bash
npx glory create page contacto
```

Esto hace dos cosas:

1. **Crea la isla** (`ContactoIsland.tsx` + `.css` + registro en `appIslands.tsx`)
2. **Registra en PHP** (agrega entrada en `App/Config/pages.php`)

## Que se genera en PHP

```php
// App/Config/pages.php

PageManager::reactPage('contacto', 'ContactoIsland', [
    'titulo' => 'Contacto'
]);
```

Esto le dice a WordPress:
- Crea una pagina con slug `contacto`
- Cuando se visite, renderiza el contenedor para `ContactoIsland`
- Pasa `{ titulo: 'Contacto' }` como props

## Registro manual

Si quieres control total sobre las props:

```php
// Props estaticos
PageManager::reactPage('servicios', 'ServiciosIsland', [
    'titulo' => 'Nuestros Servicios',
    'contactoEmail' => 'info@ejemplo.com'
]);

// Props dinamicos (callback)
PageManager::reactPage('perfil', 'PerfilIsland', function($pageId) {
    return [
        'usuario' => wp_get_current_user()->display_name,
        'esAdmin' => current_user_can('manage_options'),
    ];
});
```

## Paginas con datos de WordPress

```php
// La isla puede acceder a contenido via useGloryContent
PageManager::reactPage('blog', 'BlogIsland');
```

El contenido se inyecta automaticamente via `ReactContentProvider` en `window.__GLORY_CONTENT__`.

## Slug

El slug se genera automaticamente del nombre:

| Input | Slug |
|-------|------|
| `contacto` | `contacto` |
| `MiPagina` | `mi-pagina` |
| `sobre nosotros` | `sobre-nosotros` |

## Verificar

Despues de crear la pagina:

1. Abre WordPress Admin
2. Ve a **Paginas** — deberia aparecer la pagina creada
3. Si usas `defaultContentManager`, la pagina se crea automaticamente en la primera carga
