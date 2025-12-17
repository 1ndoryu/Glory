# Glory React SSR - Plan de Implementacion con Next.js

## Resumen Ejecutivo

Migrar Glory React de "Islands Architecture" (CSR) a **Next.js con SSR completo**, manteniendo WordPress como backend headless.

### Objetivos

1. **SEO Perfecto**: HTML completo en cada request
2. **100% React**: Todo el frontend en React/Next.js
3. **WordPress Headless**: WP solo como CMS (admin, contenido, media)
4. **Desarrollo Simple**: Una sola forma de hacer paginas

---

## Arquitectura Propuesta

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
   (o dominio.com/wp-admin)     (o dominio.com)
```

### Flujo de Datos

1. Usuario visita `dominio.com/blog/mi-post`
2. Next.js recibe la solicitud en el servidor
3. Next.js hace fetch a WordPress REST API para obtener datos del post
4. Next.js renderiza el HTML completo con React
5. HTML se envia al navegador (SEO perfecto)
6. React "hidrata" el HTML para interactividad

---

## Estructura del Proyecto

```
glory/                                    <- Tema WordPress existente
├── Glory/
│   ├── assets/
│   │   └── react/                        <- Proyecto Vite actual (se mantiene por compatibilidad)
│   └── src/
│       └── Services/
│           └── WordPressApi.php          <- NUEVO: Endpoints personalizados para Next.js
│
├── App/
│   └── ...                               <- Codigo PHP de la aplicacion
│
└── frontend/                             <- NUEVO: Proyecto Next.js
    ├── app/                              <- App Router (Next.js 14+)
    │   ├── layout.tsx                    <- Layout global
    │   ├── page.tsx                      <- Home (/)
    │   ├── about/
    │   │   └── page.tsx                  <- About (/about)
    │   ├── services/
    │   │   └── page.tsx                  <- Servicios (/services)
    │   ├── blog/
    │   │   ├── page.tsx                  <- Lista de posts (/blog)
    │   │   └── [slug]/
    │   │       └── page.tsx              <- Post individual (/blog/[slug])
    │   ├── contact/
    │   │   └── page.tsx                  <- Contacto (/contact)
    │   └── globals.css                   <- Estilos globales
    │
    ├── components/
    │   ├── layout/
    │   │   ├── Header.tsx
    │   │   ├── Footer.tsx
    │   │   └── Navigation.tsx
    │   ├── sections/
    │   │   ├── HeroSection.tsx
    │   │   ├── ServicesGrid.tsx
    │   │   ├── TestimonialsSection.tsx
    │   │   └── CtaSection.tsx
    │   └── ui/
    │       ├── Button.tsx
    │       ├── Card.tsx
    │       └── Badge.tsx
    │
    ├── lib/
    │   ├── wordpress.ts                  <- Cliente para WordPress REST API
    │   ├── types.ts                      <- Tipos TypeScript
    │   └── utils.ts                      <- Utilidades
    │
    ├── styles/
    │   ├── variables.css                 <- Variables CSS
    │   └── components.css                <- Estilos de componentes
    │
    ├── public/
    │   └── images/                       <- Assets estaticos
    │
    ├── next.config.js
    ├── tailwind.config.js
    ├── tsconfig.json
    └── package.json
```

---

## Paginas de Ejemplo a Crear

### 1. Home Page (`/`)
- Hero section con titulo y CTA
- Grid de servicios destacados
- Seccion de testimonios
- CTA final

### 2. About Page (`/about`)
- Historia de la empresa
- Mision y vision
- Equipo (si aplica)

### 3. Services Page (`/services`)
- Lista de servicios con descripcion
- Cards interactivas

### 4. Blog Page (`/blog`)
- Lista de posts de WordPress
- Paginacion
- Filtros por categoria

### 5. Blog Post Page (`/blog/[slug]`)
- Contenido del post (desde WordPress)
- Imagen destacada
- Autor y fecha
- Posts relacionados

### 6. Contact Page (`/contact`)
- Formulario de contacto
- Informacion de contacto
- Mapa (opcional)

---

## Stack Tecnologico

| Tecnologia    | Version | Proposito           |
| ------------- | ------- | ------------------- |
| Next.js       | 14.x    | Framework React SSR |
| React         | 18.x    | Libreria UI         |
| TypeScript    | 5.x     | Tipado estatico     |
| Tailwind CSS  | 4.x     | Estilos             |
| Framer Motion | 12.x    | Animaciones         |
| Lucide React  | 0.5x    | Iconos              |

---

## Configuracion WordPress (Backend)

### 1. Habilitar CORS para la API

```php
/* 
 * Agregar en functions.php o plugin
 * Permite que Next.js acceda a la REST API
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        $origin = get_http_origin();
        
        // Origenes permitidos
        $allowed = [
            'http://localhost:3000',
            'https://tu-dominio.com',
        ];
        
        if (in_array($origin, $allowed)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
        }
        
        return $value;
    });
});
```

### 2. Endpoints Personalizados (Opcional)

```php
/* 
 * Endpoint optimizado para Next.js
 * GET /wp-json/glory/v1/posts
 */
