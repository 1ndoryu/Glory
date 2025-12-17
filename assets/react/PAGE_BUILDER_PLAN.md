# Glory Page Builder

**Fecha:** 17 Diciembre 2025  
**Estado:** ✅ V1 COMPLETADA  
**Version:** 1.0 (Simple - Bloques Lineales)

---

## 1. Introduccion

El **Glory Page Builder** es un sistema modular para crear y editar paginas con bloques visuales. Esta completamente integrado en el framework Glory y es **100% opcional**.

### Caracteristicas

- Editar contenido de paginas React sin tocar codigo
- Reordenar secciones (arriba/abajo)
- Agregar y eliminar secciones
- Guardar cambios en WordPress via REST API
- Completamente opcional por pagina
- Componentes reutilizables y agnosticos

---

## 2. Guia de Uso Rapido

### 2.1 Usando PageLayout (Recomendado)

`PageLayout` es el componente que envuelve **todas** las paginas. Incluye nav, footer, y opcionalmente PageBuilder.

```tsx
import { PageLayout } from '@/pageBuilder';

function MiPagina({ blocks, isAdmin, saveEndpoint, restNonce }) {
    return (
        <PageLayout
            siteName="Mi Sitio"
            navLinks={[
                { text: 'Inicio', href: '/' },
                { text: 'Precios', href: '#pricing' }
            ]}
            socialLinks={[
                { type: 'twitter', href: '#' },
                { type: 'github', href: '#' }
            ]}
            blocks={blocks}
            isAdmin={isAdmin}
            saveEndpoint={saveEndpoint}
            restNonce={restNonce}
        />
    );
}
```

Esto genera automaticamente:
- Navigation con logo y links
- Page Builder con todos los controles de edicion
- Footer con copyright y links sociales

### 2.2 Pagina Sin Page Builder

```tsx
import { PageLayout } from '@/pageBuilder';

function PaginaSimple() {
    return (
        <PageLayout siteName="Mi Sitio" usePageBuilder={false}>
            <h1>Contenido personalizado</h1>
            <p>Esta pagina no usa bloques.</p>
        </PageLayout>
    );
}
```

### 2.3 Usando PageBuilder Directamente

Si necesitas control total del layout, usa `PageBuilder`:

```tsx
import { PageBuilder } from '@/pageBuilder';

function MiLayoutCustom({ blocks, isAdmin, saveEndpoint, restNonce }) {
    return (
        <div>
            <MyCustomHeader />
            
            <PageBuilder
                blocks={blocks}
                isAdmin={isAdmin}
                saveEndpoint={saveEndpoint}
                restNonce={restNonce}
            />
            
            <MyCustomFooter />
        </div>
    );
}
```

### 2.4 Props de PageLayout

| Prop             | Tipo           | Default   | Descripcion           |
| ---------------- | -------------- | --------- | --------------------- |
| `siteName`       | `string`       | "Glory"   | Nombre del sitio      |
| `logoUrl`        | `string`       | -         | URL del logo          |
| `navLinks`       | `NavLink[]`    | `[]`      | Links de navegacion   |
| `navCtaText`     | `string`       | "Login"   | Texto del boton CTA   |
| `navCtaUrl`      | `string`       | "#"       | URL del boton CTA     |
| `hideNav`        | `boolean`      | `false`   | Ocultar navegacion    |
| `copyright`      | `string`       | auto      | Texto de copyright    |
| `socialLinks`    | `SocialLink[]` | `[]`      | Links sociales        |
| `hideFooter`     | `boolean`      | `false`   | Ocultar footer        |
| `usePageBuilder` | `boolean`      | auto      | Usar Page Builder     |
| `blocks`         | `BlockData[]`  | -         | Bloques iniciales     |
| `isAdmin`        | `boolean`      | `false`   | Si puede editar       |
| `saveEndpoint`   | `string`       | -         | URL REST para guardar |
| `restNonce`      | `string`       | -         | Nonce de WordPress    |
| `bgColor`        | `string`       | "#050505" | Color de fondo        |

### 2.5 Props de PageBuilder (uso directo)

