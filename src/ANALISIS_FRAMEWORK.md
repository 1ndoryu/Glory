# Análisis Completo del Framework Glory (`Glory/src/`)

> Fecha: Enero 2025  
> Objetivo: Evaluación archivo por archivo para un framework WordPress con foco React.  
> Categorías: **KEEP** (conservar), **DEPRECATE** (reemplazar a futuro), **ELIMINATE** (eliminar)

---

## Resumen Ejecutivo

| Métrica | Valor |
|---------|-------|
| Total archivos analizados | ~80 archivos |
| Líneas de código estimadas | ~12,000+ |
| Directorios principales | 13 (Admin, Api, Console, Contracts, Core, Exception, Helpers, Manager, Plugins, Repository, Seo, Services, Utility) |
| Archivos KEEP | ~45 |
| Archivos DEPRECATE | ~12 |
| Archivos ELIMINATE | ~8 |
| Plugin independiente (AmazonProduct) | ~35 archivos (evaluación separada) |

### Arquitectura General
- **Patrón:** Clases estáticas sin contenedor DI. Registros globales + Managers (fachadas) + Servicios.
- **Bootstrap:** `Setup.php` → carga todo condicionalmente según `GloryFeatures`.
- **React Mode:** `GloryFeatures::isReactMode()` desactiva ~30 features PHP cuando React maneja el frontend.
- **Puntos fuertes:** Sistema de opciones bien estratificado (Registry → Repository → Manager), DefaultContent robusto, ReactIslands maduro.
- **Puntos débiles:** Clases que superan 300 líneas (MenuManager 796, PageManager 802, SeoFrontendRenderer 599, GestorCssCritico 639, AssetsUtility 674, MediaIntegrityService 531), acoplamiento por clases estáticas, namespace inconsistente en Contracts.

---

## 1. Admin/ (8 archivos)

### OpcionPanelController.php
- **Líneas:** ~91
- **Descripción:** Registra página de admin WP para panel de opciones del tema. Carga CSS/JS del panel.
- **Dependencias:** PanelDataProvider, OpcionPanelSaver, PanelRenderer, AssetManager
- **Uso:** Activo — punto de entrada del panel de opciones admin.
- **React/PHP:** PHP backend puro
- **Veredicto:** **KEEP** — Necesario para configuración del tema desde WP admin.

### OpcionPanelSaver.php
- **Líneas:** ~93
- **Descripción:** Maneja submit del formulario de opciones (guardar/reset). Verifica nonce, sanitiza, guarda vía OpcionRepository.
- **Dependencias:** OpcionRegistry, OpcionRepository, OpcionManager
- **Uso:** Activo — esencial para persistir opciones.
- **React/PHP:** PHP backend puro
- **Veredicto:** **KEEP**

### PanelDataProvider.php
- **Líneas:** ~60
- **Descripción:** Provee datos (opciones, features, modo dev) al panel de admin.
- **Dependencias:** OpcionRegistry, OpcionRepository, GloryFeatures, AssetManager
- **Uso:** Activo
- **React/PHP:** PHP backend puro
- **Veredicto:** **KEEP**

### PanelRenderer.php
- **Líneas:** ~178
- **Descripción:** Renderiza HTML del panel de opciones con tabs, secciones y tipos de campo (text, textarea, select, color, switch, repeater, schedule).
- **Dependencias:** FormBuilder (externo), OpcionManager
- **Uso:** Activo
- **React/PHP:** PHP backend con output HTML
- **Veredicto:** **KEEP** — Podría migrarse a React en futuro, pero funcional como está.

### SeoMetabox.php
- **Líneas:** ~194
- **Descripción:** Metabox SEO para páginas (título, descripción, canonical, FAQ JSON, breadcrumb JSON-LD).
- **Dependencias:** PageManager, GloryLogger
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — SEO es fundamental independientemente del frontend.

