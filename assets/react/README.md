# Glory React Islands

Sistema de integracion React para WordPress usando el patron "Islands Architecture".

## Estructura de Archivos

```
Glory/
  assets/
    react/                          <- Proyecto Vite (configuracion + componentes genericos)
      src/
        islands/                    <- Componentes genericos reutilizables
          ExampleIsland.tsx
        main.tsx                    <- Entry point (registra todos los componentes)
        index.css                   <- Tailwind CSS
      package.json
      vite.config.ts
      tsconfig.json

App/
  React/                            <- Componentes especificos del proyecto
    components/
      ui/                           <- Componentes UI atomicos reutilizables
        Badge.tsx                   <- Componente de etiqueta
        Button.tsx                  <- Boton con variantes (primary, outline, ghost)
        index.ts                    <- Barrel export
      sections/                     <- Secciones de pagina reutilizables
        TopBanner.tsx               <- Banner superior promocional
        Header.tsx                  <- Cabecera con navegacion
        HeroSection.tsx             <- Seccion hero con titulo y CTAs
        Footer.tsx                  <- Pie de pagina
        FeatureList.tsx             <- Lista de caracteristicas con iconos
        GridCards.tsx               <- Cuadricula de tarjetas
        QuoteSection.tsx            <- Seccion de cita destacada
        index.ts                    <- Barrel export
    islands/
      HomeIsland.tsx                <- Ejemplo: pagina home completa
    node_modules/                   <- Symlink a Glory/assets/react/node_modules (para IDE)
    tsconfig.json                   <- Config para que el IDE resuelva tipos
  Templates/
    pages/
      home.php                      <- Usa ReactIslands::render('HomeIsland')
```

## Configuracion Inicial

### 1. Instalar dependencias

```bash
cd Glory/assets/react
npm install
```

### 2. Crear symlink para tipos en App/React (solo una vez)

Para que el IDE (VSCode) resuelva correctamente los tipos de React en `App/React/`, 
necesitas crear un symlink de `node_modules`:

**Windows (PowerShell como Administrador):**
```powershell
cmd /c mklink /D "ruta\al\tema\App\React\node_modules" "ruta\al\tema\Glory\assets\react\node_modules"
```

**Linux/Mac:**
```bash
ln -s ../../Glory/assets/react/node_modules App/React/node_modules
```

Esto permite que TypeScript encuentre los tipos sin duplicar las dependencias.

## Comandos

```bash
# Desarrollo (HMR activo)
cd Glory/assets/react
npm run dev

# Produccion (genera bundles optimizados)
npm run build
```

## Componentes Reutilizables

El proyecto organiza los componentes en dos categorias:

### Componentes UI (`App/React/components/ui/`)

Componentes atomicos de interfaz que se pueden usar en cualquier parte de la aplicacion.

```tsx
// Importar desde el barrel export
import { Button, Badge } from '../components/ui';

// Uso de Button
<Button variant="primary" href="/contacto">Contactar</Button>
<Button variant="outline" onClick={handleClick}>Cancelar</Button>
<Button variant="ghost" icon={ArrowRight}>Ver mas</Button>

// Uso de Badge
<Badge>Nuevo</Badge>
<Badge className="bg-green-100 text-green-800">Activo</Badge>
```

**Variantes de Button:**
- `primary`: Fondo oscuro, texto claro (default)
- `outline`: Borde con fondo transparente
- `ghost`: Sin borde ni fondo

### Componentes de Seccion (`App/React/components/sections/`)

Secciones completas de pagina, configurables via props.

