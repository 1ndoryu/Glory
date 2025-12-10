Especificacion Tecnica: Plugin de Integracion de Productos Amazon

1. Objetivo del Sistema 
Desarrollar una extension para WordPress que automatice la obtencion, visualizacion y actualizacion de productos de Amazon mediante API, priorizando el rendimiento web (WPO) y la flexibilidad en el ordenamiento de datos.

2. Arquitectura de Datos (Backend)

Fuente de Datos: Implementacion de un cliente API capaz de conectarse a un servicio intermediario de productos (Proxy API) con arquitectura modular para permitir una migracion futura a la Amazon Product Advertising API (PA-API) oficial sin reescribir la logica visual.

Autenticacion: Gestion de claves API (Key/Secret) y parametros de afiliacion (Tracking ID) configurables desde el panel de administracion.

Recuperacion de Datos: "Fetching" masivo de productos basado en palabras clave (keywords) o ASINs, recuperando: Titulo, Imagen, Precio Actual, Precio Original (disponible solo en endpoint de Deals), Calificacion (Estrellas), Reviews y Enlace de Afiliado.

NOTA: La API actual (amazon-data.p.rapidapi.com) no expone precio original en busquedas normales ni por ASIN. El precio original solo esta disponible en el endpoint deal.php para productos en oferta.

3. Logica de Negocio y Funcionalidades

Motor de Ordenamiento: Algoritmos para ordenar los resultados obtenidos segun criterios especificos:

- Mayor porcentaje de descuento (calculado dinamicamente entre precio original vs. precio venta).
- Mejores valoraciones (rating).
- Novedades (fecha de inclusion).
- Precio (ascendente/descendente).

Paginacion: Sistema de paginacion numerica backend-side (no scroll infinito) que divide los resultados en bloques de 9 a 12 items por pagina.

Filtros Disponibles:
- Por categoria
- Por rango de precio
- Por rating minimo
- Solo Prime
- Solo Ofertas (productos con descuento)

4. Interfaz de Usuario (Frontend)

Layout Responsivo: Sistema de rejilla (Grid) CSS con auto-fill.

Rendimiento: Generacion de HTML optimizado para SEO. JavaScript sin dependencias externas (vanilla JS, sin jQuery) para mejorar Core Web Vitals (LCP/CLS).

5. Gestion de Contenido

Shortcodes disponibles:
- [amazon_products] - Muestra productos importados con filtros interactivos
- [amazon_deals] - Muestra productos guardados que tienen descuento (NO llama a API)

Atributos de [amazon_products]:
- limit: Numero de productos (default: 12)
- ids: IDs de WordPress separados por coma (ej: "123,456,789")
- search: Palabra clave para filtrar en titulo
- min_price / max_price: Filtro de precio
- min_rating: Rating minimo (1-5)
- category: Slug de categoria
- only_prime: "1" para solo productos Prime
- only_deals: "1" para solo productos con descuento
- orderby: "date", "price", "rating", "discount", "random"
- order: "ASC" o "DESC"
- hide_filters: "1" para ocultar panel de filtros
- pagination: "0" para desactivar paginacion

Atributos de [amazon_deals]:
- limit: Numero de ofertas (default: 12)
- orderby: "discount" (default), "date", "price", "rating"
- order: "DESC" (default) o "ASC"
- category: Filtrar por categoria

Ejemplos:
- [amazon_products limit="8" orderby="rating"]
- [amazon_products ids="123,456" hide_filters="1"]
- [amazon_products search="auriculares" orderby="random"]
- [amazon_deals limit="12" orderby="discount"]

6. Panel de Administracion

Pestanas disponibles (implementadas como clases separadas):
- Import Products: Buscar e importar productos por keyword
- Import Deals: Importar ofertas con precio original y descuento
- API Settings: Configuracion de API key, region, affiliate tag
- Design: Personalizacion de colores del boton y precios
- Updates: Sincronizacion manual/automatica de precios
- Usage & Help: Documentacion de shortcodes

7. Estructura del Plugin (Refactorizada)