### SyncController.php
- **Líneas:** ~18
- **Descripción:** Thin wrapper que instancia y registra SyncManager.
- **Dependencias:** SyncManager
- **Uso:** Activo (bootstrapping)
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Podrá integrarse directamente en Setup.php si se simplifica.

### SyncManager.php
- **Líneas:** ~313
- **Descripción:** Botones de sincronización en admin bar, auto-sync en modo dev, purga de caché (soporta WP Rocket, LiteSpeed, W3TC, etc.), reset SEO, resync HTML.
- **Dependencias:** GloryLogger, DefaultContentSynchronizer, OpcionManager, PageManager, AssetManager, MenuManager, GloryFeatures
- **Uso:** Activo — componente central de sincronización
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Funcionalidad core. Considerar dividir (supera 300 líneas).

### TaxonomyMetaManager.php
- **Líneas:** ~117
- **Descripción:** Imagen de categoría vía WP Media Library. Agrega campos en formularios de categoría.
- **Dependencias:** Ninguna de Glory (solo WP nativo)
- **Uso:** Activo
- **React/PHP:** PHP backend con JS inline
- **Veredicto:** **KEEP**

---

## 2. Api/ (4 archivos)

### ImagesController.php
- **Líneas:** ~371
- **Descripción:** REST API `/glory/v1/images` — lista imágenes por alias, obtiene URL optimizada con CDN Jetpack Photon, imagen aleatoria.
- **Dependencias:** AssetsUtility, ImageUtility
- **Uso:** Activo — sirve assets al frontend React.
- **React/PHP:** PHP backend, **crítico para React frontend**
- **Veredicto:** **KEEP** — Supera 300 líneas, considerar dividir.

### MCPController.php
- **Líneas:** ~234
- **Descripción:** REST API `/glory/v1/mcp` — gestiona Application Passwords para MCP (Claude/Cursor). CRUD de tokens.
- **Dependencias:** Solo WP nativo
- **Uso:** Herramienta de desarrollo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Útil para flujo de desarrollo con IA.

### NewsletterController.php
- **Líneas:** ~120
- **Descripción:** REST API `/glory/v1/newsletter` — suscripción email con tabla custom en DB.
- **Dependencias:** Solo WP nativo (wpdb)
- **Uso:** Activo si se usa newsletter
- **React/PHP:** PHP backend, consumible por React
- **Veredicto:** **KEEP** — Funcionalidad independiente y útil.

### PageBlocksController.php
- **Líneas:** ~192
- **Descripción:** REST API `/glory/v1/page-blocks/{id}` — guardar/cargar bloques de page builder como JSON en post_meta.
- **Dependencias:** Solo WP nativo
- **Uso:** Activo — page builder React
- **React/PHP:** PHP backend, **crítico para React page builder**
- **Veredicto:** **KEEP**

---

## 3. Console/ (1 archivo)

### CriticalCssCommand.php
- **Líneas:** ~72
- **Descripción:** Comando WP-CLI `glory critical-css generate`.
- **Dependencias:** LocalCriticalCss, GestorCssCritico
- **Uso:** Herramienta DevOps
- **React/PHP:** PHP backend
- **Veredicto:** **DEPRECATE** — Critical CSS es menos relevante en modo React (SPA). Listado como `reactExcludedFeature`.

---

## 4. Contracts/ (1 archivo)

### FormHandlerInterface.php
- **Líneas:** ~15
- **Descripción:** Interface con método `procesar()`.
- **Dependencias:** Ninguna
- **Uso:** **Posiblemente muerto** — namespace declarado `Glory\Handler\Form` no coincide con ubicación en `Contracts/`. No se encontraron implementaciones evidentes.
- **React/PHP:** PHP backend
- **Veredicto:** **ELIMINATE** — Namespace incorrecto, sin implementaciones conocidas.

---

## 5. Core/ (6 archivos) — TODOS KEEP