```tsx
import { Header, Footer, HeroSection, TopBanner, GridCards, QuoteSection, FeatureList } from '../components/sections';

// TopBanner - Banner promocional superior
<TopBanner text="Oferta especial" linkText="Ver mas" linkHref="/oferta" />

// Header - Con navegacion responsive
<Header 
    logoText="MiMarca"
    navItems={[{label: 'Inicio', href: '/'}, {label: 'Servicios', href: '#servicios'}]}
    ctaText="Contactar"
    ctaHref="/contacto"
/>

// HeroSection - Seccion hero con indicadores de estado
<HeroSection
    title={<>Tu titulo con <span className="text-gray-400">estilo</span></>}
    subtitle="Descripcion del servicio"
    primaryCta={{text: 'Empezar', href: '/signup'}}
    secondaryCta={{text: 'Saber mas', href: '#info'}}
    statusIndicators={[
        {label: 'Paso 1', isActive: true, isAnimated: true},
        {label: 'Paso 2', isActive: false}
    ]}
/>

// Footer - Con columnas de enlaces
<Footer 
    columns={[
        {title: 'Producto', links: [{label: 'Features', href: '#'}]},
        {title: 'Legal', links: [{label: 'Privacidad', href: '/privacy'}]}
    ]}
    copyrightText="© 2025 MiEmpresa"
/>
```

## Crear un Nuevo Componente React

### 1. Crear el archivo del componente

**Componente generico (reutilizable):** `Glory/assets/react/src/islands/MiComponente.tsx`

**Componente especifico del proyecto:** `App/React/islands/MiComponente.tsx`

```tsx
// App/React/islands/ContactForm.tsx
import { useState } from 'react';

interface ContactFormProps {
  title?: string;
  email?: string;
}

export function ContactForm({ title = 'Contacto', email }: ContactFormProps): JSX.Element {
  const [name, setName] = useState('');
  
  return (
    <div className="p-6 bg-white rounded-lg shadow">
      <h2 className="text-2xl font-bold mb-4">{title}</h2>
      <input 
        type="text"
        value={name}
        onChange={(e) => setName(e.target.value)}
        className="border rounded px-4 py-2 w-full"
        placeholder="Tu nombre"
      />
    </div>
  );
}
```

### 2. Registrar el componente en main.tsx

```tsx
// Glory/assets/react/src/main.tsx

// Importar el componente
import {ContactForm} from '@app/islands/ContactForm';

// Agregarlo al mapa de componentes
const islandComponents = {
    ExampleIsland: ExampleIsland,
    HomeIsland: HomeIsland,
    ContactForm: ContactForm,  // <- Agregar aqui
};
```

### 3. Usar en PHP

```php
<?php
use Glory\Services\ReactIslands;

// Renderizar el componente
echo ReactIslands::render('ContactForm', [
    'title' => 'Escribenos',
    'email' => 'hola@ejemplo.com'
]);
```

## Crear una Pagina Completa en React

Para paginas que controlan todo el layout (con su propio header/footer):

### 1. Crear el componente de pagina

```tsx
// App/React/islands/MiPagina.tsx
export function MiPagina(): JSX.Element {
  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header propio */}
      <header className="bg-white shadow">...</header>
      
      {/* Contenido */}
      <main>...</main>
      
      {/* Footer propio */}
      <footer className="bg-gray-800">...</footer>
    </div>
  );
}
```

### 2. Registrar en main.tsx

```tsx
import {MiPagina} from '@app/islands/MiPagina';

const islandComponents = {
    // ...
    MiPagina: MiPagina,
};
```

### 3. Crear la funcion PHP

```php
<?php
// App/Templates/pages/mi-pagina.php
use Glory\Services\ReactIslands;

function miPagina()
{
    echo ReactIslands::render('MiPagina');
}
```

### 4. Registrar en PageManager

```php
<?php
// App/Config/pages.php
PageManager::define('mi-pagina', 'miPagina');
```

### 5. Configurar como fullpage (opcional)

Para que la pagina NO use el header/footer de WordPress, edita `TemplateGlory.php`:

```php
$isReactFullpage = ($slug === 'home' || $slug === 'mi-pagina');
```

## Pasar Props desde PHP

```php
echo ReactIslands::render('MiComponente', [
    'titulo' => 'Hola Mundo',
    'items' => ['uno', 'dos', 'tres'],
    'config' => [
        'mostrarIconos' => true,
        'colorPrimario' => '#3b82f6'
    ]
]);
```

En React:

```tsx
interface MiComponenteProps {
  titulo: string;
  items: string[];
  config: {
    mostrarIconos: boolean;
    colorPrimario: string;
  };
}

export function MiComponente({ titulo, items, config }: MiComponenteProps): JSX.Element {
  // usar props...
}
```