AmazonProduct/
|-- AmazonProductPlugin.php         # Punto de entrada, registro de CPT y hooks
|-- Controller/
|   |-- AdminController.php         # Orquestacion de tabs del admin
|   |-- DemoController.php          # Generacion de datos demo
|-- Renderer/
|   |-- ProductRenderer.php         # Coordinador principal, registro shortcodes
|   |-- CardRenderer.php            # Renderizado de cards (unificado)
|   |-- FilterRenderer.php          # Panel de filtros UI
|   |-- GridRenderer.php            # Grid de productos y paginacion
|   |-- QueryBuilder.php            # Construccion de WP_Query
|   |-- AssetLoader.php             # Carga de CSS/JS
|   |-- DealsRenderer.php           # Shortcode [amazon_deals]
|-- Service/
|   |-- AmazonApiService.php        # Cliente de la API
|   |-- ProductImporter.php         # Logica de importacion de productos y deals
|   |-- DiscountCalculator.php      # Calculos de descuento y utilidades
|-- Admin/
|   |-- Tabs/
|       |-- TabInterface.php        # Interface para tabs
|       |-- ImportTab.php           # Tab de importacion de productos
|       |-- DealsTab.php            # Tab de importacion de deals
|       |-- ConfigTab.php           # Tab de configuracion API
|       |-- DesignTab.php           # Tab de diseno visual
|       |-- UpdatesTab.php          # Tab de actualizaciones
|       |-- HelpTab.php             # Tab de ayuda
|-- i18n/
|   |-- Labels.php                  # Traducciones centralizadas (es/en)
|-- assets/
    |-- js/
    |   |-- amazon-product.js       # Filtros AJAX (vanilla JS)
    |-- css/
        |-- amazon-product.css      # Estilos del plugin

8. Principios de Diseno Aplicados

- Single Responsibility Principle (SRP): Cada clase tiene una unica responsabilidad
- Open/Closed Principle: Las tabs pueden extenderse sin modificar AdminController
- Interface Segregation: TabInterface define contrato minimo para tabs
- Dependency Inversion: Clases de alto nivel no dependen de implementaciones concretas

9. Mejoras vs Version Anterior

| Aspecto         | Antes                  | Despues                      |
| --------------- | ---------------------- | ---------------------------- |
| AdminController | 535 lineas             | ~120 lineas                  |
| ProductRenderer | 709 lineas             | ~160 lineas (coordinador)    |
| Renderer/       | 1 archivo monolitico   | 7 clases especializadas      |
| Traduccion      | Mezclada en PHP        | Clase Labels centralizada    |
| Cards HTML      | Duplicado en 2 metodos | CardRenderer unificado       |
| Importacion     | En AdminController     | ProductImporter dedicado     |
| Tabs Admin      | Switch/case monotilico | Clases separadas extensibles |

Detalle Renderer refactorizado (Diciembre 2024):
| Clase           | Lineas | Responsabilidad                    |
| --------------- | ------ | ---------------------------------- |
| ProductRenderer | ~160   | Coordinador, registro shortcodes   |
| FilterRenderer  | ~175   | Panel de filtros UI                |
| GridRenderer    | ~145   | Grid de productos y paginacion     |
| QueryBuilder    | ~210   | Construccion de WP_Query           |
| AssetLoader     | ~80    | Carga de CSS/JS                    |
| DealsRenderer   | ~130   | Shortcode [amazon_deals]           |
| CardRenderer    | ~230   | Renderizado de cards (sin cambios) |

10. Extension del Plugin

Para agregar un nuevo tab al admin:

1. Crear clase que implemente TabInterface
2. Agregar instancia en AdminController::registerTabs()

Para agregar traducciones:

1. Agregar strings en Labels::TRANSLATIONS
2. Usar Labels::get('key') en cualquier parte

11. MEJORAS PENDIENTES (Diciembre 2024)

Estado: [ ] = Pendiente, [x] = Completado

11.1 BUGS CRITICOS

[x] BUG-01: Idioma no cambia aunque se configure en espanol
    - Problema: Labels::$currentLang se cachea y no se resetea al guardar settings
    - Solucion: Llamar Labels::resetCache() despues de guardar configuracion
    - Archivo: Admin/Tabs/ConfigTab.php, i18n/Labels.php
    - COMPLETADO: Se agrego import de Labels y llamada a resetCache() en saveSettings()