### DefaultContentRegistry.php
- **Líneas:** ~67
- **Descripción:** Almacén estático de definiciones de contenido por defecto (por post type).
- **Dependencias:** GloryLogger
- **Uso:** Activo — pilar del sistema de contenido
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### GloryFeatures.php
- **Líneas:** ~230
- **Descripción:** Sistema de feature flags. React Mode que excluye ~30 features PHP. `isActive()`, `isReactMode()`, `applyReactMode()`.
- **Dependencias:** OpcionManager, AssetManager, OpcionRegistry
- **Uso:** **Componente crítico** — determina qué partes del framework cargan.
- **React/PHP:** PHP backend, controla integración React
- **Veredicto:** **KEEP** — Corazón del framework.

### GloryLogger.php
- **Líneas:** ~311
- **Descripción:** Sistema de logging buffered con niveles (INFO/WARNING/ERROR/CRITICAL), agrupación por función caller, flush en WP shutdown.
- **Dependencias:** Ninguna (solo error_log WP)
- **Uso:** Activo — usado en todo el framework
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Supera 300 líneas, pero funcionalidad justificada.

### OpcionRegistry.php
- **Líneas:** ~68
- **Descripción:** Registro estático de definiciones de opciones con versionado por hash.
- **Dependencias:** GloryLogger
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### OpcionRepository.php
- **Líneas:** ~130
- **Descripción:** Repositorio de opciones WP con prefijo `glory_opcion_` y patrón sentinel para distinguir null de no-encontrado.
- **Dependencias:** Ninguna (solo WP nativo)
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### Setup.php
- **Líneas:** ~170
- **Descripción:** Bootstrap principal. Orquesta carga condicional de todos los componentes según GloryFeatures.
- **Dependencias:** Prácticamente todo el framework
- **Uso:** **Punto de entrada principal**
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

---

## 6. Exception/ (1 archivo)

### ExcepcionComandoFallido.php
- **Líneas:** ~50
- **Descripción:** Excepción custom para comandos externos fallidos (exit code + error output).
- **Dependencias:** Ninguna
- **Uso:** Usado por ManejadorGit
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Si ManejadorGit se mantiene. **DEPRECATE** si Git se elimina.

---

## 7. Helpers/ (1 archivo)

### AjaxNav.php
- **Líneas:** ~65
- **Descripción:** Configuración estática para navegación AJAX (selectores CSS de contenido y scroll).
- **Dependencias:** Ninguna (solo WP add_filter)
- **Uso:** Probablemente inactivo en modo React (React maneja su propia navegación SPA).
- **React/PHP:** PHP backend para frontend PHP
- **Veredicto:** **DEPRECATE** — Reemplazado por React Router o equivalente en modo React.

---

## 8. Manager/ (8 archivos)

### AdminPageManager.php
- **Líneas:** ~68
- **Descripción:** Registro de páginas de admin (menú/submenú WP) vía métodos estáticos.
- **Dependencias:** GloryLogger
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### AssetManager.php
- **Líneas:** ~430
- **Descripción:** Gestión de CSS/JS con feature gating, critical CSS injection, estilos async, scripts deferred, escaneo de carpetas con caché, auto-detección de archivos .min.
- **Dependencias:** GloryLogger, GloryFeatures, GestorCssCritico, OpcionManager
- **Uso:** **Componente core** — carga todos los assets del tema.
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Supera 300 líneas significativamente, debería dividirse (ej: CriticalCssIntegration, FolderScanner como helpers).

### DefaultContentManager.php
- **Líneas:** ~250
- **Descripción:** Fachada del sistema de contenido por defecto con `define()`, `build()`, `buildSamplePosts()`.
- **Dependencias:** DefaultContentRegistry, DefaultContentSynchronizer, AssetsUtility
- **Uso:** Activo — sistema fundamental para sembrar contenido
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### MenuManager.php
- **Líneas:** ~796
- **Descripción:** Gestión completa de menús WP — definición por código, detección de sync, normalización de placeholders, soporte multi-location.
- **Dependencias:** GloryFeatures
- **Uso:** Activo
- **React/PHP:** PHP backend (los menús se consumen tanto en PHP como en React vía REST)
- **Veredicto:** **KEEP** — **URGENTE dividir** (796 líneas, más del doble del máximo). Separar: MenuDefinition, MenuSync, MenuNormalizer.