## Tailwind CSS

Las clases de Tailwind se generan automaticamente al escanear:
- `Glory/assets/react/src/**/*.tsx`
- `App/React/**/*.tsx`

Si agregas una nueva carpeta con componentes, actualiza `src/index.css`:

```css
@import 'tailwindcss';

@source "../src/**/*.{js,ts,jsx,tsx}";
@source "../../../../App/React/**/*.{js,ts,jsx,tsx}";
@source "../../../../MiOtraCarpeta/**/*.{js,ts,jsx,tsx}";  /* Nueva */
```

## Sistema de CSS Variables

El proyecto usa un sistema de tokens de diseno centralizado en `App/Assets/css/init.css`.
Esto permite cambiar colores globalmente desde un solo archivo.

### Variables Disponibles

**Colores de Texto:**
- `--color-text-primary`: Texto principal (#292524)
- `--color-text-secondary`: Texto secundario (#57534e)
- `--color-text-muted`: Texto discreto (#79716b)
- `--color-text-subtle`: Texto muy suave (#a8a29e)

**Colores de Fondo:**
- `--color-bg-primary`: Fondo principal (#f8f8f6)
- `--color-bg-secondary`: Fondo alterno (#f0efeb)
- `--color-bg-tertiary`: Fondo de UI (#f5f5f4)
- `--color-bg-surface`: Cards y contenedores (#ffffff)
- `--color-bg-elevated`: Ligeramente elevado (#fcfcfc)

**Colores de Borde:**
- `--color-border-primary`: Bordes principales (#e5e5e0)
- `--color-border-secondary`: Bordes secundarios (#e7e5e4)
- `--color-border-subtle`: Bordes sutiles (#f5f5f4)

**Colores de Acento:**
- `--color-accent-primary`: Botones primarios (#292524)
- `--color-accent-hover`: Hover de botones (#1c1917)

### Uso en Componentes

```tsx
// Usando style prop (recomendado para variables)
<div style={{
    backgroundColor: 'var(--color-bg-surface)',
    color: 'var(--color-text-primary)',
    borderColor: 'var(--color-border-primary)'
}}>
    Contenido
</div>
```

### Cambiar Tema Global

Para cambiar un color en todo el proyecto, edita `App/Assets/css/init.css`:

```css
:root {
    --color-accent-primary: #3b82f6;  /* Cambiar de gris a azul */
}
```

## Desarrollo vs Produccion

| Aspecto      | Desarrollo     | Produccion                |
| ------------ | -------------- | ------------------------- |
| Comando      | `npm run dev`  | `npm run build`           |
| HMR          | Si             | No                        |
| Minificacion | No             | Si                        |
| Source Maps  | Si             | No                        |
| Assets       | localhost:5173 | /Glory/assets/react/dist/ |

La clase `ReactIslands.php` detecta automaticamente si Vite dev server esta corriendo y carga los assets correspondientes.

## Troubleshooting

### Los estilos no se aplican
1. Verifica que `npm run build` se ejecuto despues de agregar nuevos componentes
2. Verifica que la carpeta del componente esta en `@source` de `index.css`

### El componente no se monta
1. Verifica que el componente esta registrado en `main.tsx`
2. Verifica la consola del navegador para errores
3. Verifica que el nombre en `ReactIslands::render('Nombre')` coincide con la clave en `islandComponents`

### Errores de TypeScript en el IDE (Cannot find module 'react')
1. Verifica que el symlink `App/React/node_modules` existe y apunta a `Glory/assets/react/node_modules`
2. Si no existe, crealo siguiendo las instrucciones de "Configuracion Inicial"
3. Ejecuta "TypeScript: Restart TS Server" en VSCode (Ctrl+Shift+P)
4. Si persiste, cierra y abre VSCode

### Error "preamble" en modo desarrollo
Este error ocurre si el cliente de Vite no se carga correctamente.
Solucion: Verifica que `ReactIslands.php` incluye el script de React Refresh preamble.

### Pagina en blanco
1. Verifica los logs de PHP (wp-content/debug.log)
2. Activa WP_DEBUG en wp-config.php
3. Revisa si hay errores de sintaxis en los archivos PHP
