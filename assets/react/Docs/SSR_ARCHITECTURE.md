# Arquitectura de Renderizado React en Glory (SSR Update)

**Fecha:** 17 Diciembre 2025
**Estado:** IMPLEMENTADO

Este documento detalla el análisis de opciones para implementar Server-Side Rendering (SSR) en Glory, la decisión final tomada y el plan técnico de ejecución.

---

## 1. El Problema (Contexto Actual)

Actualmente, Glory utiliza una arquitectura **Client-Side Rendering (CSR)** pura con el patrón "Islands".
1. PHP renderiza un `div` vacío.
2. El navegador descarga JS.
3. React se monta y genera el HTML.

**Desventajas:**
*   **SEO Deficiente:** Google y otros bots ven un contenedor vacío inicialmente (aunque Google renderiza JS, no es instantáneo ni garantizado para todo el contenido).
*   **Performance (LCP):** El usuario ve una pantalla blanca o "loading" hasta que carga el JS.

**Objetivo:** Lograr que el HTML llegue completo desde el servidor (SEO Friendly) sin perder la interactividad de React, manteniendo compatibilidad con **Hosting Compartido (Apache/PHP)**.

---

## 2. Análisis de Opciones

Evaluamos tres caminos principales para lograr el objetivo:

### Opción A: Next.js + WordPress Headless
Separar completamente el frontend (Next.js) del backend (WordPress).
*   **Pros:** Estándar de la industria, ecosistema rico.
*   **Contras:** Requiere servidor Node.js (Vercel/DigitalOcean). **Incompatible con hosting compartido tradicional.** Complica el despliegue (dos proyectos separados).

### Opción B: PHP V8Js / Exec Node
Intentar ejecutar JS dentro de PHP cada vez que carga una página.
*   **Pros:** SSR real Dinámico.
*   **Contras:** Lento. Requiere configuración de servidor avanzada (extensiones PHP raras). Inviable en hosting compartido estándar.

### Opción C: Static SSR (SSG) + Inyección PHP (La Solución Elegida)
Generar el HTML estático ("esqueleto" con contenido base) durante el proceso de **Build** (`npm run build`) y usar PHP para servir ese HTML e inyectar datos frescos.

*   **Pros:**
    *   **100% SEO:** HTML real en el código fuente.
    *   **Hosting Friendly:** Solo requiere PHP y archivos estáticos. Cero Node.js en producción.
    *   **Híbrido:** Permite mezclar páginas PHP puro con páginas Full React.
    *   **DX (Developer Experience):** Escribes solo React.
*   **Contras:** Requiere un paso de "Build" antes de subir cambios de estructura (habitual en desarrollo moderno).

---

## 3. Decisión Arquitectónica

**Se elige la Opción C: Static SSR (SSG).**

Esta arquitectura cumple con el requisito crítico de **"Funcionar en hosting tradicional"** mientras permite desarrollar las páginas **"100% en React"**.

### ¿Cómo funciona la "Hidratación Diferida"?

1.  **Build Time (Local):** Vite renderiza cada Isla React a un archivo `.html` estático y lo guarda en `dist/ssg/`.
2.  **Request Time (Producción):**
    *   PHP recibe la petición (ej: `/home`).
    *   Busca si existe `dist/ssg/HomeIsland.html`.
    *   Lee el HTML y lo imprime dentro del layout.
    *   Inyecta los datos de la base de datos (Posts, Títulos) en un objeto JSON (`data-props`).
3.  **Client Time (Navegador):**
    *   El usuario ve el contenido inmediatamente (HTML estático).
    *   React carga, lee el JSON y se "adhiere" (Hidrata) al HTML existente sin parpadear.

---

## 4. Implementacion (Estado: COMPLETADO)

### Fase 1: Configuracion del Build (Vite) - HECHO
*   Script `scripts/prerender.ts` genera HTML estatico de cada isla.
*   Usa `vite-node` para ejecutar TypeScript con soporte completo de imports.
*   `package.json` actualizado con scripts:
    *   `npm run build` - Compila JS + genera HTML SSG
    *   `npm run build:fast` - Solo compila JS (sin SSG)
    *   `npm run prerender` - Solo genera HTML SSG

### Fase 2: Servicio PHP - HECHO
`Glory\Services\ReactIslands.php` actualizado:
*   Metodo `getSSRContent()` busca HTML en `dist/ssg/{IslandName}.html`
*   Metodo `render()` usa SSG automaticamente si existe el archivo
*   Añade `data-hydrate="true"` cuando sirve contenido SSG
*   En modo desarrollo, NO usa SSG (siempre fresco)

### Fase 3: Hidratacion Cliente - HECHO
`main.tsx` actualizado:
*   Importa `hydrateRoot` de `react-dom/client`
*   Detecta `data-hydrate="true"` en el contenedor
*   Si hay SSG: usa `hydrateRoot()` (preserva HTML)
*   Si no hay SSG: usa `createRoot()` (renderizado normal)
*   Incluye fallback: si hidratacion falla, reintenta con CSR

---

## 5. Flujo de Trabajo (Desarrollador)