### OpcionManager.php
- **Líneas:** ~290
- **Descripción:** Fachada de opciones con `register()`, `get()`, sync, caché in-memory, manejo dev/prod.
- **Dependencias:** GloryFeatures, GloryLogger, OpcionRegistry, OpcionRepository, AssetManager
- **Uso:** **Componente core** — usado en todo el framework.
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### PageManager.php
- **Líneas:** ~802
- **Descripción:** Gestión de páginas con `define()`, `reactPage()`, React Fullpage, interceptación de templates, SEO defaults, content mode (code/editor), jerarquía padre/hijo.
- **Dependencias:** GloryLogger, UserUtility, ReactIslands
- **Uso:** **Componente crítico** — integra React pages directamente.
- **React/PHP:** PHP backend con integración React profunda
- **Veredicto:** **KEEP** — **URGENTE dividir** (802 líneas). Separar: PageDefinition, PageTemplateInterceptor, PageSeoDefaults, ReactPageHandler.

### PostTypeManager.php
- **Líneas:** ~270
- **Descripción:** Registro de CPT con auto-generación de labels en español y meta defaults.
- **Dependencias:** GloryLogger
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### TemplateManager.php
- **Líneas:** ~80
- **Descripción:** Escanea `App/Templates/` para plantillas PHP, resuelve callbacks.
- **Dependencias:** Ninguna de Glory (usa error_log directo, no GloryLogger)
- **Uso:** Potencialmente infrautilizado en modo React.
- **React/PHP:** PHP backend
- **Veredicto:** **DEPRECATE** — En modo React, las plantillas PHP son menos relevantes. Migrar a usar GloryLogger.

---

## 9. Repository/ (1 archivo)

### DefaultContentRepository.php
- **Líneas:** ~90
- **Descripción:** Abstracciones WP_Query para buscar/verificar posts de contenido por defecto (por slug, meta keys `_glory_default_content_slug/edited`).
- **Dependencias:** Solo WP nativo
- **Uso:** Activo — parte del sistema DefaultContent
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

---

## 10. Seo/ (1 archivo)

### SeoFrontendRenderer.php
- **Líneas:** ~599
- **Descripción:** Renderizado SEO completo — document title, meta description, canonical, Open Graph, Twitter Cards, JSON-LD (FAQ, Breadcrumb, Organization, Article/BlogPosting).
- **Dependencias:** PageManager
- **Uso:** **Activo y crítico** — SEO funciona independientemente del frontend (React o PHP).
- **React/PHP:** PHP backend para ambos frontends
- **Veredicto:** **KEEP** — **Dividir obligatoriamente** (599 líneas). Separar: MetaTagRenderer, OpenGraphRenderer, JsonLdRenderer.

---

## 11. Services/ — Top Level (14 archivos)

### AnalyticsEngine.php
- **Líneas:** ~93
- **Descripción:** Motor genérico de analytics sobre arrays (sum, count, avg, min, max, percentiles).
- **Dependencias:** Ninguna
- **Uso:** **Potencialmente muerto** — no tiene dependencias de WP ni de Glory. Muy genérico.
- **React/PHP:** PHP puro (utility)
- **Veredicto:** **ELIMINATE** — Demasiado genérico, sin uso evidente. Las funciones array nativas de PHP cubren estos casos.

### BusquedaService.php
- **Líneas:** ~250
- **Descripción:** Servicio de búsqueda configurable (posts, usuarios, custom types con handlers).
- **Dependencias:** GloryLogger
- **Uso:** Activo si se usa búsqueda server-side
- **React/PHP:** PHP backend, consumible por React vía REST
- **Veredicto:** **KEEP** — Útil como backend de búsqueda para React.