add_action('rest_api_init', function() {
    register_rest_route('glory/v1', '/posts', [
        'methods' => 'GET',
        'callback' => function($request) {
            $posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => $request->get_param('per_page') ?: 10,
                'paged' => $request->get_param('page') ?: 1,
            ]);
            
            return array_map(function($post) {
                return [
                    'id' => $post->ID,
                    'slug' => $post->post_name,
                    'title' => $post->post_title,
                    'excerpt' => get_the_excerpt($post),
                    'content' => apply_filters('the_content', $post->post_content),
                    'date' => $post->post_date,
                    'featuredImage' => get_the_post_thumbnail_url($post, 'large'),
                ];
            }, $posts);
        },
        'permission_callback' => '__return_true',
    ]);
});
```

---

## Configuracion Next.js

### next.config.js

```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
    // Permitir imagenes desde WordPress
    images: {
        remotePatterns: [
            {
                protocol: 'http',
                hostname: 'glorybuilder.local',
            },
            {
                protocol: 'https',
                hostname: 'tu-dominio.com',
            },
        ],
    },
    
    // Variables de entorno
    env: {
        WORDPRESS_API_URL: process.env.WORDPRESS_API_URL,
    },
};

module.exports = nextConfig;
```

### .env.local

```env
WORDPRESS_API_URL=http://glorybuilder.local
```

---

## Cliente WordPress (lib/wordpress.ts)

```typescript
const API_URL = process.env.WORDPRESS_API_URL;

export interface WPPost {
    id: number;
    slug: string;
    title: { rendered: string };
    excerpt: { rendered: string };
    content: { rendered: string };
    date: string;
    featured_media: number;
    _embedded?: {
        'wp:featuredmedia'?: Array<{ source_url: string }>;
    };
}

export async function getPosts(options: { 
    perPage?: number; 
    page?: number;
} = {}): Promise<WPPost[]> {
    const { perPage = 10, page = 1 } = options;
    
    const res = await fetch(
        `${API_URL}/wp-json/wp/v2/posts?per_page=${perPage}&page=${page}&_embed`,
        { next: { revalidate: 60 } } // Cache por 60 segundos
    );
    
    if (!res.ok) throw new Error('Error fetching posts');
    
    return res.json();
}

export async function getPostBySlug(slug: string): Promise<WPPost | null> {
    const res = await fetch(
        `${API_URL}/wp-json/wp/v2/posts?slug=${slug}&_embed`,
        { next: { revalidate: 60 } }
    );
    
    if (!res.ok) return null;
    
    const posts = await res.json();
    return posts[0] || null;
}

export async function getPage(slug: string): Promise<WPPost | null> {
    const res = await fetch(
        `${API_URL}/wp-json/wp/v2/pages?slug=${slug}&_embed`,
        { next: { revalidate: 60 } }
    );
    
    if (!res.ok) return null;
    
    const pages = await res.json();
    return pages[0] || null;
}
```

---

## Ejemplo de Pagina SSR

### app/blog/[slug]/page.tsx

```tsx
import { getPostBySlug, getPosts } from '@/lib/wordpress';
import { notFound } from 'next/navigation';
import type { Metadata } from 'next';

interface Props {
    params: { slug: string };
}

// Genera rutas estaticas en build (SSG)
export async function generateStaticParams() {
    const posts = await getPosts({ perPage: 100 });
    return posts.map(post => ({ slug: post.slug }));
}

// Metadata dinamica para SEO
export async function generateMetadata({ params }: Props): Promise<Metadata> {
    const post = await getPostBySlug(params.slug);
    
    if (!post) {
        return { title: 'Post no encontrado' };
    }
    
    return {
        title: post.title.rendered,
        description: post.excerpt.rendered.replace(/<[^>]*>/g, ''),
    };
}