### Creando una nueva página
1.  Crear `App/React/islands/NuevaPagina.tsx`.
2.  Desarrollar usando `npm run dev` (Hot Reload funciona normal).
3.  Al terminar: `npm run build`.
    *   Esto genera `dist/assets/index.js` (Lógica)
    *   Y genera `dist/ssg/NuevaPagina.html` (Estructura SEO)
4.  En PHP: `echo ReactIslands::render('NuevaPagina', $datos);`

### Actualizando Contenido
*   Si cambias el **Texto** de un post en WP Admin -> Se actualiza **al instante** (PHP inyecta el dato fresco).
*   Si cambias el **Color de fondo** o estructura en React -> Requiere `npm run build` y subir archivos.

### Templates Disponibles

| Template            | Uso                                  | Header/Footer WP |
| ------------------- | ------------------------------------ | ---------------- |
| `TemplateReact.php` | Paginas 100% React (landing, apps)   | NO               |
| `TemplateGlory.php` | Paginas hibridas (PHP + islas React) | SI               |

Para usar `TemplateReact.php`, registra la pagina como React Fullpage:

```php
// pages.php
PageManager::registerReactFullPages(['home', 'servicios']);
PageManager::define('home', 'home');
```

El sistema detecta automaticamente si la pagina esta en el array y usa el template correcto.

---

## 6. Ejemplo de Código Resultante

**React:**
```tsx
export function Home({ title }) {
  return <h1>{title}</h1>; // Se pre-renderiza como <h1>%TITLE%</h1> o con datos mock
}
```

**PHP:**
```php
// El usuario visita la web
$titulo_real = get_option('blogname'); // "Mi Empresa"
// PHP carga el HTML estático pre-generado
// Y React en el cliente actualiza el titulo si difiere, o PHP lo inyecta pre-hidratado.
echo ReactIslands::render('Home', ['title' => $titulo_real]);
```

---

## 7. Limitaciones Conocidas y Mitigaciones

### Limitacion 1: SEO para Contenido Muy Dinamico

**Problema:** El HTML estatico generado en build contiene datos "mock" o del momento del build. Google indexa este HTML, no los datos frescos inyectados por PHP.

**Impacto Real:**
- Para landing pages (Home, About, Services): **Bajo impacto** - el contenido rara vez cambia.
- Para blogs/productos activos: **Alto impacto** - Google veria contenido desactualizado.

**Mitigacion:**
1. **Usar Modo Hibrido para blogs**: Las paginas de blog deben usar PHP puro para el contenido principal.
2. **Rebuilds periodicos**: Si el contenido cambia frecuentemente, programar `npm run build` semanal/mensual.
3. **Datos criticos en HTML**: Asegurar que el prerender incluya textos descriptivos reales, no solo placeholders.

### Limitacion 2: Flash de Contenido (FOUC)

**Problema:** Si el HTML estatico difiere mucho de los datos reales, puede haber un "flash" al cargar.

**Mitigacion:**
1. **Usar datos representativos en el build**: Prerender con contenido similar al real.
2. **CSS de transicion**: Aplicar `opacity: 0` inicial y hacer fade-in despues de hidratar.
3. **Mantener estructura consistente**: Solo cambiar textos, no layouts completos.

### Limitacion 3: Deploy Manual

**Problema:** Cada cambio de estructura requiere `npm run build` + subir archivos.

**Mitigacion:**
1. **Script de deploy**: Crear script que automatice build + FTP en un solo comando.
2. **Documentar proceso**: Flujo claro para el equipo.
3. **Separar contenido de estructura**: Lo que cambia frecuentemente (textos) viene de PHP; lo que cambia poco (layouts) requiere rebuild.

---

## 8. Cuando NO Usar SSG (Usar PHP Puro)

| Tipo de Pagina       | Recomendacion | Razon                                        |
| -------------------- | ------------- | -------------------------------------------- |
| Landing Pages        | SSG (React)   | Contenido estable, interactividad alta       |
| Paginas de Servicios | SSG (React)   | Contenido estable                            |
| Blog Posts           | PHP Puro      | Contenido dinamico frecuente                 |
| Paginas de Producto  | PHP + Islands | Datos dinamicos + widgets React              |
| Admin/Dashboard      | PHP + Islands | Requiere autenticacion, datos en tiempo real |
| Terminos Legales     | PHP Puro      | Texto simple, sin interactividad             |

---

## 9. Compatibilidad con Proyectos PHP Puro

Glory mantiene **100% compatibilidad** con proyectos que no usan React:

1. **reactMode desactivado por defecto**: Si no se llama `GloryFeatures::enable('reactMode')`, Glory funciona como tema PHP normal.
2. **Scripts condicionales**: Los assets de React SOLO se cargan si hay islas registradas.
3. **Sin dependencias**: No se requiere Node.js ni npm para proyectos PHP puros.

**Para usar Glory sin React:**
```php
// control.php - NO incluir esta linea
// GloryFeatures::enable('reactMode');

// Resultado: Glory funciona 100% en modo WordPress tradicional
```
