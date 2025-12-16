# Plan: Secciones Dinámicas para Shortcodes de Amazon Products

## Fecha: 2025-12-16

---

## Problema Actual

Actualmente los shortcodes se escriben directamente en el HTML con valores fijos:

```
[amazon_products only_deals="1" orderby="random" limit="4" hide_filters="1" pagination="0"]

[amazon_products search="pala" orderby="random" exclude="paletero,bolsa,funda,protector,mochila,raquetero,grip,overgrip,pelota,bote"]
```

**Problemas:**
- El cliente necesita editar HTML para cambiar parámetros
- No hay forma visual de gestionar las secciones
- Difícil saber qué productos aparecen en cada sección
- No hay forma de excluir productos específicos de una sección

---

## Solución Propuesta

### Nuevo Atributo: `section`

```
[amazon_products section="palas" search="pala" orderby="random" exclude="..."]
```

- `section="palas"` → Identificador único de la sección
- Los demás atributos son **valores por defecto**
- Si existe configuración guardada en la BD, se usa esa en lugar de los defaults
- Si se resetea, vuelve a los valores del shortcode

---

## Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────────┐
│                          FRONTEND (Shortcode)                        │
├─────────────────────────────────────────────────────────────────────┤
│  [amazon_products section="palas" search="pala"                      │
│   orderby="random" exclude="paletero,bolsa,funda"]                   │
│                                                                      │
│  1. Busca config guardada para "palas" en wp_options                 │
│  2. Si existe → usa valores guardados                                │
│  3. Si no existe → usa valores del shortcode                         │
│  4. Renderiza productos según la configuración activa                │
└─────────────────────────────────────────────────────────────────────┘
        │
        │ Sincronización
        ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    ADMIN (Nueva Tab: Secciones)                      │
├─────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  SECCIONES DE PRODUCTOS                                      │    │
│  ├─────────────────────────────────────────────────────────────┤    │
│  │                                                              │    │
│  │  ▼ Sección: palas (12 productos)              [Restaurar]   │    │
│  │  ├─ Search: [pala          ]                                 │    │
│  │  ├─ Exclude: [paletero,bolsa,funda...]                       │    │
│  │  ├─ Order: [random ▼]                                        │    │
│  │  ├─ Limit: [12]                                              │    │
│  │  └─ Productos excluidos:                                     │    │
│  │     ☐ Pala XForce Pro (ID: 123)                              │    │
│  │     ☐ Pala Bullpadel (ID: 456)                               │    │
│  │                                                              │    │
│  │  [Previsualizar] [Guardar cambios]                           │    │
│  │                                                              │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ▶ Sección: ofertas (8 productos)                [Restaurar]        │
│  ▶ Sección: accesorios (24 productos)            [Restaurar]        │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
        │
        │ Sincronización bidireccional
        ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    PRODUCTO INDIVIDUAL (Metabox)                     │
├─────────────────────────────────────────────────────────────────────┤
│  Panel lateral en cada producto:                                     │
│                                                                      │
│  ┌─────────────────────────────────────────────┐                    │
│  │  SECCIONES DONDE APARECE                    │                    │
│  ├─────────────────────────────────────────────┤                    │
│  │                                             │                    │
│  │  ☑ palas                                    │                    │
│  │  ☐ ofertas (excluido manualmente)           │                    │
│  │  ☑ accesorios                               │                    │
│  │                                             │                    │
│  │  [Guardar]                                  │                    │
│  └─────────────────────────────────────────────┘                    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Modelo de Datos

### 1. Opción de WordPress: `glory_amazon_sections`

```php
[
    'palas' => [
        'defaults' => [
            'search' => 'pala',
            'orderby' => 'random',
            'exclude' => 'paletero,bolsa,funda,protector',
            'limit' => 12,
            'only_deals' => '0',
            'only_prime' => '0',
            'min_price' => '',
            'max_price' => '',
            'min_rating' => '',
        ],
        'overrides' => [
            'search' => 'pala padel',
            'exclude' => 'paletero,bolsa,funda,protector,junior',
        ],
        'excluded_ids' => [123, 456, 789],
        'last_modified' => '2025-12-16 14:30:00',
        'source_file' => 'page-palas.php',
        'source_line' => 45,
    ],
    'ofertas' => [
        'defaults' => [...],
        'overrides' => [],
        'excluded_ids' => [],
        ...
    ]
]
```

### 2. Meta de Producto: `_glory_section_visibility`

```php
[
    'palas' => 'visible',
    'ofertas' => 'excluded',
    'accesorios' => 'visible',
]
```

---

## Flujo de Sincronización

### Al renderizar el shortcode:

```
1. Parsear atributos del shortcode
2. Si existe `section`:
   a. Registrar sección si es nueva (guardar defaults)
   b. Cargar overrides guardados
   c. Mergear: defaults + overrides
   d. Aplicar excluded_ids al query
3. Si no existe `section`:
   a. Comportamiento actual (usar atributos directos)
4. Renderizar productos
```

### Al guardar desde Admin:

```
1. Usuario modifica parámetros de sección
2. Guardar solo los campos que difieren de defaults (overrides)
3. Sincronizar excluded_ids con meta de productos
```

### Al restaurar defaults (Sync Manager):

```
1. Eliminar overrides de la sección
2. Limpiar excluded_ids
3. La próxima renderización usa los defaults del shortcode
```

---

## Estructura de Archivos

### Nuevos archivos a crear:

```
Glory/src/Plugins/AmazonProduct/
├── Admin/
│   └── Tabs/
│       └── SectionsTab.php          # Nueva tab de secciones
├── Service/
│   ├── SectionManager.php           # CRUD de secciones
│   ├── SectionRegistry.php          # Registro de secciones activas
│   └── SectionSyncService.php       # Sincronización con Sync Manager
├── Model/
│   └── Section.php                  # Modelo de sección
├── Admin/
│   └── Metabox/
│       └── ProductSectionsMetabox.php  # Metabox en productos
└── assets/
    ├── css/
    │   └── sections-tab.css         # Estilos de la tab
    └── js/
        └── sections-tab.js          # Interactividad de la tab
```

### Archivos a modificar:

```
├── Renderer/
│   └── ProductRenderer.php          # Añadir lógica de sección
├── Renderer/
│   └── QueryBuilder.php             # Añadir filtro excluded_ids
├── Controller/
│   └── ClientAdminController.php    # Registrar nueva tab
└── AmazonProductPlugin.php          # Registrar hooks de metabox
```

---

## Fases de Implementación

### Fase 1: Modelo y Servicio Base - COMPLETADO
1. [x] Crear `Model/Section.php` - Estructura de datos
2. [x] Crear `Service/SectionManager.php` - CRUD
3. [x] Crear `Service/SectionRegistry.php` - Registro automático

### Fase 2: Integración con Shortcode - COMPLETADO
1. [x] Modificar `ProductRenderer.php` - Detectar atributo `section`
2. [x] Modificar `QueryBuilder.php` - Añadir filtro `excluded_ids`
3. [x] Crear lógica de merge (defaults + overrides)

### Fase 3: Tab de Administración - COMPLETADO
1. [x] Crear `Admin/Tabs/SectionsTab.php`
2. [x] Crear vista HTML de la tab
3. [x] Crear `assets/js/sections-tab.js`
4. [x] Crear `assets/css/sections-tab.css`
5. [x] Registrar tab en `AdminController.php`

### Fase 4: Lista de Productos por Sección - COMPLETADO
1. [x] Implementar preview de productos en cada sección
2. [x] Implementar paginación AJAX (modal preview)
3. [x] Implementar exclusión individual de productos

### Fase 5: Metabox en Productos - COMPLETADO
1. [x] Crear `Admin/Metabox/ProductSectionsMetabox.php`
2. [x] Registrar metabox en el post type `amazon_product`
3. [x] Implementar guardado de visibilidad por sección

### Fase 6: Sincronización con Sync Manager
1. [ ] Crear `Service/SectionSyncService.php`
2. [ ] Integrar con el sistema de "Restaurar a Default" de Glory
3. [ ] Implementar hook de restauración

### Fase 7: Testing y Documentación
1. [ ] Probar flujo completo
2. [ ] Actualizar USAGE_GUIDE.md
3. [ ] Documentar nuevos atributos y funcionalidades

---

## UI de la Tab de Secciones

### Vista Compacta (Lista Colapsable)