// Pagina SSR
export default async function BlogPostPage({ params }: Props) {
    const post = await getPostBySlug(params.slug);
    
    if (!post) {
        notFound();
    }
    
    const featuredImage = post._embedded?.['wp:featuredmedia']?.[0]?.source_url;
    
    return (
        <article id="blog-post-container" className="contenedorArticulo">
            {featuredImage && (
                <img 
                    src={featuredImage} 
                    alt={post.title.rendered}
                    className="imagenDestacada"
                />
            )}
            
            <header className="cabeceraArticulo">
                <h1>{post.title.rendered}</h1>
                <time>{new Date(post.date).toLocaleDateString('es-ES')}</time>
            </header>
            
            <div 
                className="contenidoArticulo"
                dangerouslySetInnerHTML={{ __html: post.content.rendered }}
            />
        </article>
    );
}
```

---

## Plan de Implementacion

### Fase 1: Configuracion Base (1-2 horas) - EJEMPLO SIMPLIFICADO COMPLETADO

1. [x] Crear proyecto Next.js en `frontend/`
2. [x] Configurar TypeScript
3. [x] Configurar CSS Puro (sin Tailwind, usando variables CSS)
4. [x] Configurar variables de entorno (`.env.local`)
5. [x] Crear cliente WordPress (`lib/wordpress.ts`)

### Fase 2: Layout y Componentes Base (2-3 horas) - EJEMPLO SIMPLIFICADO COMPLETADO

1. [x] Crear Layout global (`app/layout.tsx`)
2. [x] Crear Header con navegacion
3. [x] Crear Footer
4. [x] Crear componentes UI base (Botones, Tarjetas)

### Fase 3: Paginas de Ejemplo (3-4 horas)

1. [x] Home page con secciones (Hero + Grid de Posts)

### Fase 4: Estilos y Pulido (1-2 horas) - EJEMPLO SIMPLIFICADO COMPLETADO

1. [x] Variables CSS globales (`globals.css`)
2. [x] Estilos responsive
3. [ ] Animaciones con Framer Motion
4. [ ] Iconos con Lucide React

### Fase 5: Integracion WordPress (1 hora)

1. [ ] Configurar CORS en WordPress
2. [ ] Crear endpoints personalizados (opcional)
3. [ ] Probar conexion API

### Fase 6: Documentacion (1 hora)

1. [ ] Actualizar `react-glory.md`
2. [ ] Documentar comandos y flujo de desarrollo
3. [ ] Ejemplos de uso

---

## Comandos de Desarrollo

```bash
# Instalar dependencias
cd frontend
npm install

# Desarrollo (HMR)
npm run dev
# Abre http://localhost:3000

# Build de produccion
npm run build

# Iniciar servidor de produccion
npm start
```

---

## Despliegue

### Opcion 1: Vercel (Recomendado)

1. Conectar repositorio a Vercel
2. Configurar variables de entorno (`WORDPRESS_API_URL`)
3. Deploy automatico en cada push

### Opcion 2: Self-hosted

1. `npm run build`
2. `npm start` (requiere Node.js en servidor)
3. Usar PM2 o similar para mantener el proceso

### Opcion 3: Exportacion Estatica

```javascript
// next.config.js
output: 'export'
```

Genera HTML estatico, no requiere servidor Node.js.

---

## Consideraciones de SEO

| Aspecto          | Implementacion                      |
| ---------------- | ----------------------------------- |
| Meta Title       | `generateMetadata()` en cada pagina |
| Meta Description | Extraido del excerpt de WordPress   |
| Open Graph       | Configurado en metadata             |
| Sitemap          | Plugin `next-sitemap`               |
| Robots.txt       | Generado automaticamente            |
| Canonical URLs   | Configurado en Next.js              |

---

## Preguntas Frecuentes

### ¿Donde queda el admin de WordPress?

WordPress sigue funcionando normalmente en `glorybuilder.local/wp-admin`. Solo el frontend publico se maneja con Next.js.

### ¿Como actualizo contenido?

Editas posts/paginas en WordPress como siempre. Next.js obtiene los datos frescos en cada request (o segun la configuracion de cache).

### ¿Puedo usar plugins de WordPress?

Si, pero solo para funcionalidad backend. Los plugins que modifican el frontend no tendran efecto.

### ¿Como manejo formularios?

Puedes enviar formularios a:
1. Endpoint de WordPress (Contact Form 7 REST API)
2. Servicio externo (Formspree, Netlify Forms)
3. API Route de Next.js

---

## Siguiente Paso

Una vez aprobado este plan, procedera a crear la estructura del proyecto Next.js con todas las paginas de ejemplo.

---

*Glory React SSR - Plan de Implementacion v1.0*
