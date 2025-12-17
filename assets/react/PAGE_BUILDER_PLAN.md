# Glory Page Builder

**Fecha:** 17 Diciembre 2025  
**Estado:** ✅ V1 COMPLETADA  
**Version:** 1.1 (Flujo Simplificado)

---

## 1. Introduccion

El **Glory Page Builder** es un sistema modular para crear y editar paginas con bloques visuales. Esta completamente integrado en el framework Glory y es **100% opcional**.

### Caracteristicas

- Editar contenido de paginas React sin tocar codigo
- Reordenar secciones (arriba/abajo)
- Agregar y eliminar secciones
- Guardar cambios en WordPress via REST API
- Completamente opcional por pagina
- Solo **3 pasos** para crear una pagina nueva

---

## 2. Crear una Pagina React (Solo 3 Pasos)

### Paso 1: Crear el Island

Crear componente en `App/React/islands/MiPaginaIsland.tsx`:

```tsx
import { PageLayout } from '@/pageBuilder';

export function MiPaginaIsland({ siteName }) {
    return (
        <PageLayout siteName={siteName} usePageBuilder={false}>
            <h1>Mi Pagina</h1>
            <p>Contenido aqui</p>
        </PageLayout>
    );
}

export default MiPaginaIsland;
```

### Paso 2: Registrar en appIslands.tsx

Agregar import y registro en `App/React/appIslands.tsx`:

```tsx
import { MiPaginaIsland } from './islands/MiPaginaIsland';

export const appIslands = {
    // ... otros islands
    MiPaginaIsland: MiPaginaIsland,
};
```

### Paso 3: Agregar en pages.php

Una sola linea en `App/Config/pages.php`:

```php
PageManager::reactPage('mi-pagina', 'MiPaginaIsland', [
    'siteName' => 'Mi Sitio'
]);
```

**¡Listo!** La pagina estara disponible en `/mi-pagina/`

---

## 3. Opciones de reactPage()

### 3.1 Sin Props

```php
PageManager::reactPage('about', 'AboutIsland');
```

### 3.2 Con Props Estaticos

```php
PageManager::reactPage('about', 'AboutIsland', [
    'siteName' => 'Mi Sitio',
    'year' => 2025
]);
```

### 3.3 Con Props Dinamicos (Callback)

Para props que dependen de WordPress (como bloques del Page Builder):

```php
PageManager::reactPage('home', 'HomeIsland', function($pageId) {
    $blocksJson = get_post_meta($pageId, '_glory_page_blocks', true);
    $isAdmin = current_user_can('edit_pages');
    
    return [
        'blocks' => $blocksJson ? json_decode($blocksJson, true) : null,
        'isAdmin' => $isAdmin,
        'saveEndpoint' => $isAdmin ? rest_url('glory/v1/page-blocks/' . $pageId) : null,
        'restNonce' => $isAdmin ? wp_create_nonce('wp_rest') : null
    ];
});
```

### 3.4 Con Restriccion de Roles

```php
PageManager::reactPage('admin-panel', 'AdminIsland', [], ['administrator']);
```

---

## 4. Pagina CON Page Builder vs SIN Page Builder

### CON Page Builder (editable visualmente)

```tsx
// Island
import { PageLayout } from '@/pageBuilder';

export function HomeIsland({ blocks, isAdmin, saveEndpoint, restNonce }) {
    return (
        <PageLayout
            siteName="Glory"
            blocks={blocks}
            isAdmin={isAdmin}
            saveEndpoint={saveEndpoint}
            restNonce={restNonce}
        />
    );
}
```

```php
// pages.php - usa callback para props dinamicos
PageManager::reactPage('home', 'HomeIsland', function($pageId) {
    // ... obtener bloques de post_meta, nonce, etc.
});
```

### SIN Page Builder (contenido estatico)

```tsx
// Island
import { PageLayout } from '@/pageBuilder';

export function AboutIsland({ siteName }) {
    return (
        <PageLayout siteName={siteName} usePageBuilder={false}>
            <h1>Sobre Nosotros</h1>
            <p>Contenido estatico aqui</p>
        </PageLayout>
    );
}
```

