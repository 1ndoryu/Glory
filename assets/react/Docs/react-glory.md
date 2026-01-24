# Glory React Islands - Documentacion

Sistema de integracion React para WordPress diseñado para **flexibilidad total**.

## Indice

- [Concepto Hibrido](#concepto-hibrido)
- [Guia Rapida: Crear Pagina React](#guia-rapida-crear-pagina-react)
- [Estructura de Archivos](#estructura-de-archivos)
- [Modos de Renderizado](#modos-de-renderizado)
- [Comandos NPM](#comandos-npm)
- [Workflow de Desarrollo](#workflow-de-desarrollo)
- [Workflow de Produccion](#workflow-de-produccion)
- [Eleccion de Modo por Tipo de Pagina](#eleccion-de-modo-por-tipo-de-pagina)
- [Compatibilidad PHP Puro](#compatibilidad-con-proyectos-php-puro)

---

## Concepto Hibrido

Glory permite decidir **pagina por pagina** que tecnologia usar. Todo convive en el mismo tema y hosting.

| Opcion         | Tecnologia  | SEO      | Caso de Uso                         |
| -------------- | ----------- | -------- | ----------------------------------- |
| **100% React** | React + SSG | Perfecto | Landing Pages, Dashboards, Apps     |
| **Hibrido**    | PHP + React | Perfecto | Posts de Blog, paginas con widgets  |
| **PHP Puro**   | WordPress   | Perfecto | Paginas legacy, admin, texto simple |

---

## Guia Rapida: Crear Pagina React

### Metodo OCP (Solo 3 Pasos - RECOMENDADO)

Este método cumple con el principio **Open/Closed**: los archivos existentes no se modifican para agregar nuevas páginas.

#### Paso 1: Crear el Componente

Crea tu componente en `App/React/islands/MiPaginaIsland.tsx`:

```tsx
interface MiPaginaIslandProps {
    siteName?: string;
}

export function MiPaginaIsland({ siteName = 'Glory' }: MiPaginaIslandProps): JSX.Element {
    return (
        <div className="contenedorPrincipal">
            <h1>Mi Pagina en {siteName}</h1>
            <p>Contenido aqui</p>
        </div>
    );
}

export default MiPaginaIsland;
```

#### Paso 2: Registrar en inicializarIslands.ts

Edita `App/React/config/inicializarIslands.ts`:

```tsx
import {MiPaginaIsland} from '../islands/MiPaginaIsland';

registrarIsland(
    'MiPaginaIsland',
    MiPaginaIsland,
    'Descripcion de mi pagina'
);
```

#### Paso 3: Una Linea en pages.php

Edita `App/Config/pages.php`:

```php
PageManager::reactPage('mi-pagina', 'MiPaginaIsland', [
    'siteName' => 'Mi Sitio'
]);
```

**¡Listo!** Tu pagina estara disponible en `/mi-pagina/`

> **Nota OCP:** Con este sistema, `appIslands.tsx` nunca necesita modificarse.
> Las islands se auto-registran al ser importadas en `inicializarIslands.ts`.

---

### Metodo Legacy (Compatibilidad)

Para proyectos existentes que prefieren el método manual, puedes seguir agregando islands directamente en `appIslands.tsx` en el objeto `islandsManuales`:

```tsx
const islandsManuales: Record<string, React.ComponentType<Record<string, unknown>>> = {
    MiPaginaIsland: MiPaginaIsland,
};
```

Este método sigue funcionando pero **no se recomienda** para nuevos desarrollos.

### Opciones de reactPage()

```php
// Sin props
PageManager::reactPage('about', 'AboutIsland');

// Con props estaticos
PageManager::reactPage('about', 'AboutIsland', [
    'siteName' => 'Mi Sitio',
    'year' => 2025
]);

// Con props dinamicos (callback)
PageManager::reactPage('home', 'HomeIsland', function($pageId) {
    $blocksJson = get_post_meta($pageId, '_glory_page_blocks', true);
    return [
        'blocks' => $blocksJson ? json_decode($blocksJson, true) : null,
        'isAdmin' => current_user_can('edit_pages'),
        'saveEndpoint' => rest_url('glory/v1/page-blocks/' . $pageId),
        'restNonce' => wp_create_nonce('wp_rest')
    ];
});

// Con restriccion de roles
PageManager::reactPage('admin', 'AdminIsland', [], ['administrator']);
```

---

### Metodo Tradicional (4 Pasos - Para Casos Complejos)

Usa este metodo cuando necesites logica PHP muy compleja que no encaja en un callback.

#### Paso 1: Crear Componente

Igual que el metodo simplificado.

#### Paso 2: Registrar en appIslands.tsx

Igual que el metodo simplificado.

#### Paso 3: Crear Funcion PHP

Crea `App/Templates/pages/mi-pagina.php`:

```php
<?php

use Glory\Services\ReactIslands;

function miPagina()
{
    // Logica PHP compleja aqui
    $datos = obtenerDatosComplejos();
    
    echo ReactIslands::render('MiPaginaIsland', [
        'datos' => $datos
    ]);
}
```

#### Paso 4: Definir en pages.php

```php
PageManager::registerReactFullPages(['mi-pagina']);
PageManager::define('mi-pagina', 'miPagina');
```

---

### Comparacion de Metodos

| Metodo        | Pasos | Archivo PHP   | Cuando Usar             |
| ------------- | ----- | ------------- | ----------------------- |
| `reactPage()` | 3     | Auto-generado | 90% de los casos        |
| `define()`    | 4     | Manual        | Logica PHP muy compleja |

**Recomendacion:** Siempre empieza con `reactPage()`. Solo usa `define()` si realmente necesitas un archivo PHP dedicado.

---

## Estructura de Archivos

```
glory/
├── App/                              # Contenido ESPECIFICO del proyecto
│   ├── React/
│   │   ├── islands/                  # Componentes React del proyecto
│   │   │   ├── DashboardIsland.tsx
│   │   │   └── MiNuevaIsland.tsx
│   │   ├── config/                   # Configuración y registros (OCP)
│   │   │   ├── registroIslands.ts    # API de registro de islands
│   │   │   ├── inicializarIslands.ts # Auto-registro de islands
│   │   │   ├── registroPaneles.ts    # API de registro de paneles
│   │   │   └── inicializarPaneles.ts # Auto-registro de paneles
│   │   ├── appIslands.tsx            # Entry point (no modificar para agregar islands)
│   │   ├── package.json              # Tipos TS (@types/react, lucide-react)
│   │   ├── node_modules/             # Solo tipos, no runtime
│   │   └── tsconfig.json             # Config TS para el IDE
│   ├── Templates/pages/
│   │   └── home.php                  # Funciones PHP que renderizan islas
│   └── Config/
│       └── pages.php                 # Definiciones de rutas de paginas
│
├── Glory/                            # Framework AGNOSTICO (no modificar por proyecto)
│   ├── assets/react/
│   │   ├── src/
│   │   │   ├── main.tsx             # Entry point - mapa de componentes
│   │   │   ├── index.css            # Tailwind CSS
│   │   │   └── islands/             # Componentes genericos de Glory
│   │   │       └── ExampleIsland.tsx
│   │   ├── scripts/
│   │   │   └── prerender.ts         # Script SSG
│   │   ├── dist/
│   │   │   ├── assets/              # JS/CSS compilados
│   │   │   └── ssg/                 # HTML pre-renderizado
│   │   │       └── HomeIsland.html
│   │   └── package.json
│   └── src/
│       ├── Services/
│       │   └── ReactIslands.php     # API PHP para renderizar islas
│       └── Manager/
│           └── PageManager.php      # Gestion de paginas
│
├── TemplateGlory.php                # Template con header/footer WP
└── TemplateReact.php                # Template 100% React (sin header/footer)
```

---

## Modos de Renderizado

### Modo 1: 100% React (Fullpage)

Para landing pages y apps donde React controla todo el layout.

```php
// pages.php
PageManager::registerReactFullPages(['home', 'servicios']);
PageManager::define('home', 'home');
```

**Caracteristicas:**
- Usa `TemplateReact.php` (sin header/footer WP)
- HTML pre-renderizado para SEO
- React hidrata para interactividad

### Modo 2: Hibrido (Islands)

Para paginas donde PHP maneja el layout y React hace widgets.

```php
// En cualquier template PHP
<main>
    <?php the_content(); ?>
    
    <!-- Widget React -->
    <?php echo ReactIslands::render('ContactForm', ['email' => $email]); ?>
</main>
```

**Caracteristicas:**
- Usa `TemplateGlory.php` o tu template
- Multiples islas por pagina
- SEO garantizado por PHP

### Modo 3: PHP Puro

No uses React en absoluto. Glory funciona como tema WordPress normal.

---

## Comandos NPM

Todos los comandos se ejecutan desde la **raiz del tema** (`/glory`):

| Comando                       | Descripcion                       |
| ----------------------------- | --------------------------------- |
| `npm run dev`                 | Servidor desarrollo con HMR       |
| `npm run build`               | Compila JS/CSS + genera HTML SSG  |
| `npm run build:fast`          | Solo JS/CSS (sin SSG)             |
| `npm run prerender`           | Solo genera HTML SSG              |
| `npm run install:all`         | Instala deps en Glory y App/React |
| `npm run types:add <paquete>` | Agrega tipos TS a App/React       |

### Agregar Nuevas Librerias

Si necesitas usar una nueva libreria en tus componentes:

```bash
# Desde /glory
npm run types:add nombre-libreria
```

Esto instalara los tipos en `App/React/node_modules` para que el IDE funcione correctamente.

---

## Workflow de Desarrollo

```bash
# 1. Desde la raiz del tema
cd wp-content/themes/glory

# 2. Iniciar servidor de desarrollo
npm run dev

# 3. Editar componentes en App/React/islands/
# Los cambios se reflejan al instante (HMR)

# 4. Ver en navegador
# http://glorybuilder.local/
```

**Nota:** En modo dev, React usa `createRoot()` (renderizado completo), no hidratacion SSG.


---

## Workflow de Produccion

```bash
# 1. Compilar para produccion (desde /glory)
npm run build

# 2. Verificar archivos generados
# - Glory/assets/react/dist/assets/*.js, *.css
# - Glory/assets/react/dist/ssg/*.html

# 3. Subir al hosting via FTP/SFTP
# Subir: Glory/assets/react/dist/ -> wp-content/themes/glory/Glory/assets/react/dist/
```

### Que pasa si edito contenido en WP Admin?

- **Textos/Titulos**: Se actualizan **al instante** (PHP inyecta datos frescos)
- **Layout/Estilos**: Requiere `npm run build` (si cambiaste el componente)

---

## Eleccion de Modo por Tipo de Pagina

| Tipo de Pagina    | Modo Recomendado | Razon                               |
| ----------------- | ---------------- | ----------------------------------- |
| Home / Landing    | 100% React       | Animaciones, interactividad alta    |
| Servicios / About | 100% React       | Layouts complejos, efectos visuales |
| Blog Posts        | PHP o Hibrido    | Contenido dinamico, SEO critico     |
| Productos         | Hibrido          | Datos de BD + widgets React         |
| Contacto          | Hibrido          | Formulario React + info PHP         |
| Terminos / Legal  | PHP Puro         | Solo texto, cero complejidad        |

---

## Compatibilidad con Proyectos PHP Puro

Glory mantiene **100% compatibilidad** con proyectos que NO usan React:

1. **reactMode desactivado por defecto** - Glory funciona como tema normal
2. **Scripts condicionales** - React solo carga si hay islas registradas
3. **Sin dependencias** - No requiere Node.js ni npm en el servidor

```php
// Para proyecto SIN React, NO llamar a:
// GloryFeatures::enable('reactMode');

// Glory cargara sus scripts nativos (modales, AJAX, etc.)
```

---

## Referencia Tecnica

### PageManager::reactPage() ⭐ RECOMENDADO

Define una pagina React en una sola linea (auto-genera handler PHP):

```php
PageManager::reactPage(
    string $slug,              // Slug de la URL (ej: 'about', 'home-static')
    string $islandName,        // Nombre del Island React (ej: 'AboutIsland')
    array|callable|null $props, // Props estaticos o callback
    array $roles = []          // Roles requeridos (opcional)
);
```

**Ejemplos:**

```php
// Simple
PageManager::reactPage('about', 'AboutIsland');

// Con props estaticos
PageManager::reactPage('about', 'AboutIsland', ['siteName' => 'Mi Sitio']);

// Con callback para props dinamicos
PageManager::reactPage('home', 'HomeIsland', function($pageId) {
    return ['blocks' => get_post_meta($pageId, '_blocks', true)];
});
```

### ReactIslands::render()

```php
ReactIslands::render(
    string $islandName,      // Nombre del componente (key en main.tsx)
    array $props = [],       // Props JSON para el componente
    string $fallbackContent = '', // HTML fallback (auto-detecta SSG)
    array $containerAttrs = []   // Atributos adicionales del div
): string
```

### PageManager::registerReactFullPages()

Registra slugs como paginas React Fullpage (sin header/footer WP):

```php
PageManager::registerReactFullPages(['home', 'servicios', 'contacto']);
```

> **Nota:** `reactPage()` hace esto automaticamente.

### PageManager::define()

Define una pagina gestionada con archivo PHP manual:

```php
PageManager::define(
    string $slug,        // Slug de la pagina
    ?string $handler,    // Funcion de renderizado
    ?string $template,   // Plantilla (opcional)
    array $roles         // Roles requeridos (opcional)
);
```

---

Consulta `SSR_ARCHITECTURE.md` para detalles tecnicos sobre las limitaciones y mitigaciones del SSG.
Consulta `PAGE_BUILDER_PLAN.md` para documentacion del sistema de bloques editables.