```
┌─────────────────────────────────────────────────────────────────────┐
│  GESTIÓN DE SECCIONES                                    [+ Info]   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Las secciones se detectan automáticamente desde los shortcodes    │
│  que usan el atributo section="nombre".                            │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ ▼ palas                                      12 productos     │  │
│  │                                                               │  │
│  │   Configuración:                                              │  │
│  │   ├─ Buscar:  [pala padel        ] (default: pala)           │  │
│  │   ├─ Excluir: [paletero,bolsa,...                  ]         │  │
│  │   ├─ Orden:   [Aleatorio ▼]                                  │  │
│  │   ├─ Límite:  [12]                                           │  │
│  │   └─ Prime:   ☐   Ofertas: ☐                                 │  │
│  │                                                               │  │
│  │   Productos excluidos manualmente: 2  [Ver/Editar]            │  │
│  │                                                               │  │
│  │   [Previsualizar] [Guardar] [Restaurar defaults]              │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ ▶ ofertas                                     8 productos     │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ ▶ accesorios                                 24 productos     │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Modal de Productos Excluidos

```
┌─────────────────────────────────────────────────────────────────────┐
│  PRODUCTOS EXCLUIDOS DE "palas"                              [X]    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Buscar: [                                        ] 🔍              │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  ☑ Pala XForce Pro 2024                           [Incluir] │    │
│  │     ID: 123 | Precio: €199.99                               │    │
│  ├─────────────────────────────────────────────────────────────┤    │
│  │  ☑ Pala Bullpadel Vertex                          [Incluir] │    │
│  │     ID: 456 | Precio: €249.99                               │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  Mostrando 2 de 2 productos excluidos                              │
│                                                                     │
│  [Incluir seleccionados]                              [Cerrar]      │
└─────────────────────────────────────────────────────────────────────┘
```

### Modal de Previsualización

```
┌─────────────────────────────────────────────────────────────────────┐
│  PREVIEW: palas                                              [X]    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Mostrando 12 productos con la configuración actual:               │
│                                                                     │
│  ┌─────────┬─────────┬─────────┬─────────┐                         │
│  │ [img]   │ [img]   │ [img]   │ [img]   │                         │
│  │ Pala A  │ Pala B  │ Pala C  │ Pala D  │                         │
│  │ €199    │ €249    │ €179    │ €299    │                         │
│  │ [Excl.] │ [Excl.] │ [Excl.] │ [Excl.] │                         │
│  ├─────────┼─────────┼─────────┼─────────┤                         │
│  │ [img]   │ [img]   │ [img]   │ [img]   │                         │
│  │ Pala E  │ Pala F  │ Pala G  │ Pala H  │                         │
│  │ €189    │ €219    │ €159    │ €279    │                         │
│  │ [Excl.] │ [Excl.] │ [Excl.] │ [Excl.] │                         │
│  └─────────┴─────────┴─────────┴─────────┘                         │
│                                                                     │
│  < 1 2 3 >                                                         │
│                                                                     │
│  [Cerrar]                                                          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Endpoints AJAX Necesarios

| Acción                        | Descripción                                      |
| ----------------------------- | ------------------------------------------------ |
| `glory_get_sections`          | Obtener lista de todas las secciones             |
| `glory_get_section`           | Obtener detalle de una sección                   |
| `glory_save_section`          | Guardar cambios de una sección                   |
| `glory_restore_section`       | Restaurar a defaults                             |
| `glory_preview_section`       | Obtener preview de productos                     |
| `glory_exclude_product`       | Excluir producto de sección                      |
| `glory_include_product`       | Incluir producto en sección                      |
| `glory_get_section_products`  | Lista paginada de productos                      |
| `glory_save_product_sections` | Guardar secciones de un producto (desde metabox) |

---

## Consideraciones Técnicas

### Performance
- Las secciones se cachean en `wp_options` como array serializado
- La lista de productos usa paginación AJAX (no carga todo)
- El preview usa el mismo QueryBuilder que el frontend

### Compatibilidad
- Shortcodes sin `section` siguen funcionando igual
- Shortcodes con `section` pero sin config guardada usan defaults
- El sistema es retrocompatible

### Seguridad
- Validar nonces en todos los endpoints AJAX
- Sanitizar todos los inputs
- Verificar capabilities del usuario

---

## Ejemplo de Uso

### Antes (hardcoded):
```html
[amazon_products search="pala" orderby="random" exclude="paletero,bolsa,funda,protector,mochila,raquetero,grip,overgrip,pelota,bote"]
```

### Después (dinámico):
```html
[amazon_products section="palas" search="pala" orderby="random" exclude="paletero,bolsa,funda,protector,mochila,raquetero,grip,overgrip,pelota,bote"]
```

**Comportamiento:**
1. Primera vez: Se registra la sección "palas" con estos defaults
2. El cliente puede ir al admin → Secciones → palas
3. Modificar el search, exclude, order, etc.
4. Excluir productos específicos del grid
5. Los cambios se reflejan en el frontend inmediatamente
6. Si quiere volver atrás: botón "Restaurar defaults"

---

## Preguntas Abiertas

1. **¿Múltiples shortcodes con la misma sección?**
   - Opción A: Todos usan la misma config (recomendado)
   - Opción B: Cada instancia puede tener su propia config

2. **¿Registrar secciones manualmente?**
   - Actualmente: Solo se registran al renderizar el shortcode
   - Alternativa: Permitir crear secciones desde el admin

3. **¿Historial de cambios?**
   - ¿Guardar versiones anteriores de la configuración?
   - ¿Mostrar quién y cuándo modificó?

---

*Plan creado: 2025-12-16*
*Autor: Sistema Glory*