### DefaultContentSynchronizer.php
- **Líneas:** ~180
- **Descripción:** Lógica de sincronización core — crea/actualiza/elimina posts desde definiciones, detecta edición manual, repara media.
- **Dependencias:** DefaultContentRegistry, DefaultContentRepository, PostSyncHandler, TermSyncHandler, MediaIntegrityService
- **Uso:** **Activo — pilar del sistema DefaultContent**
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### EventBus.php
- **Líneas:** ~85
- **Descripción:** Bus de eventos simple basado en versiones usando WP options para invalidación de caché.
- **Dependencias:** Solo WP nativo
- **Uso:** Activo — usado por PostActionManager
- **React/PHP:** PHP backend (agnóstico)
- **Veredicto:** **KEEP**

### GestorCssCritico.php
- **Líneas:** ~639
- **Descripción:** Sistema completo de Critical CSS — gestión de caché, UI en admin bar, handlers AJAX, integración WP-Cron, modos local/remoto.
- **Dependencias:** GloryLogger, GloryFeatures, OpcionManager, LocalCriticalCss
- **Uso:** Activo en modo PHP. **Listado en `reactExcludedFeatures`**.
- **React/PHP:** PHP backend para frontend PHP
- **Veredicto:** **DEPRECATE** — No aplica en modo React (Vite/bundler maneja CSS). Conservar solo si se mantiene un frontend PHP paralelo. Supera 300 líneas masivamente.

### LocalCriticalCss.php
- **Líneas:** ~70
- **Descripción:** Ejecuta script Node.js (`generateCritical.js`) para generar CSS crítico localmente.
- **Dependencias:** GloryLogger
- **Uso:** Dependiente de GestorCssCritico
- **React/PHP:** PHP backend + Node.js
- **Veredicto:** **DEPRECATE** — Misma razón que GestorCssCritico.

### ManejadorGit.php
- **Líneas:** ~433
- **Descripción:** Wrapper Git completo vía `proc_open` (clone, fetch, commit, push, branch, stash, log).
- **Dependencias:** GloryLogger, ExcepcionComandoFallido
- **Uso:** **Herramienta DevOps** — deploy/backup
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** (como herramienta dev/ops). Supera 300 líneas — considerar dividir o mover a un namespace `Tools/`.

### PerformanceProfiler.php
- **Líneas:** ~350
- **Descripción:** Profiling de rendimiento con timing, memoria, tracking de HTTP requests.
- **Dependencias:** GloryFeatures
- **Uso:** Herramienta de desarrollo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP** — Útil para debugging.

### PostActionManager.php
- **Líneas:** ~210
- **Descripción:** CRUD wrapper para WP posts con validación y EventBus.
- **Dependencias:** GloryLogger, EventBus
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### QueryProfiler.php
- **Líneas:** ~260
- **Descripción:** Profiler de queries SQL con widget en admin bar (Top 10 más lentas).
- **Dependencias:** GloryFeatures
- **Uso:** Herramienta de desarrollo
- **React/PHP:** PHP backend con JS widget
- **Veredicto:** **KEEP** — Útil para debugging.

### ReactContentProvider.php
- **Líneas:** ~265
- **Descripción:** Puente WP→React — registra queries, formatea posts con metadata completa, inyecta como `window.__GLORY_CONTENT__`.
- **Dependencias:** DefaultContentRegistry
- **Uso:** **Componente clave para React** — provee datos al frontend.
- **React/PHP:** PHP backend para React frontend
- **Veredicto:** **KEEP**

### ReactIslands.php
- **Líneas:** ~388
- **Descripción:** Arquitectura de React Islands — registro/render de islas, detección Vite dev server, hidratación SSG, carga de bundles producción.
- **Dependencias:** Ninguna de Glory (solo WP nativo)
- **Uso:** **Componente crítico para React** — implementa el patrón de islas.
- **React/PHP:** PHP + React (sistema híbrido)
- **Veredicto:** **KEEP** — Supera 300 líneas, pero justificado por complejidad.

