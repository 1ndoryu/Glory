# Auditoría Completa: Glory PHP Framework (`Glory/src/`)

**Fecha:** Junio 2025  
**Archivos analizados:** ~120 de 137 PHP files  
**Directorios cubiertos:** Core/, Manager/, Admin/, Api/, Services/, Services/Sync/, Services/Stripe/, Utility/, Seo/, Tools/, Repository/, Plugins/AmazonProduct/

---

## P0 — CRÍTICOS (Requieren acción inmediata)

### P0-01 · Inyección de datos sin sanitizar desde `$_POST`
- **Archivo:** `Admin/OpcionPanelSaver.php` (líneas ~30-50)
- **Problema:** Los valores de `$_POST` se pasan directamente a `OpcionRepository::save()` sin sanitizar. Si bien hay verificación de nonce en `OpcionPanelController`, los valores individuales no se escapan ni validan antes de guardarlos en la base de datos.
- **Fix sugerido:** Aplicar `sanitize_text_field()`, `wp_kses_post()` o validación por tipo de campo antes de guardar cada valor.

### P0-02 · SSL verification deshabilitada en cURL (WebScraperProvider + ImageDownloaderService)
- **Archivos:** `Plugins/AmazonProduct/Service/WebScraperProvider.php` (línea ~231), `Plugins/AmazonProduct/Service/ImageDownloaderService.php` (línea ~165)
- **Problema:** `CURLOPT_SSL_VERIFYPEER => false` desactiva la verificación de certificados SSL, permitiendo ataques MITM.
- **Fix sugerido:** Usar `true` o delegar a `wp_remote_get/post` que maneja SSL correctamente. Si es necesario para el proxy, limitar a entorno de desarrollo.

### P0-03 · Uso de `curl_*` directamente en lugar de API de WordPress
- **Archivos:** `Plugins/AmazonProduct/Service/WebScraperProvider.php` (línea ~231+), `Plugins/AmazonProduct/Service/ImageDownloaderService.php` (línea ~165+)
- **Problema:** Uso directo de `curl_init/curl_exec` rompe la portabilidad (no todos los hostings tienen cURL) y omite los filtros de seguridad de WordPress (`pre_http_request`, etc.). Además impide que plugins de caché/seguridad intercepten las peticiones.
- **Fix sugerido:** Usar `wp_remote_request()` con argumentos de proxy cuando sea posible.

### P0-04 · Secreto de diagnóstico hardcodeado
- **Archivo:** `Plugins/AmazonProduct/Api/ApiEndpoints.php` (líneas ~107-113)
- **Problema:** El endpoint de diagnóstico `/proxy-diagnostic` y `/email-test` usan un secreto por defecto `'glory-diag-2024'` si no se define `GLORY_DIAGNOSTIC_SECRET`. Cualquiera que conozca este valor puede ejecutar diagnósticos.
- **Fix sugerido:** No tener fallback hardcodeado. Si la constante no está definida, deshabilitar el endpoint o requerir `manage_options` capability.

### P0-05 · DemoController: Formularios POST sin verificación de nonce
- **Archivo:** `Plugins/AmazonProduct/Controller/DemoController.php` (líneas ~45-50)
- **Problema:** Los formularios `generate_demo_data` y `update_demo_prices` solo verifican `current_user_can('manage_options')` pero NO verifican un nonce, haciéndolos vulnerables a CSRF.
- **Fix sugerido:** Agregar `wp_nonce_field()` al formulario y `check_admin_referer()` en el handler.

### P0-06 · `$_GET['secret']` accedido directamente en permission_callback
- **Archivo:** `Plugins/AmazonProduct/Api/ApiEndpoints.php` (líneas ~107, ~124)  
- **Problema:** Se accede a `$_GET['secret']` directamente dentro del `permission_callback` sin usar `$request->get_param()`. Además la comparación de secretos no es timing-safe.
- **Fix sugerido:** Usar `$request->get_param('secret')` y `hash_equals()` para la comparación.

---

## P1 — ALTOS (Deberían corregirse pronto)

### P1-01 · Interpolación SQL sin `$wpdb->prepare()` en NewsletterController
- **Archivo:** `Api/NewsletterController.php` (línea ~30-40)
- **Problema:** `SHOW TABLES LIKE '$tabla'` usa interpolación de string directa con `$wpdb->prefix`. Aunque `$wpdb->prefix` es generalmente seguro, el patrón es incorrecto y viola las mejores prácticas. `crearTabla()` debería ejecutarse en hook de activación, no en `after_setup_theme`.
- **Fix sugerido:** Usar `$wpdb->prepare("SHOW TABLES LIKE %s", $tabla)`.