```php
// pages.php - props simples
PageManager::reactPage('about', 'AboutIsland', ['siteName' => 'Mi Sitio']);
```

---

## 5. Props de PageLayout

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

---

## 6. Crear Bloques Personalizados

### 6.1 Estructura de un Bloque

```tsx
// App/React/blocks/MiBloque.tsx

import type { BlockComponentProps, BlockDefinition } from '@/pageBuilder';

interface MiBloqueProps {
    titulo: string;
    descripcion: string;
}

export function MiBloque({ data }: BlockComponentProps<MiBloqueProps>) {
    return (
        <section id="mi-bloque">
            <h2>{data.titulo}</h2>
            <p>{data.descripcion}</p>
        </section>
    );
}

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

### 6.2 Registrar Bloques

En `App/React/blocks/index.ts`:

```tsx
import { BlockRegistry } from '@/pageBuilder';
import { miBloqueDefinition } from './MiBloque';

export function registerAppBlocks() {
    BlockRegistry.register(miBloqueDefinition);
}
```

### 6.3 Tipos de Campo Editables

| Tipo       | Descripcion                        |
| ---------- | ---------------------------------- |
| `text`     | Input de texto simple              |
| `textarea` | Area de texto multilinea           |
| `url`      | Input para URLs                    |
| `number`   | Input numerico                     |
| `select`   | Dropdown con opciones              |
| `icon`     | Selector de icono Lucide           |
| `array`    | Lista de items con campos anidados |

---

## 7. Arquitectura

### 7.1 Separacion Glory / App

```
Glory/assets/react/src/pageBuilder/   <- AGNOSTICO (no tocar)
├── types.ts
├── BlockRegistry.ts
├── BlockRenderer.tsx
├── BlockEditorModal.tsx
├── components/
│   ├── PageBuilder.tsx
│   ├── PageBuilderToolbar.tsx
│   ├── EditModeToggle.tsx
│   └── AddBlockPanel.tsx
├── layouts/
│   └── PageLayout.tsx
└── index.ts

App/React/                            <- ESPECIFICO DEL PROYECTO
├── blocks/
│   ├── HeroBlock.tsx
│   ├── FeaturesBlock.tsx
│   └── index.ts
├── islands/
│   ├── HomeIsland.tsx
│   └── AboutIsland.tsx
└── appIslands.tsx
```

### 7.2 Flujo de Datos

```
App/Config/pages.php
    ↓ reactPage()
PageManager (auto-genera handler)
    ↓
ReactIslands::render()
    ↓
React Island Component
    ↓
PageLayout (nav + contenido + footer)
    ↓
PageBuilder (si hay bloques)
```

---

## 8. REST API

| Metodo | Endpoint                             | Descripcion     |
| ------ | ------------------------------------ | --------------- |
| GET    | `/wp-json/glory/v1/page-blocks/{id}` | Obtener bloques |
| POST   | `/wp-json/glory/v1/page-blocks/{id}` | Guardar bloques |

---

## 9. Comparacion de Metodos

| Metodo               | Uso                   | Template PHP  |
| -------------------- | --------------------- | ------------- |
| `reactPage()`        | Paginas React simples | Auto-generado |
| `define()`           | Logica PHP compleja   | Manual        |
| `defineWithParent()` | Paginas hijas         | Manual        |

**Recomendacion:** Usa `reactPage()` siempre que sea posible.

---

## 10. Troubleshooting

### El slug debe ser minusculas con guiones

```php
// ❌ Incorrecto
PageManager::reactPage('homeStatic', ...);  // Mayuscula

// ✅ Correcto
PageManager::reactPage('home-static', ...); // Solo minusculas y guiones
```

### Island no carga

Verificar que el island esta registrado en `appIslands.tsx`.

### Bloques no registrados (warning en build)

Es normal durante SSG. Los bloques se registran en el cliente.