### ServidorChat.php
- **Líneas:** ~170
- **Descripción:** Servidor WebSocket chat usando Ratchet/ReactPHP. Solo CLI.
- **Dependencias:** Ratchet, ReactPHP (composer)
- **Uso:** **Especializado** — funcionalidad de chat, no relacionada con el framework core.
- **React/PHP:** PHP backend (servidor independiente)
- **Veredicto:** **ELIMINATE** — No pertenece a un framework de temas. Mover a proyecto independiente si se necesita.

### TokenManager.php
- **Líneas:** ~35
- **Descripción:** Validación simple de Bearer token contra WP option.
- **Dependencias:** OpcionManager
- **Uso:** Activo — seguridad de API
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

---

## 12. Services/Stripe/ (6 archivos)

### StripeConfig.php
- **Líneas:** ~95
- **Descripción:** Configuración centralizada Stripe (claves desde constantes o WP options, detección test mode).
- **Dependencias:** Solo WP nativo
- **Veredicto:** **KEEP** — Si se usa Stripe.

### StripeApiClient.php
- **Líneas:** ~170
- **Descripción:** Cliente HTTP Stripe sin librería oficial — usa `wp_remote_*`. Métodos para customers, subscriptions, invoices, billing portal.
- **Dependencias:** GloryLogger, StripeConfig
- **Veredicto:** **KEEP** — Si se usa Stripe.

### StripeCheckoutService.php
- **Líneas:** ~170
- **Descripción:** Sesiones de Stripe Checkout para suscripciones y pagos únicos.
- **Dependencias:** StripeApiClient
- **Veredicto:** **KEEP** — Si se usa Stripe.

### AbstractStripeWebhookHandler.php
- **Líneas:** ~155
- **Descripción:** Clase base abstracta para webhooks (subscription, checkout, invoice, payment events).
- **Dependencias:** GloryLogger, StripeApiClient, StripeWebhookVerifier
- **Veredicto:** **KEEP** — Si se usa Stripe.

### StripeWebhookVerifier.php
- **Líneas:** ~115
- **Descripción:** Verificación de firma HMAC-SHA256 de webhooks Stripe sin SDK oficial.
- **Dependencias:** StripeConfig
- **Veredicto:** **KEEP** — Si se usa Stripe.

### StripeWebhookException.php
- **Líneas:** ~45
- **Descripción:** Excepción personalizada con error codes y HTTP status mapping.
- **Dependencias:** Ninguna
- **Veredicto:** **KEEP** — Si se usa Stripe.

> **Nota global Stripe/:** Todo el módulo Stripe es coherente, bien implementado sin SDK externo, y modular. **KEEP como módulo opcional**. Si no se usa Stripe en el proyecto, puede ignorarse completamente sin afectar el framework core.

---

## 13. Services/Sync/ (4 archivos) — TODOS KEEP

### MediaIntegrityService.php
- **Líneas:** ~531
- **Descripción:** Verifica y repara imágenes de posts (thumbnail, galería). Lógica de fallback determinístico, cooldown anti-reintento, sanitización de URLs rotas en contenido y meta.
- **Dependencias:** GloryLogger, AssetsUtility
- **Uso:** **Activo — parte core del sistema de sincronización**
- **Veredicto:** **KEEP** — **Dividir obligatoriamente** (531 líneas). Separar: FeaturedImageRepair, GalleryRepair, ContentSanitizer.

### PostSyncHandler.php
- **Líneas:** ~220
- **Descripción:** Crea/actualiza/compara posts individuales contra definiciones. Detecta cambios en campos, meta, imagen destacada.
- **Dependencias:** GloryLogger, PostRelationHandler, MediaIntegrityService, AssetsUtility
- **Uso:** Activo — core de sincronización
- **Veredicto:** **KEEP**

### PostRelationHandler.php
- **Líneas:** ~150
- **Descripción:** Gestiona relaciones de un post: imagen destacada, galería, términos de taxonomía. Importa assets y crea términos faltantes.
- **Dependencias:** GloryLogger, AssetsUtility
- **Uso:** Activo
- **Veredicto:** **KEEP**

