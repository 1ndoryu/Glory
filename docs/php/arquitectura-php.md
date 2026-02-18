# Arquitectura PHP

Mapa completo del codigo PHP en `Glory/src/`.

## Directorios

```
Glory/src/
├── Admin/          # Panel de admin, metaboxes
├── Api/            # REST API controllers
├── Core/           # Features, logger, registries
├── Manager/        # Managers principales (pages, menus, assets)
├── Repository/     # Acceso a datos
├── Seo/            # Renderers de SEO
├── Services/       # Servicios de negocio
├── Tools/          # Herramientas (Git)
└── Utility/        # Utilidades
```

## Admin/

| Clase | Responsabilidad |
|-------|----------------|
| `OpcionPanelController` | Controller del panel de opciones |
| `OpcionPanelSaver` | Guardar opciones desde el panel |
| `PanelDataProvider` | Datos para renderizar el panel |
| `PanelRenderer` | Renderizar HTML del panel |
| `SeoMetabox` | Metabox de SEO en editor de paginas |
| `SyncController` | Controller de sincronizacion manual |
| `SyncManager` | Barra de admin y botones de reset |
| `TaxonomyMetaManager` | Meta de taxonomias |

## Api/

| Clase | Responsabilidad |
|-------|----------------|
| `ImagesController` | `/glory/v1/images/*` |
| `MCPController` | `/glory/v1/mcp/*` |
| `NewsletterController` | `/glory/v1/newsletter` |
| `PageBlocksController` | `/glory/v1/page-blocks/*` |

## Core/

| Clase | Responsabilidad |
|-------|----------------|
| `DefaultContentRegistry` | Registro de contenido por defecto |
| `GloryFeatures` | Sistema de feature flags |
| `GloryLogger` | Logger del framework |
| `SchemaRegistry` | Registro y validación de schemas (tablas y post types). [Detalle](schema-system.md) |
| `OpcionRegistry` | Registro de opciones disponibles |
| `OpcionRepository` | Lectura/escritura de opciones |
| `Setup` | Bootstrap del framework |

## Manager/

| Clase | Responsabilidad |
|-------|----------------|
| `AdminPageManager` | Paginas de admin |
| `AssetManager` | Enqueue de scripts/estilos |
| `DefaultContentManager` | Sincronizacion de contenido |
| `FolderScanner` | Escaneo de carpetas |
| `MenuManager` | Registro de menus |
| `MenuDefinition` | Estructura de menu |
| `MenuSync` | Sync menus PHP → WP |
| `MenuNormalizer` | Normaliza items |
| `OpcionManager` | API de opciones |
| `PageManager` | Registro de paginas |
| `PageDefinition` | Estructura de pagina |
| `PageProcessor` | Procesamiento de paginas |
| `PageReconciler` | Sync paginas PHP → WP |
| `PageSeoDefaults` | Defaults SEO |
| `PageTemplateInterceptor` | Intercepta template loader |
| `PostTypeManager` | Custom post types |

## Seo/

| Clase | Responsabilidad |
|-------|----------------|
| `SeoFrontendRenderer` | Fachada SEO |
| `MetaTagRenderer` | Meta tags basicos |
| `OpenGraphRenderer` | Open Graph tags |
| `JsonLdRenderer` | JSON-LD structured data |

## Services/

| Clase | Responsabilidad |
|-------|----------------|
| `ReactIslands` | Motor de islas (inyecta contenedores) |
| `ReactContentProvider` | Serializa datos WP → React |
| `DefaultContentSynchronizer` | Logica de sync |
| `EventBus` | Eventos internos |
| `QueryProfiler` | Profiler de queries SQL |
| `PerformanceProfiler` | Profiler de rendimiento |
| `TokenManager` | Tokens de autenticacion |
| `PostActionManager` | Acciones post-save |
| `BusquedaService` | Busqueda interna |

### Services/Sync/

| Clase | Responsabilidad |
|-------|----------------|
| `ContentSanitizer` | Sanitizar contenido |
| `FeaturedImageRepair` | Reparar imagenes destacadas |
| `GalleryRepair` | Reparar galerias |
| `MediaIntegrityService` | Fachada integridad media |
| `PostRelationHandler` | Relaciones entre posts |
| `PostSyncHandler` | Sync de posts |
| `TermSyncHandler` | Sync de terminos |

## Utility/

| Clase | Responsabilidad |
|-------|----------------|
| `AssetsUtility` | Paths y URLs de assets |
| `AssetResolver` | Resolucion de rutas |
| `AssetImporter` | Importacion masiva |
| `AssetLister` | Listado |
| `EmailUtility` | Envio de emails |
| `ImageUtility` | Manipulacion de imagenes |
| `PostUtility` | Utilidades de posts |
| `ScheduleManager` | WP Cron |
| `UserUtility` | Utilidades de usuarios |

## Principio SRP

Todos los archivos PHP estan bajo **300 lineas**. Si una clase crece, se divide en clases mas pequenas con responsabilidad unica.