[x] BUG-02: Palabra "Filtros" hardcodeada en espanol
    - Problema: Linea 141 de ProductRenderer.php tiene "Filtros" en lugar de Labels::get()
    - Archivo: Renderer/ProductRenderer.php
    - COMPLETADO: Se agrego 'filters' a Labels::TRANSLATIONS y se usa Labels::get('filters')

[x] BUG-03: Contador de resultados muestra solo productos de pagina actual
    - Problema: Dice "12 resultados" pero hay mas en otras paginas
    - Solucion: Mostrar total general (found_posts) no el limit por pagina
    - Archivos modificados:
      - Renderer/ProductRenderer.php: Agrega getTotalProductCount() y pasa total a FilterRenderer
      - Renderer/FilterRenderer.php: renderResultsHeader() acepta $totalCount como parametro
      - assets/js/amazon-product.js: Usa data-total-count en lugar de contar cards visibles
    - COMPLETADO: El contador ahora muestra el total real desde PHP (found_posts)

11.2 NUEVOS ATRIBUTOS DE SHORTCODE

[x] FEAT-01: Atributo hide_filters="1"
    - Permite ocultar completamente el panel de filtros
    - Archivo: Renderer/ProductRenderer.php
    - COMPLETADO: Implementado con logica condicional

[x] FEAT-02: Atributo ids="123,456,789"
    - Mostrar productos especificos por ID de WordPress
    - Archivo: Renderer/ProductRenderer.php (buildQuery)
    - COMPLETADO: Usa post__in de WP_Query

[x] FEAT-03: Atributo search="palabra"
    - Filtrar productos guardados que contengan la palabra en el titulo
    - Archivo: Renderer/ProductRenderer.php (buildQuery)
    - COMPLETADO: Usa parametro 's' de WP_Query

[x] FEAT-04: Atributo random="1" (se usa orderby="random")
    - Ordenar productos de forma aleatoria
    - Archivo: Renderer/ProductRenderer.php (buildQuery)
    - COMPLETADO: orderby="random" usa 'rand' de WP_Query

[x] FEAT-05: Atributo pagination="0"
    - Desactivar paginacion y mostrar todos hasta el limit
    - Archivo: Renderer/ProductRenderer.php
    - COMPLETADO: Implementado en renderGrid y renderDiscountSortedGrid

[x] FEAT-06: Atributo min_rating="4"
    - Filtrar por rating minimo desde el shortcode
    - Archivo: Renderer/ProductRenderer.php
    - COMPLETADO: Ya existia, se documento en HelpTab

11.3 SISTEMA DE ACTUALIZACION DE PRODUCTOS

[x] FEAT-07: Panel de control de actualizacion programada
    - Implementado:
      a) Frecuencia configurable en ConfigTab (manual, diario, semanal)
      b) Ultima sincronizacion visible con fecha/hora
      c) Proxima sincronizacion programada visible
      d) Boton de sincronizacion manual con validacion de limite API
      e) Log de actualizaciones recientes con tabla visual
    - Archivos:
      - Service/ProductSyncService.php (nuevo) - Logica de sincronizacion
      - Admin/Tabs/UpdatesTab.php - Panel de control completo
      - AmazonProductPlugin.php - Integracion con WP Cron
    - COMPLETADO: Panel funcional con estado, logs y sincronizacion manual

[x] FEAT-08: Actualizacion inteligente de productos guardados
    - Implementado en ProductSyncService::syncAllProducts():
      1. getAllSavedAsins() obtiene lista de ASINs en DB
      2. Procesa por lotes (BATCH_SIZE = 10) para evitar timeout
      3. updateProductData() actualiza solo precio, rating, reviews
      4. Verifica limite de API antes de cada llamada
      5. Registra todo en log de sincronizacion
    - Ahorra llamadas API: solo consulta productos existentes
    - COMPLETADO: Sincronizacion inteligente por lotes

11.4 MEJORAS EN IMPORTACION

[x] FEAT-09: Indicar productos ya guardados en resultados de busqueda
    - Al buscar productos, marcar visualmente los que ya estan importados
    - Mostrar badge "Ya importado" y fecha de importacion
    - Evitar reimportar duplicados
    - Archivo: Admin/Tabs/ImportTab.php
    - COMPLETADO: Badge verde, columna Estado, boton Actualizar vs Importar