### TermSyncHandler.php
- **Líneas:** ~100
- **Descripción:** Sincroniza categorías definidas en código con la DB. Elimina términos obsoletos, crea/actualiza definidos.
- **Dependencias:** GloryLogger, AssetsUtility
- **Uso:** Activo
- **Veredicto:** **KEEP**

---

## 14. Utility/ (7 archivos)

### AssetsUtility.php
- **Líneas:** ~674
- **Descripción:** Utilidad central de assets — registro de aliases, resolución flexible (case-insensitive, sin extensión), importación a Media Library con trazabilidad (`_glory_asset_source`), URLs con CDN Jetpack Photon, listado/selección aleatoria.
- **Dependencias:** GloryLogger, AssetManager
- **Uso:** **Componente core** — usado por todo el sistema de media/contenido.
- **React/PHP:** PHP backend, sirve a ambos frontends
- **Veredicto:** **KEEP** — **URGENTE dividir** (674 líneas, más del doble del máximo). Separar: AssetResolver, AssetImporter, AssetLister, AssetImageRenderer.

### EmailUtility.php
- **Líneas:** ~55
- **Descripción:** Envío de email HTML a administradores vía `wp_mail()`.
- **Dependencias:** GloryLogger
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### ImageUtility.php
- **Líneas:** ~90
- **Descripción:** Optimización de imágenes con Jetpack Photon CDN. Genera tags `<img>` con dimensiones y lazy loading.
- **Dependencias:** Solo WP nativo
- **Uso:** Activo — usado por ImagesController
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### PostUtility.php
- **Líneas:** ~30
- **Descripción:** Helper simple para obtener post meta con fallback al post actual.
- **Dependencias:** Solo WP nativo
- **Uso:** Activo
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

### ScheduleManager.php
- **Líneas:** ~185
- **Descripción:** Gestor de horarios de negocio — normaliza datos de horario, calcula estado abierto/cerrado con timezone, mensajes en español.
- **Dependencias:** OpcionManager, GloryLogger
- **Uso:** Activo si el sitio tiene horarios
- **React/PHP:** PHP backend, datos consumibles por React
- **Veredicto:** **KEEP** — Funcionalidad específica pero bien encapsulada.

### TemplateRegistry.php
- **Líneas:** ~80
- **Descripción:** Registro global de plantillas de renderizado con callables, filtrable por post type.
- **Dependencias:** Ninguna
- **Uso:** Potencialmente infrautilizado en modo React
- **React/PHP:** PHP backend
- **Veredicto:** **DEPRECATE** — En modo React, el renderizado es responsabilidad de componentes React.

### UserUtility.php
- **Líneas:** ~50
- **Descripción:** Helpers de usuario: `logeado()`, `tieneRoles()`, `meta()`.
- **Dependencias:** Solo WP nativo
- **Uso:** Activo — usado por PageManager
- **React/PHP:** PHP backend
- **Veredicto:** **KEEP**

---

## 15. Plugins/AmazonProduct/ (~35 archivos)

### Evaluación General
- **Descripción:** Plugin completo de productos Amazon con arquitectura servidor/cliente (SaaS). Incluye scraping, API REST, Stripe webhooks, licencias, importación, sincronización, renderizado con filtros, taxonomías custom.
- **Arquitectura:** Usa `PluginMode` (server/client) definido en wp-config.php o .env
- **Líneas estimadas:** ~3,000+
- **Dependencias Glory:** PostTypeManager, GloryFeatures, GloryLogger

### Estructura:
| Directorio | Archivos | Propósito |
|------------|----------|-----------|
| Admin/ | AdminAssetLoader, ApiWizardAjaxHandler, Metabox/, Tabs/, Views/ | UI de administración |
| Api/ | ApiEndpoints, StripeWebhookHandler | REST API y webhooks |
| Controller/ | AdminController, DemoController, ImportAjaxController, ManualImportAjaxController, ServerAdminController | Controladores de página |
| Mode/ | PluginMode | Detección server/client |
| Model/ | License, Section, TransactionLog | Modelos de datos |
| Renderer/ | AssetLoader, CardRenderer, DealsRenderer, FilterRenderer, GridRenderer, ProductRenderer, QueryBuilder | Renderizado frontend |
| Service/ | 19 archivos (AmazonApi, Scrapers, Sync, Images, Licenses, SMTP, etc.) | Lógica de negocio |

