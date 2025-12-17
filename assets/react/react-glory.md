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

### Paso 1: Crear el Componente

Crea tu componente en `App/React/islands/MiPagina.tsx`:

```tsx
import { useState } from 'react';

interface MiPaginaProps {
    titulo?: string;
    contenido?: string;
}

export function MiPagina({ 
    titulo = 'Mi Pagina', 
    contenido = 'Contenido por defecto' 
}: MiPaginaProps): JSX.Element {
    const [contador, setContador] = useState(0);

    return (
        <div className="min-h-screen bg-[#050505] text-white">
            <div className="max-w-[1200px] mx-auto px-6 py-20">
                <h1 className="text-5xl font-bold mb-4">{titulo}</h1>
                <p className="text-gray-400 mb-8">{contenido}</p>
                <button 
                    onClick={() => setContador(c => c + 1)}
                    className="px-6 py-3 bg-white text-black rounded-lg"
                >
                    Clicks: {contador}
                </button>
            </div>
        </div>
    );
}

export default MiPagina;
```

### Paso 2: Registrar en main.tsx

Edita `Glory/assets/react/src/main.tsx` para importar y registrar tu componente:

```tsx
// COMPONENTES APP (Especificos del proyecto)
import {MiPagina} from '@app/islands/MiPagina';

const islandComponents = {
    ExampleIsland: ExampleIsland,
    HomeIsland: HomeIsland,
    MiPagina: MiPagina,  // <-- Agregar aqui
};
```

### Paso 3: Crear la Funcion PHP

Crea `App/Templates/pages/mi-pagina.php`:

```php
<?php

use Glory\Services\ReactIslands;

function miPagina()
{
    // Datos dinamicos desde WordPress
    $titulo = get_the_title() ?: 'Mi Pagina';
    $contenido = get_bloginfo('description');

    // Renderizar isla React
    echo ReactIslands::render('MiPagina', [
        'titulo' => $titulo,
        'contenido' => $contenido
    ]);
}
```

### Paso 4: Definir en pages.php

Edita `App/Config/pages.php`:

```php
// Registrar como React Fullpage (100% React, sin header/footer WP)
PageManager::registerReactFullPages(['mi-pagina']);

// Definir la pagina con su funcion
PageManager::define('mi-pagina', 'miPagina');
```

### Paso 5: Agregar Mock Props (Opcional - para SSG)

Edita `Glory/assets/react/scripts/prerender.ts`:

```typescript
const mockProps = {
    // ... otros
    MiPagina: {
        titulo: 'Mi Pagina',
        contenido: 'Contenido de ejemplo'
    }
};
```

### Paso 6: Compilar

```bash
cd Glory/assets/react
npm run build
```

---

## Estructura de Archivos

```
glory/
├── App/                              # Contenido ESPECIFICO del proyecto
│   ├── React/
│   │   ├── islands/                  # Componentes React del proyecto
│   │   │   └── HomeIsland.tsx
│   │   ├── package.json              # Tipos TS (@types/react, lucide-react)
│   │   ├── node_modules/             # Solo tipos, no runtime
│   │   └── tsconfig.json             # Config TS para el IDE
│   ├── Templates/pages/
│   │   └── home.php                  # Funciones PHP que renderizan islas
│   └── Config/
│       └── pages.php                 # Definiciones de paginas
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
# 1. Compilar para produccion
cd Glory/assets/react
npm run build

# 2. Verificar archivos generados
# - dist/assets/*.js, *.css
# - dist/ssg/*.html

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

### PageManager::define()

Define una pagina gestionada:

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