| Prop                | Tipo          | Default           | Descripcion              |
| ------------------- | ------------- | ----------------- | ------------------------ |
| `blocks`            | `BlockData[]` | `[]`              | Bloques iniciales        |
| `isAdmin`           | `boolean`     | `false`           | Si puede editar          |
| `saveEndpoint`      | `string`      | `null`            | URL REST para guardar    |
| `restNonce`         | `string`      | `null`            | Nonce de WordPress       |
| `disabled`          | `boolean`     | `false`           | Desactivar completamente |
| `allowedBlockTypes` | `string[]`    | todos             | Limitar tipos de bloque  |
| `editButtonText`    | `string`      | "Editar Pagina"   | Texto del boton          |
| `toolbarTitle`      | `string`      | "Editando Pagina" | Titulo del toolbar       |

---

## 3. Configuracion PHP

### 3.1 Template Basico

```php
use Glory\Services\ReactIslands;

function miPagina() {
    $pageId = get_the_ID() ?: 0;
    
    // Cargar bloques guardados (si existen)
    $blocksJson = get_post_meta($pageId, '_glory_page_blocks', true);
    $blocks = $blocksJson ? json_decode($blocksJson, true) : null;
    
    // Verificar permisos
    $isAdmin = current_user_can('edit_pages');
    
    // Endpoint y nonce (solo para admins)
    $saveEndpoint = $isAdmin ? rest_url('glory/v1/page-blocks/' . $pageId) : null;
    $restNonce = $isAdmin ? wp_create_nonce('wp_rest') : null;
    
    echo ReactIslands::render('MiIsland', [
        'blocks' => $blocks,
        'isAdmin' => $isAdmin,
        'saveEndpoint' => $saveEndpoint,
        'restNonce' => $restNonce
    ]);
}
```

### 3.2 Pagina Sin Page Builder

```php
// Simplemente no pasar props de bloques
function paginaSimple() {
    echo ReactIslands::render('MiIsland', [
        'titulo' => 'Mi Pagina',
        'contenido' => 'Sin bloques'
    ]);
}
```

### 3.3 Desactivar REST API

En `App/Config/control.php`:

```php
GloryFeatures::disable('pageBuilder');
```

---

## 4. Crear Bloques Personalizados

### 4.1 Estructura de un Bloque

Cada bloque se define en `App/React/blocks/`:

```tsx
// App/React/blocks/MiBloque.tsx

import type { BlockComponentProps, BlockDefinition } from '@/pageBuilder';

// Props del bloque
interface MiBloqueProps {
    titulo: string;
    descripcion: string;
}

// Componente
export function MiBloque({ data }: BlockComponentProps<MiBloqueProps>) {
    return (
        <section id="mi-bloque">
            <h2>{data.titulo}</h2>
            <p>{data.descripcion}</p>
        </section>
    );
}

// Definicion para el registro
export const miBloqueDefinition: BlockDefinition<MiBloqueProps> = {
    type: 'miBloque',
    label: 'Mi Bloque',
    icon: 'Star',
    component: MiBloque,
    defaultProps: {
        titulo: 'Titulo por defecto',
        descripcion: 'Descripcion por defecto'
    },
    editableFields: [
        { key: 'titulo', label: 'Titulo', type: 'text' },
        { key: 'descripcion', label: 'Descripcion', type: 'textarea' }
    ]
};
```

### 4.2 Registrar Bloques

En `App/React/blocks/index.ts`:

```tsx
import { BlockRegistry } from '@/pageBuilder';
import { miBloqueDefinition } from './MiBloque';

export function registerAppBlocks() {
    BlockRegistry.register(miBloqueDefinition);
}
```

### 4.3 Tipos de Campo Editables

| Tipo       | Descripcion                        |
| ---------- | ---------------------------------- |
| `text`     | Input de texto simple              |
| `textarea` | Area de texto multilinea           |
| `url`      | Input para URLs                    |
| `number`   | Input numerico                     |
| `select`   | Dropdown con opciones              |
| `icon`     | Selector de icono Lucide           |
| `array`    | Lista de items con campos anidados |

Ejemplo de campo array:

