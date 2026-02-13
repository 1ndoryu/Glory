# create page

Crea una isla React y la registra como pagina en WordPress.

## Uso

```bash
npx glory create page <nombre>
```

## Que hace

1. Ejecuta `create island` (genera .tsx + .css + registro)
2. Agrega entrada en `App/Config/pages.php`

## Ejemplo

```bash
npx glory create page contacto
```

### Genera en pages.php

```php
// ContactoIsland
PageManager::reactPage('contacto', 'ContactoIsland', [
    'titulo' => 'Contacto'
]);
```

## Slug

El nombre se convierte a slug valido:

| Input | Slug |
|-------|------|
| `contacto` | `contacto` |
| `MiPagina` | `mi-pagina` |
| `sobre nosotros` | `sobre-nosotros` |
| `BlogPost` | `blog-post` |

## Comportamiento

- Si el slug ya existe en `pages.php`, muestra warning
- Inserta antes de la seccion "PAGINAS CON TEMPLATES PHP"
- Si no encuentra esa seccion, agrega al final del archivo
