# Estructura del Proyecto

```
glorytemplate/
├── App/                          # Tu codigo (proyecto)
│   ├── Config/
│   │   ├── pages.php             # Registro de paginas React
│   │   ├── control.php           # Feature flags
│   │   ├── assets.php            # Assets adicionales
│   │   ├── config.php            # Constantes del tema
│   │   ├── environment.php       # Variables de entorno
│   │   └── opcionesTema.php      # Opciones del panel admin
│   ├── Content/
│   │   ├── defaultContent.php    # Contenido por defecto (sync)
│   │   ├── menu.php              # Definicion de menus
│   │   └── postType.php          # Custom post types
│   └── React/
│       ├── appIslands.tsx        # Registro de islas del proyecto
│       ├── islands/              # Islas (paginas/secciones)
│       ├── blocks/               # Bloques para page builder
│       ├── styles/               # CSS de las islas
│       └── types/                # Tipos del proyecto
│
├── Glory/                        # Framework (submodulo git)
│   ├── assets/react/             # Motor React
│   │   └── src/
│   │       ├── core/             # Kernel: registry, provider, hydration
│   │       ├── hooks/            # Hooks del framework
│   │       ├── types/            # Tipos base WP + Glory
│   │       ├── islands/          # Islas de ejemplo
│   │       └── main.tsx          # Entry point
│   ├── cli/                      # CLI scaffolding
│   ├── src/                      # PHP Bridge (clases del framework)
│   │   ├── Admin/                # Panel de opciones
│   │   ├── Api/                  # REST API controllers
│   │   ├── Core/                 # Features, logger, registries
│   │   ├── Manager/              # PageManager, MenuManager, AssetManager...
│   │   ├── Seo/                  # MetaTag, OpenGraph, JsonLd
│   │   ├── Services/             # ReactIslands, ReactContentProvider
│   │   └── Utility/              # Assets, Email, Image utils
│   └── Config/                   # Config interna del framework
│
├── functions.php                 # Entry point WordPress
├── header.php                    # HTML head + body
├── index.php                     # Contenedor principal
├── footer.php                    # Cierre + scripts
├── TemplateReact.php             # Template unico React
├── package.json                  # Scripts npm + deps
├── composer.json                 # Deps PHP
└── style.css                     # Metadata del tema WP
```

## App/ vs Glory/

| Directorio | Que es | Quien lo edita |
|------------|--------|----------------|
| `App/` | Tu proyecto. Islas, config, estilos, tipos | Tu |
| `Glory/` | Framework core. Submodulo git | Actualizaciones del framework |

::: warning Regla fundamental
Nunca modifiques archivos dentro de `Glory/` directamente. Todo tu codigo va en `App/`.
:::

## Archivos clave

### App/React/appIslands.tsx

Registro central de islas del proyecto. Cada isla que crees debe estar importada y registrada aqui. El CLI lo hace automaticamente.

```tsx
import { BienvenidaIsland } from './islands/BienvenidaIsland';

export const appIslands = {
    BienvenidaIsland: BienvenidaIsland,
};

export default appIslands;
```

### App/Config/pages.php

Define que paginas existen y que isla renderiza cada una:

```php
PageManager::reactPage('home', 'BienvenidaIsland', [
    'titulo' => 'Bienvenido a Glory React'
]);
```

### App/Config/control.php

Feature flags del proyecto:

```php
GloryFeatures::enable('pageManager');
GloryFeatures::disable('tailwind');
```