### P1-02 · `serialize()` usado en clave de caché (FolderScanner)
- **Archivo:** `Manager/FolderScanner.php` (línea ~40-50)
- **Problema:** `serialize()` de los parámetros de entrada se usa para generar una clave de caché. Si algún parámetro proviene de entrada del usuario (improbable pero posible), podría permitir inyección de objetos PHP.
- **Fix sugerido:** Usar `json_encode()` o `md5(json_encode(...))` para la clave de caché.

### P1-03 · Token de API almacenado sin hash (TokenManager)
- **Archivo:** `Services/TokenManager.php` (líneas ~20-30)
- **Problema:** El token de aplicación se almacena en texto plano en `wp_options`. Si la BD es comprometida, el token queda expuesto directamente.
- **Fix sugerido:** Almacenar un hash del token (`wp_hash_password()`) y comparar con `wp_check_password()`.

### P1-04 · ReactContentProvider expone todos los meta públicos
- **Archivo:** `Services/ReactContentProvider.php` (líneas ~200-250)
- **Problema:** Se recupera y expone `get_post_meta($postId)` sin filtrar claves, potencialmente exponiendo información sensible a través del JSON que se inyecta en el frontend.
- **Fix sugerido:** Definir una whitelist de meta fields permitidos para exposición pública.

### P1-05 · Duplicación de lógica de verificación de webhook (StripeWebhookHandler vs StripeWebhookVerifier)
- **Archivos:** `Plugins/AmazonProduct/Api/StripeWebhookHandler.php` (método `verifyWebhookSignature`), `Services/Stripe/StripeWebhookVerifier.php`
- **Problema:** La misma lógica de verificación de firma de Stripe está implementada dos veces: una en `StripeWebhookHandler::verifyWebhookSignature()` y otra en `StripeWebhookVerifier::verify()`. Esto viola DRY y aumenta riesgo de inconsistencias.
- **Fix sugerido:** `StripeWebhookHandler` debería delegar a `StripeWebhookVerifier` (que ya existe con las mismas constantes y lógica).

### P1-06 · Credenciales de proxy en código de log
- **Archivo:** `Plugins/AmazonProduct/Service/WebScraperProvider.php` (logs de proxy)
- **Problema:** Información de autenticación del proxy (usuario + sesión) se incluye en logs de GloryLogger, que van a `error_log`. Podría filtrar credenciales.
- **Fix sugerido:** Redactar credenciales antes de logear (`substr($proxyAuth, 0, 8) . '...'`).

### P1-07 · `$GLOBALS` contaminación
- **Archivos:** `Manager/PageDefinition.php` (`$GLOBALS['_glory_react_configs']`), `Manager/PageProcessor.php` (`$GLOBALS['gloryCopyContext']`), `Services/Sync/TermSyncHandler.php` (`$GLOBALS['glory_categorias_definidas']`)
- **Problema:** Uso de `$GLOBALS` como mecanismo de comunicación entre componentes. Crea acoplamiento oculto, dificulta testing y puede causar colisiones de nombres.
- **Fix sugerido:** Usar un Registry singleton, contenedor de inyección de dependencias o pasar datos explícitamente.

### P1-08 · `file_put_contents` sin manejo de errores (FolderScanner)
- **Archivo:** `Manager/FolderScanner.php` (línea ~70-80)
- **Problema:** `file_put_contents()` para escribir caché no verifica el resultado ni maneja fallos de escritura.
- **Fix sugerido:** Verificar el retorno y logear error si falla.

---

## P2 — MEDIOS (Mejoras importantes de calidad)

### P2-01 · Ausencia total de `declare(strict_types=1)`
- **Archivos:** TODOS (~137 archivos PHP)
- **Problema:** Ningún archivo en `Glory/src/` declara strict types. Esto permite conversiones de tipo implícitas que pueden ocultar bugs.
- **Fix sugerido:** Agregar `declare(strict_types=1);` como primera línea de TODOS los archivos PHP.

### P2-02 · Return types incompletos/ausentes
- **Archivos:** Múltiples — `OpcionRepository.php`, `OpcionManager.php`, `AdminPageManager.php`, `AssetManager.php`, etc.
- **Problema:** Muchos métodos no declaran return type (`mixed` implícito), o usan `mixed` donde un tipo más específico es posible. `AdminPageManager` declara parámetros como `?string` para callbacks que WP espera como `callable`.
- **Fix sugerido:** Agregar return types explícitos donde sea posible. Cambiar `?string` a `?callable` en `AdminPageManager`.