### Veredicto: **KEEP como módulo opcional/plugin independiente**
- Es un mini-producto SaaS dentro del framework.
- Bien separado del core (namespace propio `Glory\Plugins\AmazonProduct`).
- No afecta al framework si se elimina.
- Considerar extraer a un plugin WP independiente si crece más.

---

## Resumen de Acciones Prioritarias

### ELIMINATE (eliminar ya)
| Archivo | Razón |
|---------|-------|
| Contracts/FormHandlerInterface.php | Namespace incorrecto, sin implementaciones |
| Services/AnalyticsEngine.php | Genérico sin uso, funciones PHP nativas lo cubren |
| Services/ServidorChat.php | No pertenece al framework de temas |

### DEPRECATE (reemplazar progresivamente)
| Archivo | Razón | Alternativa |
|---------|-------|-------------|
| Console/CriticalCssCommand.php | reactExcludedFeature | No necesario con Vite |
| Services/GestorCssCritico.php | reactExcludedFeature, 639 líneas | Vite maneja CSS en React |
| Services/LocalCriticalCss.php | Dependiente de GestorCssCritico | Vite |
| Helpers/AjaxNav.php | React Router reemplaza | React Router |
| Manager/TemplateManager.php | PHP templates en modo React | Componentes React |
| Utility/TemplateRegistry.php | PHP renderizado en modo React | Componentes React |

### KEEP pero DIVIDIR (urgente, superan 300 líneas)
| Archivo | Líneas | Propuesta de división |
|---------|--------|-----------------------|
| Manager/MenuManager.php | 796 | MenuDefinition, MenuSync, MenuNormalizer |
| Manager/PageManager.php | 802 | PageDefinition, PageTemplateInterceptor, PageSeoDefaults, ReactPageHandler |
| Seo/SeoFrontendRenderer.php | 599 | MetaTagRenderer, OpenGraphRenderer, JsonLdRenderer |
| Utility/AssetsUtility.php | 674 | AssetResolver, AssetImporter, AssetLister |
| Services/Sync/MediaIntegrityService.php | 531 | FeaturedImageRepair, GalleryRepair, ContentSanitizer |
| Manager/AssetManager.php | 430 | AssetEnqueuer, FolderScanner |
| Services/ManejadorGit.php | 433 | GitReader, GitWriter (o mover a Tools/) |

### Componentes Clave para React (no tocar)
1. **ReactIslands.php** — Motor de islas React
2. **ReactContentProvider.php** — Puente de datos WP→React
3. **PageManager.php** — Soporte `reactPage()` y `reactFullpage()`
4. **GloryFeatures.php** — React Mode toggle
5. **PageBlocksController.php** — API para page builder React
6. **ImagesController.php** — API de imágenes para React

---

## Diagrama de Dependencias Core

```
Setup.php (Bootstrap)
├── GloryFeatures.php (Feature Flags + React Mode)
│   ├── OpcionManager.php → OpcionRegistry + OpcionRepository
│   └── AssetManager.php → GestorCssCritico
├── PageManager.php → ReactIslands.php
├── MenuManager.php
├── PostTypeManager.php
├── DefaultContentManager.php
│   └── DefaultContentSynchronizer.php
│       ├── PostSyncHandler → PostRelationHandler + MediaIntegrityService
│       ├── TermSyncHandler
│       └── DefaultContentRegistry + DefaultContentRepository
├── SyncManager.php (Admin bar sync)
├── SeoFrontendRenderer.php
├── ReactContentProvider.php
├── API Controllers (Images, Newsletter, PageBlocks, MCP)
└── PerformanceProfiler + QueryProfiler (dev only)
```
