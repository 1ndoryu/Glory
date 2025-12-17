# Glory React Architecture

Glory soporta dos modelos de integración React:

1.  **Next.js SSR (Recomendado)**: Arquitectura Headless con WordPress como API.
2.  **Islands Architecture (Legacy)**: Componentes React incrustados en temas clásicos de WordPress.

## 1. Next.js SSR (Headless)

Nuevo estándar para desarrollos Glory. Separa el frontend (Next.js) del backend (WordPress), conectados vía REST API.

### Flujo de Datos

```
+-------------------+         +-------------------+
|    WordPress      |         |     Next.js       |
|    (Headless)     |         |     (Frontend)    |
+-------------------+         +-------------------+
|                   |         |                   |
| - Admin Panel     |  REST   | - SSR Pages       |
| - Posts/Pages     | ------> | - React Components|
| - Media Library   |   API   | - Routing         |
| - Users           |         | - Estilos         |
|                   |         |                   |
+-------------------+         +-------------------+
        |                              |
        v                              v
   glorybuilder.local           localhost:3000
   (API & Admin)                (Sitio Público)
```

### Configuración

El servicio `NextjsApiService` en WordPress se encarga de:
1.  **Habilitar CORS**: Permite peticiones desde Next.js (`http://localhost:3000`).
2.  **Endpoints Personalizados**: API optimizada en `/wp-json/glory/v1/`.

Activación en `App/Config/control.php`:

```php
// Habilitar modo React
GloryFeatures::applyReactMode();

// Inicializar API Next.js
use Glory\Services\NextjsApiService;
NextjsApiService::inicializar();
```

### Estructura del Proyecto Frontend (`frontend/`)

```
frontend/
├── app/                    # App Router (Next.js)
│   ├── layout.tsx         # Layout global
│   └── page.tsx           # Páginas SSR
├── lib/                   
│   └── wordpress.ts       # Cliente API WordPress
└── globals.css            # Sistema de diseño CSS
```

---

## 2. Islands Architecture (Legacy)

Modelo anterior donde React vive dentro del tema PHP de WordPress.

### Flujo de Datos

```
WordPress (PHP)
    |
    v
ReactIslands.php  ---> Renderiza <div data-island="...">
    |
    v
main.tsx  ---> Hidrata componentes React
```

### Estructura

- `Glory/assets/react`: Proyecto base Vite + React.
- `App/React/islands`: Componentes principales.
- `App/Content/reactContent.php`: Inyección de datos PHP -> JS.

### Comandos (Legacy)

```bash
cd Glory/assets/react
npm run dev   # Desarrollo HMR
npm run build # Producción
```

---

## Migración

Para nuevos proyectos, se recomienda usar **Next.js SSR**. Para mantener sitios existentes, **Islands Architecture** sigue siendo soportado.

### Comparativa

| Característica   | Next.js SSR (Nuevo)    | Islands (Legacy)         |
| ---------------- | ---------------------- | ------------------------ |
| **Rendering**    | Server-Side (Node)     | Client-Side (Browser)    |
| **SEO**          | Nativo (HTML completo) | Limitado (requiere JS)   |
| **Routing**      | Next.js Router         | WordPress + React Router |
| **Backend**      | WordPress Headless     | WordPress Monolito       |
| **Developer XP** | Excelente (Standard)   | Compleja (Híbrida)       |

---
*Glory Framework React Documentation v2.0*