### P2-03 · Inline `<script>` en PHP (SeoMetabox, TaxonomyMetaManager)
- **Archivos:** `Admin/SeoMetabox.php` (líneas ~150-200), `Admin/TaxonomyMetaManager.php` (líneas ~40-60)
- **Problema:** Bloques JavaScript inline dentro de métodos `render()`. Esto impide la aplicación de Content-Security-Policy (CSP) y dificulta la cacheabilidad.
- **Fix sugerido:** Mover JS a archivos `.js` separados y registrarlos con `wp_enqueue_script()` + `wp_add_inline_script()`.

### P2-04 · `posts_per_page => -1` sin límite práctico
- **Archivos:** `Manager/PageReconciler.php`, `Admin/SyncManager.php` (`resyncAllManagedPagesHtml`), `Services/DefaultContentSynchronizer.php`, `Plugins/AmazonProduct/Controller/DemoController.php` (`updateDemoPrices`), `Plugins/AmazonProduct/Controller/ImportAjaxController.php` (`getImportedAsins`)
- **Problema:** Consultas WP_Query con `posts_per_page => -1` cargan TODOS los registros en memoria. En sitios con muchos posts esto causa agotamiento de memoria y timeouts.
- **Fix sugerido:** Implementar paginación o procesamiento por lotes. Agregar un límite máximo razonable (e.g., 500).

### P2-05 · `@` para suprimir errores de funciones de archivo
- **Archivos:** `Manager/AssetManager.php` (`@filemtime`), `Utility/AssetLister.php` (`@getimagesize`, `@filesize`), `Services/ReactIslands.php` (`@file_get_contents`), `Admin/CachePurger.php` (`@opcache_reset`)
- **Problema:** Suprimir errores con `@` oculta problemas reales y dificulta el debugging.
- **Fix sugerido:** Usar verificaciones previas (`file_exists()`, `is_readable()`) antes de leer archivos, o `try/catch` donde aplique.

### P2-06 · Tablas creadas en `after_setup_theme` no en hook de activación
- **Archivo:** `Api/NewsletterController.php` (línea ~15-20)
- **Problema:** `crearTabla()` se ejecuta en cada request en `after_setup_theme`, verificando si la tabla existe cada vez. Esto agrega una query SQL innecesaria por cada carga de página.
- **Fix sugerido:** Mover la creación de tabla al hook `after_switch_theme` (activación del tema) o usar un flag de opción para no re-verificar.

### P2-07 · CachePurger bypasses WP transient API
- **Archivo:** `Admin/CachePurger.php` (líneas ~30-40)
- **Problema:** `$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'")` borra transients directamente con SQL en lugar de usar `delete_transient()`. Esto omite el object cache de WordPress (Redis/Memcached si está configurado).
- **Fix sugerido:** Usar `wp_cache_flush()` o iterar con `delete_transient()` para respetar el object cache.

### P2-08 · MD5/crc32 para hashing no criptográfico
- **Archivos:** `Core/OpcionRegistry.php` (MD5 para hash de opciones), `Core/GloryLogger.php` (crc32 para deduplicación), `Utility/AssetImporter.php` (md5 para cache keys)
- **Problema:** MD5 y crc32 son débiles. Aunque no se usan con propósitos de seguridad aquí, MD5 causa colisiones y crc32 tiene solo 32 bits de entropía.
- **Fix sugerido:** Considerar `xxhash` o `sha256` para hashes de caché. Para deduplicación de logs, `xxh3` es más rápido y seguro que crc32.

### P2-09 · `debug_backtrace()` costoso en logger
- **Archivo:** `Core/GloryLogger.php` (líneas ~80-100)
- **Problema:** `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)` se llama en cada invocación del logger. Aunque limitado a 3 frames, sigue siendo costoso en rutas calientes.
- **Fix sugerido:** Hacer el backtrace opt-in (solo en modo debug/dev) o cachearlo por request.

### P2-10 · Strings hardcodeados en español (acoplamiento de idioma)
- **Archivos:** `Manager/PostTypeManager.php` ('Añadir nuevo', 'Editar', etc.), `Utility/ScheduleManager.php` ('Cerrado', 'Abierto'), `JsonLdRenderer.php` ('Inicio')
- **Problema:** Labels y textos están hardcodeados en español en lugar de usar `__()` o `_x()` de WordPress i18n.
- **Fix sugerido:** Envolver en funciones de traducción o centralizar en un archivo de constantes/labels.