[x] FEAT-10: Detectar productos duplicados antes de importar
    - Verificar si ASIN ya existe en DB antes de crear nuevo post
    - Ofrecer opcion: "Actualizar existente" vs "Crear nuevo"
    - Archivo: Service/ProductImporter.php
    - COMPLETADO: Ya existia findByAsin(), se usa en ImportTab

11.5 MEJORAS VISUALES

[x] UI-01: Grid de filtros - cambiar a 4 columnas
    - Desktop: 4 columnas
    - Tablet (768px-1024px): 2 columnas
    - Movil (<768px): 1 columna
    - Archivo: assets/css/amazon-product.css linea 132-136
    - COMPLETADO: Media queries actualizadas

[x] UI-02: Icono de filtrar incorrecto
    - Revisar CardRenderer::getFilterIcon()
    - Archivo: Renderer/CardRenderer.php
    - COMPLETADO: SVG corregido a icono de sliders

11.6 DOCUMENTACION

[x] DOC-01: Actualizar HelpTab con todos los atributos nuevos
    - Incluir ejemplos de todos los casos de uso
    - Documentar ids, search, random, hide_filters, pagination, min_rating
    - Archivo: Admin/Tabs/HelpTab.php
    - COMPLETADO: Tabla completa con todos los atributos en espanol

11.7 ARQUITECTURA FUTURA

[x] ARCH-01: Preparar para cambio de API (RapidAPI vs Amazon PA-API)
    - Implementado:
      - Selector de tipo de API en ConfigTab
      - ApiProviderInterface creada con contrato para providers
      - RapidApiProvider implementado (funcional, es el actual)
      - AmazonPaApiProvider implementado (estructura base con TODOs)
      - AmazonApiService refactorizado como Factory/Fachada
      - Campos para PA-API agregados en ConfigTab (Access Key, Secret Key)
    - Archivos creados:
      - Service/ApiProviderInterface.php - Contrato para providers
      - Service/RapidApiProvider.php - Implementacion RapidAPI
      - Service/AmazonPaApiProvider.php - Estructura para PA-API (futura)
    - Archivos modificados:
      - Service/AmazonApiService.php - Factory que selecciona provider
      - Admin/Tabs/ConfigTab.php - Selector de provider y credenciales PA-API
    - COMPLETADO: Arquitectura preparada para migracion futura a PA-API

11.8 CORRECCIONES CRITICAS

[x] BUG-04: [amazon_deals] llama a la API en lugar de leer desde DB
    - Problema: El shortcode [amazon_deals] hace llamadas a la API en tiempo real
    - Solucion: Debe leer productos guardados que tengan descuento (only_deals="1")
    - Archivo: Renderer/ProductRenderer.php (renderDealsShortcode)
    - COMPLETADO: Ahora lee de productos guardados con original_price

11.9 INTEGRIDAD DE DATOS

[x] DATA-01: Verificar que toda la info de la API se guarda en meta
    - Creado archivo API_DATA_STRUCTURE.md con documentacion completa
    - Tabla de mapeo para cada endpoint: search.php, deal.php, asin.php
    - Meta fields actualizados en AmazonProductPlugin.php
    - Nuevos campos agregados a importDeal(): discount_percent, currency, deal_ends_at
    - Archivos modificados:
      - API_DATA_STRUCTURE.md (nuevo) - Documentacion completa
      - Service/ProductImporter.php - Nuevos campos en importDeal()
      - AmazonProductPlugin.php - Meta fields registrados
    - COMPLETADO: Documentacion y mapeo completo API -> Meta fields

[x] DATA-02: Documentar estructura de datos de cada endpoint de API
    - Documentado en API_DATA_STRUCTURE.md:
      - Endpoint search.php: 12 campos documentados
      - Endpoint deal.php: 12 campos documentados
      - Endpoint asin.php: 11 campos documentados
      - Tabla de regiones soportadas
      - Meta fields de WordPress
      - Campos que no se guardan pero podrian ser utiles
    - COMPLETADO: Referencia tecnica completa del plugin