```tsx
editableFields: [
    {
        key: 'items',
        label: 'Items',
        type: 'array',
        itemFields: [
            { key: 'titulo', label: 'Titulo', type: 'text' },
            { key: 'descripcion', label: 'Descripcion', type: 'textarea' }
        ]
    }
]
```

---

## 5. Arquitectura

### 5.1 Separacion Glory / App

```
Glory/assets/react/src/pageBuilder/   <- AGNOSTICO (no tocar)
├── types.ts                          # Tipos TypeScript
├── BlockRegistry.ts                  # Registro global
├── BlockRenderer.tsx                 # Renderizador
├── BlockEditorModal.tsx              # Modal de edicion
├── components/
│   ├── PageBuilder.tsx               # Componente de bloques
│   ├── PageBuilderToolbar.tsx        # Toolbar de edicion
│   ├── EditModeToggle.tsx            # Boton flotante
│   └── AddBlockPanel.tsx             # Panel agregar bloque
├── layouts/
│   └── PageLayout.tsx                # Layout con nav/footer/builder
└── index.ts                          # Exportaciones

App/React/                            <- ESPECIFICO DEL PROYECTO
├── blocks/
│   ├── HeroBlock.tsx
│   ├── FeaturesBlock.tsx
│   ├── PricingBlock.tsx
│   └── index.ts                      # Registro de bloques
└── islands/
    └── HomeIsland.tsx                # Solo configura PageLayout
```

### 5.2 Flujo de Datos

```
WordPress (post_meta)
    ↓ JSON
PHP (template)
    ↓ props
React Island
    ↓
PageBuilder Component
    ↓
BlockRenderer ←── BlockRegistry
    ↓
[Bloque1] [Bloque2] [Bloque3]
    ↓ (modo edicion)
Cambios → REST API → WordPress
```

---

## 6. REST API

### 6.1 Endpoints

| Metodo | Endpoint                             | Descripcion     |
| ------ | ------------------------------------ | --------------- |
| GET    | `/wp-json/glory/v1/page-blocks/{id}` | Obtener bloques |
| POST   | `/wp-json/glory/v1/page-blocks/{id}` | Guardar bloques |

### 6.2 Permisos

- **GET**: Publico (para SSR)
- **POST**: Requiere `edit_post` en la pagina

### 6.3 Formato de Datos

```json
{
    "blocks": [
        {
            "id": "hero-1",
            "type": "hero",
            "props": { ... }
        }
    ]
}
```

---

## 7. UI de Edicion

### 7.1 Modo Vista

- Usuario normal ve la pagina sin controles
- Admin ve boton flotante "Editar Pagina"

### 7.2 Modo Edicion

- Toolbar fijo arriba con "Salir" y "Guardar"
- Bloques con borde punteado
- Click en bloque lo selecciona
- Controles: Mover ↑↓, Editar, Eliminar
- Panel inferior para agregar bloques

### 7.3 Modal de Edicion

- Abre al hacer click en "Editar"
- Campos dinamicos segun `editableFields`
- Botones Cancelar y Guardar

---

## 8. Notas Tecnicas

### 8.1 SSR Compatible

El Page Builder funciona con Server-Side Rendering. Los bloques se pre-renderizan en el servidor.

### 8.2 Registro de Bloques en Cliente

Los bloques se registran en el cliente, no en SSR. Por eso aparecen warnings de "bloque no registrado" durante el build - es normal.

### 8.3 Validacion de Bloques

Cada bloque en el REST API se valida:
- Debe tener `id` (string)
- Debe tener `type` (string)
- Debe tener `props` (object)

---

## 9. Fases de Implementacion

### Fase 1: Infraestructura ✅
- types.ts, BlockRegistry.ts, BlockRenderer.tsx

### Fase 2: Bloques App ✅
- HeroBlock, FeaturesBlock, PricingBlock

### Fase 3: Modo Edicion ✅
- PageBuilder, Toolbar, Modal

### Fase 4: Persistencia ✅
- REST API, Guardado con nonce

### Fase 5: Refactorizacion ✅
- Componentes reutilizables en Glory
- HomeIsland simplificado

---

## 10. Escalabilidad Futura (V2+)

- Sistema de Filas/Columnas
- Drag & Drop con @dnd-kit
- Bloques Anidados
- Templates predefinidos