### P2-11 · `current_time('timestamp')` deprecado
- **Archivos:** `Plugins/AmazonProduct/Service/ProductSyncService.php` (líneas ~90, ~270), `Plugins/AmazonProduct/Service/ClientSyncService.php`
- **Problema:** `current_time('timestamp')` está deprecado desde WP 5.3. Retorna un pseudo-timestamp que no es un Unix timestamp real.
- **Fix sugerido:** Usar `time()` o `wp_date()`.

### P2-12 · Referencia a clase inexistente (FormBuilder)
- **Archivo:** `Admin/PanelRenderer.php` (línea ~20)
- **Problema:** Se referencia `Glory\Components\FormBuilder` que no existe en `Glory/src/`. Podría estar en otra ubicación o ser código muerto.
- **Fix sugerido:** Verificar si la clase existe y actualizar el import, o eliminar la referencia si es código muerto.

---

## P3 — BAJOS (Mejoras nice-to-have)

### P3-01 · Agnosticismo: GloryConfig hardcodea rutas `App/`
- **Archivo:** `Core/GloryConfig.php` (líneas ~20-40)
- **Problema:** Los defaults de configuración referencian directamente `App/Config`, `App/Content`, etc. Esto vincula el framework Glory al directorio `App/` del tema consumidor.
- **Fix sugerido:** Los defaults ya son sobreescritos por el tema, pero podrían ser más genéricos (e.g., `null` con validación) para verdadera agnosticidad.

### P3-02 · Agnosticismo: ImageUtility usa constante `LOCAL`
- **Archivo:** `Utility/ImageUtility.php` (líneas ~20-30)
- **Problema:** Referencia una constante `LOCAL` definida externamente para decidir si usar CDN. Esto acopla la utilidad al tema consumidor.
- **Fix sugerido:** Leer de `GloryConfig` o `GloryFeatures` en lugar de una constante global.

### P3-03 · Métodos estáticos excesivos (SRP/DIP)
- **Archivos:** Mayoría de clases Manager/, Admin/, Services/, Api/
- **Problema:** Casi todo el framework usa métodos estáticos (`static`), lo que impide injection de dependencias, dificulta testing unitario (no se puede mockear) y crea acoplamiento rígido.
- **Fix sugerido:** Migrar gradualmente a instancias inyectadas. Si necesitan estado global, usar un Service Container o Registry de singletons.

### P3-04 · Archivos que exceden 300 líneas (violación de regla de protocolo)
- **Archivos:**
  - `Plugins/AmazonProduct/Service/WebScraperProvider.php` (~801 líneas)
  - `Plugins/AmazonProduct/Api/StripeWebhookHandler.php` (~535 líneas)
  - `Plugins/AmazonProduct/Service/ClientSyncService.php` (~496 líneas)
  - `Plugins/AmazonProduct/Api/ApiEndpoints.php` (~483 líneas)
  - `Plugins/AmazonProduct/Service/HtmlParserService.php` (~352 líneas)
  - `Plugins/AmazonProduct/Service/LicenseService.php` (~351 líneas)
  - `Plugins/AmazonProduct/Service/ProductSyncService.php` (~328 líneas)
  - `Services/Sync/FeaturedImageRepair.php` (~322 líneas)
  - `Services/PerformanceProfiler.php` (~320 líneas)
  - `Services/ReactContentProvider.php` (~306 líneas)
  - `Manager/PageDefinition.php` (~300+ líneas)
  - `Manager/MenuSync.php` (~300+ líneas)
  - `Admin/SyncManager.php` (~300+ líneas)
  - `Utility/AssetImporter.php` (~332 líneas)
- **Fix sugerido:** Dividir según SRP. WebScraperProvider puede separar parsing en su propia clase. StripeWebhookHandler ya tiene AbstractStripeWebhookHandler disponible.

### P3-05 · Código muerto potencial: `OpcionManager::init()` con add_action comentado
- **Archivo:** `Manager/OpcionManager.php` (línea ~30)
- **Problema:** Hay un `add_action` comentado que sugiere funcionalidad deshabilitada o incompletamente migrada.
- **Fix sugerido:** Eliminar el código comentado o documentar por qué está deshabilitado.

### P3-06 · `uniqid()` para generación de slugs
- **Archivo:** `Manager/DefaultContentManager.php` (método `generarSlug`)
- **Problema:** `uniqid()` no genera valores criptográficamente seguros y tiene baja entropía. Usado como fallback para slugs.
- **Fix sugerido:** Usar `wp_generate_uuid4()` o `bin2hex(random_bytes(4))`.

### P3-07 · `error_log` con `print_r` — posible fuga de datos
- **Archivo:** `Services/PostActionManager.php` (línea ~100)
- **Problema:** `print_r($resultado, true)` en error_log podría exponer datos sensibles en los logs del servidor.
- **Fix sugerido:** Sanitizar o truncar los datos antes de logearlos.