11.10 CONTROL DE USO DE API

[x] API-01: Sistema de contador de llamadas API mensuales
    - Implementado:
      a) Configuracion de dia de inicio del ciclo (1-28) - en ConfigTab
      b) Contador persistente de llamadas por ciclo - ApiUsageTracker
      c) Visualizacion en panel admin: X llamadas usadas de Y limite - ConfigTab
      d) Opcion de limite mensual configurable - ConfigTab
      e) Advertencia al acercarse al limite (80%) y bloqueo al 100%
    - Archivos creados/modificados: 
      - Service/ApiUsageTracker.php (nuevo) - Toda la logica de tracking
      - Admin/Tabs/ConfigTab.php - UI de estadisticas y configuracion
      - Service/AmazonApiService.php - Integrado hook en makeRequest()
    - COMPLETADO: El sistema registra cada llamada, muestra estadisticas y bloquea al llegar al limite

[x] API-02: Historial de llamadas API
    - Registrar: fecha, endpoint, parametros, exito/fallo - ApiUsageTracker::recordCall()
    - Mostrar ultimas N llamadas en admin - ApiUsageTracker::getRecentCalls()
    - Util para debugging y optimizacion
    - COMPLETADO: El historial se guarda, pendiente agregar UI para visualizarlo (opcional)

11.11 CORRECCIONES EN IMPORTADOR MANUAL HTML (10/12/2024)

[x] HTML-BUG-01: URL se guardaba siempre como amazon.com
    - Problema: La URL se construia hardcodeada como amazon.com/dp/ASIN
    - Solucion: Detectar dominio desde el HTML (amazon.es, amazon.de, etc.)
    - Nuevo metodo extractAmazonDomain() busca en canonical URL, og:url y enlaces
    - Archivo: Service/HtmlParserService.php

[x] HTML-BUG-02: Currency siempre era USD
    - Problema: La moneda se hardcodeaba como 'USD' sin importar el HTML
    - Solucion: Nuevo metodo extractCurrency() que detecta simbolos de moneda
    - Detecta EUR (simbolo € o dominio .es/.de/.fr/.it), GBP, CAD, MXN, etc.
    - Mapa de monedas por dominio de Amazon como fallback
    - Archivo: Service/HtmlParserService.php

[x] HTML-BUG-03: Numero de reviews incorrecto
    - Problema: Extraia "2" en vez de "115" porque el regex no capturaba bien
    - Solucion: Nuevos patrones de busqueda:
      1. aria-label="115 Resenas"
      2. id="acrCustomerReviewText">(115)
      3. <span class="a-size-small">(115)</span>
      4. Fallback: numero seguido de "calificaciones/reviews/etc."
    - Archivo: Service/HtmlParserService.php

[x] HTML-BUG-04: Precio original extraia valores absurdos (ej: 10000 en vez de 100,00)
    - Problema: El metodo extractOriginalPrice no manejaba formato europeo
    - Causa: Buscaba patrones con $ y no con €, y el intento 4 capturaba cualquier numero
    - Solucion: Reescribir completamente el metodo para:
      1. Buscar "Precio recomendado:" + precio en formato 100,00€
      2. Buscar en basisPrice con span.a-offscreen
      3. Buscar precio tachado (data-a-strike="true")
      4. Fallback para formato USD
    - Se elimino el intento problematico que buscaba "todos los precios" y tomaba el mayor
    - Archivo: Service/HtmlParserService.php

RESUMEN DE PROGRESO (10/12/2024):
- Completados: 21 items (TODAS las tareas de mejoras pendientes)
- Pendientes: 0 items

TAREAS COMPLETADAS EN ESTA SESION:
- BUG-03: Contador de resultados muestra total real
- API-01: Sistema de contador de llamadas API mensuales
- API-02: Historial de llamadas API
- FEAT-07: Panel de control de actualizacion programada
- FEAT-08: Actualizacion inteligente de productos guardados
- DATA-01: Verificar y documentar mapeo API -> Meta fields
- DATA-02: Documentar estructura de datos de cada endpoint
- ARCH-01: Preparar para cambio de API (RapidAPI vs Amazon PA-API)