### P3-08 · Missing type hints en propiedades de clase
- **Archivos:** Múltiples clases en Manager/, especialmente arrays estáticos sin tipo.
- **Problema:** Propiedades como `private static $postTypes = []` no tienen tipo declarado (PHP 7.4+).
- **Fix sugerido:** Agregar `private static array $postTypes = [];`.

### P3-09 · Múltiples `wp_get_nav_menu_items` calls potenciales N+1
- **Archivo:** `Manager/MenuNormalizer.php` (múltiples líneas)
- **Problema:** Múltiples llamadas a `wp_get_nav_menu_items()` en una normalización pueden causar N+1 queries si se llama repetidamente.
- **Fix sugerido:** Cachear el resultado en una propiedad estática durante el ciclo de normalización.

### P3-10 · Comentario SRP contradictorio
- **Archivo:** `Manager/OpcionManager.php` (línea ~15)
- **Problema:** Contiene el comentario explícito "SRP no aplica aquí", lo cual es un code smell documentado.
- **Fix sugerido:** Refactorizar para que SRP sí aplique o justificar técnicamente por qué es una excepción válida.

### P3-11 · `GitCommandRunner` hardcodea ruta de Git en Windows
- **Archivo:** `Tools/GitCommandRunner.php` (línea ~20)
- **Problema:** `C:/Program Files/Git/cmd/git.exe` hardcodeado. No funcionará si Git está instalado en otra ubicación.
- **Fix sugerido:** Detectar con `where git` (Windows) o `which git` (Unix), o hacer la ruta configurable.

### P3-12 · `wpdb->query` devuelve resultados no verificados
- **Archivo:** `Admin/CachePurger.php` — no se verifica el resultado de `$wpdb->query()`.
- **Fix sugerido:** Verificar `false` como retorno para detectar errores SQL.

### P3-13 · Constantes `GLORY_FRAMEWORK_PATH` referenciadas sin verificación
- **Archivo:** `Manager/FolderScanner.php`, `Services/ReactIslands.php`, otros
- **Problema:** Se asume que `GLORY_FRAMEWORK_PATH` está definida, pero no hay fallback ni verificación.
- **Fix sugerido:** Agregar `defined('GLORY_FRAMEWORK_PATH')` check o lanzar excepción clara si falta.

---

## Resumen de Estadísticas

| Severidad | Total | Categoría principal |
|-----------|-------|---------------------|
| **P0 Critical** | 6 | Seguridad (CSRF, SSL, secretos hardcodeados) |
| **P1 High** | 8 | Seguridad + Arquitectura (SQL, tokens, globals) |
| **P2 Medium** | 12 | Calidad de código + Performance |
| **P3 Low** | 13 | Best practices + Agnosticismo + SOLID |
| **TOTAL** | **39** | |

### Por Categoría

| Categoría | Issues |
|-----------|--------|
| **Seguridad** | P0-01, P0-02, P0-03, P0-04, P0-05, P0-06, P1-01, P1-02, P1-03, P1-06 |
| **Performance** | P2-04, P2-05, P2-06, P2-07, P2-09 |
| **SOLID** | P1-05, P1-07, P3-03, P3-04, P3-10 |
| **Código muerto** | P3-05, P2-12 |
| **PHP Best Practices** | P2-01, P2-02, P2-03, P2-08, P2-10, P2-11, P3-06, P3-07, P3-08, P3-11, P3-12, P3-13 |
| **Agnosticismo (refs a App/)** | P3-01, P3-02 |

---

## Observaciones Positivas

- **Nonce verification** bien implementada en la mayoría de forms/AJAX (OpcionPanelController, MenuManager, ImportAjaxController, ManualImportAjaxController).
- **Feature toggle system** (GloryFeatures) bien diseñado con overrides en runtime.
- **Stripe webhook verification** usa `hash_equals()` (timing-safe) y valida timestamps.
- **API Provider Interface** (AmazonProduct) implementa correctamente el patrón Strategy/Factory.
- **License/Usage control** bien estructurado con rate limiting, GB tracking y anomaly detection.
- **SRP en Seo/** — bien dividido en MetaTagRenderer, OpenGraphRenderer, JsonLdRenderer.
- **MediaIntegrityService** ordena bien la composición de FeaturedImageRepair, GalleryRepair, ContentSanitizer.
- **GitCommandRunner** extraído de ManejadorGit para SRP — correcto.
- **Tab pattern** en AdminController/ServerAdminController con TabInterface — buen OCP.
