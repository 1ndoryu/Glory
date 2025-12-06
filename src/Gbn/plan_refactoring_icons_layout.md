# 🔧 Plan de Refactorización: Iconos SVG y Opciones de Layout

> **Fecha:** 2025-12-06  
> **Estado:** 📋 En Análisis  
> **Prioridad:** Media-Alta  
> **Principios:** SOLID (SRP, DRY), Centralización

---

## 📊 Resumen Ejecutivo

Este documento detalla los problemas de arquitectura encontrados relacionados con:
1. **Iconos SVG repetidos** en múltiples componentes PHP y archivos JS
2. **Opciones de Layout no centralizadas** entre componentes que conceptualmente comparten la misma funcionalidad
3. **Inconsistencias estructurales** entre componentes (formato de schema, nombres de campos, tipos, tabs)

### Impacto del Problema
- ❌ Código duplicado en 8+ traits PHP y 10+ archivos JS
- ❌ Iconos inconsistentes para el mismo concepto (ej: grid tiene ~4 variantes)
- ❌ Dificultad de mantenimiento: cambiar un icono requiere editar múltiples archivos
- ❌ Posibles inconsistencias visuales en la UI
- ❌ **MenuComponent usa formato legacy** (array literal, value/label en inglés)
- ❌ **Nombres de campos inconsistentes** (`displayMode` vs `layout`, `flexJustify` vs `justifyContent`)
- ❌ **Tabs con formato mixto** (minúsculas sin tilde vs capitalizadas con tilde)
- ❌ **Tipos de campo diferentes** para el mismo concepto (gap como text vs slider)

---

## 🔍 Análisis Detallado

### Problema 1: Iconos SVG Repetidos

#### 📁 Archivos PHP Afectados

| Archivo                     | Cantidad de SVGs | Categoría                      |
| --------------------------- | ---------------- | ------------------------------ |
| `Traits/HasFlexbox.php`     | 18 iconos        | Layout, dirección, alineación  |
| `Traits/HasBackground.php`  | 17 iconos        | Posición, tamaño, repetición   |
| `Traits/HasPositioning.php` | 9 iconos         | Posición, overflow             |
| `Traits/HasBorder.php`      | 4 iconos         | Estilos de borde               |
| `PostRenderComponent.php`   | 15 iconos        | Layout, dirección (DUPLICADOS) |
| `PrincipalComponent.php`    | 4 iconos         | Alineación de contenido        |

#### 📁 Archivos JS Afectados

| Archivo                         | Cantidad de SVGs | Categoría        |
| ------------------------------- | ---------------- | ---------------- |
| `ui/panel-render.js`            | 12 iconos        | Estados, tabs    |
| `ui/theme/render.js`            | 15 iconos        | Tabs, navegación |
| `ui/panel-fields/utils.js`      | 4 iconos         | Spacing          |
| `ui/panel-fields/typography.js` | 6 iconos         | Tipografía       |
| `ui/panel-fields/toggle.js`     | 2 iconos         | On/Off           |
| `ui/panel-fields/image.js`      | 1 icono          | Galería          |
| `ui/panel-fields/dimensions.js` | 4 iconos         | Dimensiones      |

#### 🔴 Inconsistencias Detectadas

**Ejemplo: Icono de Grid**

```php
// En HasFlexbox.php (línea 17)
'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'

// En PostRenderComponent.php (línea 178)
'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'
```

**Diferencias sutiles:**
- Orden de atributos (`width/height` antes o después de `viewBox`)
- Misma estructura visual pero posibles diferencias de rendering

**Ejemplo: Icono de Flex Direction**

```php
// En HasFlexbox.php - Horizontal
'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>'

// En PostRenderComponent.php - Horizontal  
'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>'
```

**Diferencias:**
- Diferentes paths (flecha completa vs flecha parcial)
- Diferentes dimensiones de línea
- **Iconos visualmente DIFERENTES para el mismo concepto**

---

### Problema 2: Opciones de Layout No Centralizadas

#### Comparación: `PrincipalComponent` vs `PostRenderComponent`

| Aspecto                 | PrincipalComponent    | PostRenderComponent          |
| ----------------------- | --------------------- | ---------------------------- |
| Usa `HasFlexbox` trait  | ✅ Sí                  | ❌ No                         |
| Usa `HasGrid` trait     | ✅ Sí                  | ❌ No                         |
| Define opciones propias | Solo alineación       | Todas las opciones de layout |
| Campo layout            | `layout`              | `displayMode`                |
| Campo columnas grid     | `gridColumns` (trait) | `gridColumns` (propio)       |

#### 🔴 Problema Principal

`PostRenderComponent` **no usa los traits existentes** (`HasFlexbox`, `HasGrid`), sino que **reimplementa** las mismas opciones con:
- Nombres de campos diferentes (`displayMode` vs `layout`)
- Iconos diferentes
- Configuraciones ligeramente diferentes

**Código actual PostRenderComponent (líneas 172-300):**
```php
// Define displayMode en lugar de usar HasFlexbox
Option::iconGroup('displayMode', 'Modo de Visualización')
    ->options([
        ['valor' => 'grid', ...],   // Debería usar HasGrid
        ['valor' => 'flex', ...],   // Debería usar HasFlexbox
        ['valor' => 'block', ...],
    ])

// Reimplementa flexDirection, flexWrap, alignItems, justifyContent
// cuando HasFlexbox ya los tiene definidos
```

---

### Problema 3: Inconsistencias Estructurales entre Componentes

#### 🔴 Diferencias en Formato de Schema

| Componente            | Formato Schema | Usa SchemaBuilder | Usa Traits                             |
| --------------------- | -------------- | ----------------- | -------------------------------------- |
| `MenuComponent`       | Array literal  | ❌ No              | ❌ No usa ninguno                       |
| `FormComponent`       | SchemaBuilder  | ✅ Sí              | ✅ HasSpacing, HasBackground, HasBorder |
| `PostRenderComponent` | SchemaBuilder  | ✅ Sí              | ⚠️ Parcial (no usa HasFlexbox/HasGrid)  |
| `PrincipalComponent`  | SchemaBuilder  | ✅ Sí              | ✅ Usa todos los traits                 |
| `SecundarioComponent` | SchemaBuilder  | ✅ Sí              | ✅ Usa todos los traits                 |
| `InputComponent`      | SchemaBuilder  | ✅ Sí              | ✅ HasSpacing, HasBorder                |

#### 🔴 Inconsistencias en Formato de Opciones

**MenuComponent usa formato diferente:**
```php
// MenuComponent - formato legacy (value/label en inglés, estructura plana)
'options' => [
    [
        'value' => 'wordpress',  // ❌ Usa 'value' en lugar de 'valor'
        'icon' => '...',
        'label' => 'WordPress'   // ❌ Usa 'label' en lugar de 'etiqueta'
    ],
]

// Resto de componentes - formato estándar Option:: (valor/etiqueta en español)
->options([
    ['valor' => 'post', 'etiqueta' => 'Entradas'],
])
```

**MenuComponent usa formato asociativo para select:**
```php
// MenuComponent línea 171-177
'options' => [
    '300' => 'Light',      // ❌ Formato key => value
    '400' => 'Normal',
]

// Formato estándar en otros componentes
->options([
    ['valor' => '300', 'etiqueta' => 'Light'],
    ['valor' => '400', 'etiqueta' => 'Normal'],
])
```

#### 🔴 Inconsistencias en Nombres de Tabs

| Componente            | Tabs Utilizados                                | Formato                  |
| --------------------- | ---------------------------------------------- | ------------------------ |
| `MenuComponent`       | `configuracion`, `estilo`, `movil`             | minúsculas sin tilde     |
| `FormComponent`       | `Configuración`, `Mensajes`, `Email`, `Estilo` | Capitalizadas con tildes |
| `PostRenderComponent` | `Query`, `Layout`, `Estilo`, `Interacción`     | Capitalizadas            |
| `PrincipalComponent`  | `Contenido`, `Estilo`, `Avanzado`              | Capitalizadas            |
| `InputComponent`      | `Configuración`, `Estilo`, `Tipografía`        | Capitalizadas            |

**Problema:** El JavaScript del panel puede tener problemas al detectar tabs si hay inconsistencia.

#### 🔴 Inconsistencias en Nombres de Campos (Sinónimos)

| Concepto            | HasFlexbox     | PostRender       | Principal             | Menu                   |
| ------------------- | -------------- | ---------------- | --------------------- | ---------------------- |
| **Layout Mode**     | `layout`       | `displayMode`    | `layout`              | `layout` (orientación) |
| **Justify Content** | `flexJustify`  | `justifyContent` | `flexJustify` (trait) | ❌ N/A                  |
| **Align Items**     | `flexAlign`    | `alignItems`     | `flexAlign` (trait)   | ❌ N/A                  |
| **Espaciado**       | `gap` (slider) | `gap` (text)     | `gap` (slider)        | `gap` (text)           |

**Problema:** El mismo concepto tiene nombres diferentes, lo que dificulta:
- La reutilización de estilos CSS
- El mapper de estilos en StyleMapper
- La consistencia del panel de opciones

#### 🔴 Inconsistencias en Tipos de Campos para Mismo Concepto

```php
// PostRenderComponent - Gap como texto
Option::text('gap', 'Espaciado (Gap)')
    ->default('20px')

// HasFlexbox - Gap como slider
Option::slider('gap', 'Separación (Gap)')
    ->unit('px')
    ->min(0)
    ->max(120)

// MenuComponent - Gap como texto sin validación
'id' => 'gap',
'type' => 'text',
'default' => '2rem',
```

#### 🔴 Inconsistencia: Operadores de Condición

```php
// MenuComponent - usa '===' (estricto)
'condition' => ['menuSource', '===', 'wordpress']

// PostRenderComponent - usa '=='
->condition(['displayMode', '==', 'grid'])

// PostRenderComponent - usa 'in'
->condition(['displayMode', 'in', ['grid', 'flex']])

// HasFlexbox - usa formato corto (2 parámetros)
->condition('layout', 'flex')
```

**Problema:** El handler de condiciones debe soportar todos estos formatos o fallará.

#### 🔴 Traits No Utilizados Donde Deberían

| Componente            | Debería Usar                     | Actualmente                               |
| --------------------- | -------------------------------- | ----------------------------------------- |
| `PostRenderComponent` | `HasFlexbox`, `HasGrid`          | ❌ Reimplementa todo                       |
| `MenuComponent`       | `HasTypography` (si existe)      | ❌ Define opciones inline                  |
| `FormComponent`       | `HasFlexbox` para layout interno | ❌ No tiene opciones de layout             |
| `ButtonComponent`     | `HasTypography`                  | ⚠️ Usa helper, pero inline styles manuales |

### Problema 4: Implementación Manual de Estilos Comunes

Algunos componentes implementan manualmente opciones que deberían ser estándar:

**Dimensiones (Width/Height):**
- `PrincipalComponent`: Define `height` y `maxAncho` manualmente.
- `ButtonComponent`: Define `width` manualmente.
- `PostRenderComponent`: No tiene control de dimensiones explícito.

**Solución Propuesta:** Crear `HasDimensions` trait.

### Problema 5: Lógica de Renderizado Dispersa

`AbstractComponent` define `getTemplate()` que devuelve un string HTML estático. Los componentes complejos (Menu, PostRender) inyectan lógica en métodos auxiliares o en el propio template string, dificultando la separación de vista/lógica.

### Problema 6: Inconsistencia en Tipografía (Typography)

Existen múltiples formas de definir tipografía, creando confusión y código duplicado:

| Método                 | Componentes           | Descripción                                                             |
| ---------------------- | --------------------- | ----------------------------------------------------------------------- |
| `Option::typography()` | `ButtonComponent`     | Retorna un campo único compuesto ('typography')                         |
| `HasTypography` Trait  | Ninguno (actualmente) | Retorna array de campos individuales (`fontSize`, `color`, `textAlign`) |
| Definición Manual      | `MenuComponent`       | Define manualmente `fontSize`, `fontWeight`, etc.                       |

**Solución**: Decidir una estrategia única (Preferiblemente el Trait que retorne opciones estándar, ya sea individuales o compuestas) y aplicarla en todos lados.

---

### Problema 7: Elementos sin Iconos Definidos (Tabs y Componentes)

#### 🔴 Tabs sin Iconos (Dependencia de Hardcoding JS)

Actualmente, los iconos de las pestañas (Tabs) están **hardcodeados en `panel-render.js`** en un objeto mapa:

```javascript
var icons = {
    'Contenido': '<svg...>',
    'Estilo': '<svg...>',
    'Avanzado': '<svg...>',
    // ...
};
```

**Consecuencias:**
1. **MenuComponent** usa tabs en minúsculas (`configuracion`, `estilo`, `movil`), por lo que **no muestran icono**.
2. **Nuevos tabs** no tienen forma de definir su icono desde PHP.
3. **Inconsistencia Visual**: Algunos paneles tienen iconos en tabs y otros no.

#### 🔴 Componentes de Formulario sin Iconos Visuales

Componentes como `FormComponent` y sus hijos (`Select`, `Input`) definen opciones críticas sin apoyo visual:
- **Métodos HTTP (GET/POST)**: Solo texto, sin distinción visual.
- **Toggle Options**: Muchos `select` funcionan como toggles pero no tienen iconos (ej: alineaciones, transformaciones de texto en Menu).

**Ejemplo en MenuComponent:**
```php
'options' => [
    '300' => 'Light',  // Sin icono
    '400' => 'Normal', // Sin icono
]
```
Esto reduce la usabilidad y la apariencia "premium" del constructor.

---

## ✅ Solución Propuesta

### Fase 1: Crear Registro Centralizado de Iconos (PHP)

```
Glory/src/Gbn/Icons/
├── IconRegistry.php          → Clase principal del registro
├── LayoutIcons.php           → Iconos de layout (flex, grid, block)
├── DirectionIcons.php        → Iconos de dirección (row, column)
├── AlignmentIcons.php        → Iconos de alineación (justify, align)
├── BackgroundIcons.php       → Iconos de fondo
├── PositioningIcons.php      → Iconos de posición
├── BorderIcons.php           → Iconos de bordes
└── ActionIcons.php           → Iconos de acciones (edit, delete, etc)
```

#### Implementación: `IconRegistry.php`

```php
<?php
namespace Glory\Gbn\Icons;

/**
 * Registro centralizado de iconos SVG para componentes GBN.
 * 
 * Principio: Single Source of Truth para todos los iconos.
 * Cualquier componente que necesite un icono debe obtenerlo de aquí.
 */
class IconRegistry
{
    private static array $icons = [];
    private static bool $initialized = false;

    /**
     * Inicializa el registro cargando todos los iconos.
     */
    public static function init(): void
    {
        if (self::$initialized) return;
        
        self::$icons = array_merge(
            LayoutIcons::all(),
            DirectionIcons::all(),
            AlignmentIcons::all(),
            BackgroundIcons::all(),
            PositioningIcons::all(),
            BorderIcons::all(),
            ActionIcons::all()
        );
        
        self::$initialized = true;
    }

    /**
     * Obtiene un icono por su clave.
     * 
     * @param string $key Clave única del icono (ej: 'layout.grid')
     * @param array $attrs Atributos opcionales a sobrescribir
     * @return string SVG del icono
     */
    public static function get(string $key, array $attrs = []): string
    {
        self::init();
        
        if (!isset(self::$icons[$key])) {
            error_log("IconRegistry: Icono no encontrado: {$key}");
            return self::getFallback();
        }
        
        $icon = self::$icons[$key];
        
        // Permitir sobrescribir width/height
        if (!empty($attrs)) {
            foreach ($attrs as $attr => $value) {
                $icon = preg_replace(
                    "/{$attr}=\"[^\"]*\"/",
                    "{$attr}=\"{$value}\"",
                    $icon
                );
            }
        }
        
        return $icon;
    }

    /**
     * Obtiene múltiples iconos para iconGroup.
     * 
     * @param array $keys Array de claves ['layout.grid', 'layout.flex', ...]
     * @return array Opciones formateadas para iconGroup
     */
    public static function getGroup(array $keys): array
    {
        $options = [];
        foreach ($keys as $key => $config) {
            $iconKey = is_string($config) ? $config : $config['icon'];
            $options[] = [
                'valor' => $config['valor'] ?? $key,
                'etiqueta' => $config['etiqueta'] ?? ucfirst($key),
                'icon' => self::get($iconKey)
            ];
        }
        return $options;
    }

    private static function getFallback(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';
    }
}
```

#### Implementación: `LayoutIcons.php`

```php
<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos relacionados con modos de layout.
 */
class LayoutIcons
{
    public static function all(): array
    {
        return [
            // === DISPLAY MODE ===
            'layout.block' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
            
            'layout.flex' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>',
            
            'layout.grid' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>',
            
            // === FLEX DIRECTION ===
            'direction.row' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>',
            
            'direction.column' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>',
            
            // === FLEX WRAP ===
            'wrap.nowrap' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>',
            
            'wrap.wrap' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>',
            
            // === JUSTIFY CONTENT ===
            'justify.start' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.center' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.end' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.between' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>',
            
            'justify.around' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>',
            
            // === ALIGN ITEMS ===
            'align.stretch' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>',
            
            'align.start' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>',
            
            'align.center' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
            
            'align.end' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>',
        ];
    }
}
```

---

### Fase 2: Crear Registro Centralizado de Iconos (JavaScript)

```
assets/js/ui/icons/
├── index.js              → Registro principal
├── layout-icons.js       → Iconos de layout
├── action-icons.js       → Iconos de acciones
├── state-icons.js        → Iconos de estados
└── tab-icons.js          → Iconos de tabs
```

#### Implementación: `index.js`

```javascript
/**
 * IconRegistry - Registro centralizado de iconos SVG para GBN Builder
 * 
 * Uso:
 *   import { Icons } from './icons/index.js';
 *   const gridIcon = Icons.get('layout.grid');
 */

import { layoutIcons } from './layout-icons.js';
import { actionIcons } from './action-icons.js';
import { stateIcons } from './state-icons.js';
import { tabIcons } from './tab-icons.js';

export const Icons = {
    _registry: {
        ...layoutIcons,
        ...actionIcons,
        ...stateIcons,
        ...tabIcons
    },

    /**
     * Obtiene un icono por su clave
     * @param {string} key - Clave del icono (ej: 'layout.grid')
     * @param {Object} attrs - Atributos opcionales
     * @returns {string} SVG del icono
     */
    get(key, attrs = {}) {
        let icon = this._registry[key];
        
        if (!icon) {
            console.warn(`IconRegistry: Icono no encontrado: ${key}`);
            return this._fallback();
        }

        // Sobrescribir atributos si se proporcionan
        if (Object.keys(attrs).length > 0) {
            Object.entries(attrs).forEach(([attr, value]) => {
                const regex = new RegExp(`${attr}="[^"]*"`, 'g');
                icon = icon.replace(regex, `${attr}="${value}"`);
            });
        }

        return icon;
    },

    /**
     * Obtiene múltiples iconos como array de opciones
     */
    getOptions(keys) {
        return keys.map(key => ({
            value: key.split('.').pop(),
            icon: this.get(key)
        }));
    },

    _fallback() {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
    }
};

// Exponer globalmente para compatibilidad
window.GbnIcons = Icons;
```

---

### Fase 3: Crear Trait Unificado de Layout

```php
<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;

/**
 * Trait HasLayoutOptions - Unifica todas las opciones de layout
 * 
 * Reemplaza y combina:
 * - HasFlexbox (parcialmente)
 * - HasGrid (parcialmente)  
 * - Opciones duplicadas en PostRenderComponent
 * 
 * Configuraciones disponibles:
 * - 'basic': solo displayMode (block/flex/grid)
 * - 'flex': displayMode + todas las opciones flex
 * - 'grid': displayMode + todas las opciones grid
 * - 'full': displayMode + flex + grid
 */
trait HasLayoutOptions
{
    /**
     * Obtiene las opciones de layout según el nivel de detalle requerido.
     * 
     * @param string $level Nivel: 'basic', 'flex', 'grid', 'full'
     * @param string $fieldPrefix Prefijo para los campos (ej: 'display' vs 'layout')
     * @return Option[]
     */
    protected function getLayoutOptions(string $level = 'full', string $fieldPrefix = 'layout'): array
    {
        $options = [];

        // Opción principal de modo de visualización
        $options[] = Option::iconGroup($fieldPrefix, 'Modo de Visualización')
            ->options([
                [
                    'valor' => 'block',
                    'etiqueta' => 'Bloque',
                    'icon' => IconRegistry::get('layout.block')
                ],
                [
                    'valor' => 'flex',
                    'etiqueta' => 'Flexbox',
                    'icon' => IconRegistry::get('layout.flex')
                ],
                [
                    'valor' => 'grid',
                    'etiqueta' => 'Grid',
                    'icon' => IconRegistry::get('layout.grid')
                ],
            ])
            ->default('block');

        // Opciones Flex
        if (in_array($level, ['flex', 'full'])) {
            $options = array_merge($options, $this->getFlexLayoutOptions($fieldPrefix));
        }

        // Opciones Grid
        if (in_array($level, ['grid', 'full'])) {
            $options = array_merge($options, $this->getGridLayoutOptions($fieldPrefix));
        }

        return $options;
    }

    /**
     * Opciones específicas de Flexbox.
     */
    protected function getFlexLayoutOptions(string $conditionField = 'layout'): array
    {
        return [
            Option::iconGroup('flexDirection', 'Dirección')
                ->options([
                    ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => IconRegistry::get('direction.row')],
                    ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => IconRegistry::get('direction.column')],
                ])
                ->default('row')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup('flexWrap', 'Envoltura')
                ->options([
                    ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => IconRegistry::get('wrap.nowrap')],
                    ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => IconRegistry::get('wrap.wrap')],
                ])
                ->default('nowrap')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup('justifyContent', 'Justificación')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('justify.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('justify.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('justify.end')],
                    ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => IconRegistry::get('justify.between')],
                    ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => IconRegistry::get('justify.around')],
                ])
                ->default('flex-start')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup('alignItems', 'Alineación')
                ->options([
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => IconRegistry::get('align.stretch')],
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('align.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('align.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('align.end')],
                ])
                ->default('stretch')
                ->condition([$conditionField, '==', 'flex']),

            Option::slider('gap', 'Separación (Gap)')
                ->unit('px')
                ->min(0)
                ->max(120)
                ->step(2)
                ->default(0)
                ->condition([$conditionField, '==', 'flex']),
        ];
    }

    protected function getGridLayoutOptions(string $conditionField = 'layout'): array
    {
        return [
            Option::slider('gridColumns', 'Columnas')
                ->min(1)
                ->max(12)
                ->step(1)
                ->default(3)
                ->condition([$conditionField, '==', 'grid']),

            Option::slider('gridGap', 'Separación Grid')
                ->unit('px')
                ->min(0)
                ->max(120)
                ->step(2)
                ->default(20)
                ->condition([$conditionField, '==', 'grid']),
        ];
    }
}
```

### Fase 4: Crear Traits Adicionales de Estandarización

#### Trait `HasDimensions`

Unificar el control de ancho y alto que actualmente está disperso.

```php
trait HasDimensions
{
    protected function getDimensionsOptions(): array
    {
        return [
            Option::select('width', 'Ancho')
                ->options(['auto' => 'Auto', '100%' => '100%', 'custom' => 'Personalizado'])
                ->default('auto')
                ->tab('Estilo'),
                
            Option::select('height', 'Altura') // Smart control en JS
                ->options(['auto' => 'Auto', 'fit' => 'Ajustar', 'custom' => 'Personalizado'])
                ->default('auto')
                ->tab('Estilo'),
        ];
    }
}
```

---

## 📋 Plan de Implementación por Fases

### Fase 1: Crear IconRegistry PHP ⏱️ 2-3 horas
```
[x] 1.1 Crear directorio Glory/src/Gbn/Icons/
[x] 1.2 Implementar IconRegistry.php
[x] 1.3 Implementar LayoutIcons.php con todos los iconos de layout
[x] 1.4 Implementar otros archivos de iconos (Background, Positioning, etc.)
[ ] 1.5 Escribir tests unitarios
```

### Fase 2: Crear IconRegistry JS ⏱️ 2-3 horas
```
[x] 2.1 Crear directorio assets/js/ui/icons/
[x] 2.2 Implementar index.js con registro principal
[x] 2.3 Implementar archivos de iconos por categoría
[x] 2.4 Actualizar ScriptManifest para incluir el nuevo módulo
```

### Fase 3: Refactorizar HasFlexbox y HasGrid ⏱️ 2-3 horas
```
[x] 3.1 Crear HasLayoutOptions.php
[x] 3.2 Actualizar HasFlexbox para usar IconRegistry
[x] 3.3 Actualizar HasGrid para usar IconRegistry (Validado: usa sliders, no iconos)
[x] 3.4 Crear tests de compatibilidad (glory/src/Gbn/Diagnostics/test_layout.php)
```

### Fase 4: Migración de PostRender y Componentes ⏱️ 4-5 horas
```
[x] 4.1 Refactorizar PostRenderComponent para usar HasLayoutOptions
[x] 4.2 Crear alias de campos si es necesario (displayMode -> layout)
[x] 4.3 Actualizar `ButtonComponent` para usar `HasTypography` y `HasDimensions`
[x] 4.4 Refactorizar `HasTypography` para que sea flexible (soportar subconjuntos de opciones)
[ ] 4.5 Probar todos los casos de uso
```

### Fase 5: Migrar Traits Restantes ⏱️ 4-5 horas
```
[x] 5.1 Actualizar HasBackground.php
[x] 5.2 Actualizar HasPositioning.php
[x] 5.3 Actualizar HasBorder.php
[x] 5.4 Actualizar HasTypography.php (si aplica)
```

### Fase 6: Migrar Archivos JS ⏱️ 3-4 horas
```
[x] 6.1 Actualizar panel-render.js:
      - Importar IconRegistry
      - Reemplazar mapa `icons` hardcodeado por llamadas a `IconRegistry.get('tab.*')`
      - Implementar fallback para tabs desconocidos
      - [x] Corrección: Refactorizar módulos JS de iconos a IIFE y encolarlos en GbnManager.php
[x] 6.2 Actualizar theme/render.js - REFACTORIZACIÓN MODULAR COMPLETADA (Dic 2025):
      - Archivo original: 702 líneas → render.js orquestador: ~225 líneas
      - Nuevos módulos creados:
        - state.js: Gestión de estado global del panel (~110 líneas)
        - utils.js: Utilidades compartidas, tabs con GbnIcons (~200 líneas)
        - renderers/page-settings.js: Form de página (~80 líneas)
        - renderers/menu.js: Menú principal (~70 líneas)
        - renderers/section-text.js: Sección tipografía (~60 líneas)
        - renderers/section-colors.js: Sección colores (~180 líneas)
        - renderers/section-pages.js: Sección páginas (~50 líneas)
        - renderers/section-components.js: Sección componentes (~200 líneas)
      - GbnManager.php actualizado con dependencias de los nuevos scripts
      - Los iconos ahora usan GbnIcons.get('theme.*') y GbnIcons.get('tab.*')
[x] 6.3 Refactorizar panel-render.js - REFACTORIZACIÓN MODULAR COMPLETADA (Dic 2025):
      - Archivo original: 796 líneas → panel-render.js orquestador: ~220 líneas
      - Nuevos módulos en panel-render/:
        - state.js: Estado del panel (currentEditingState, lastBlockId, lastActiveTab) (~105 líneas)
        - style-resolvers.js: Mapa de resolvers por rol de componente (~180 líneas)
        - state-selector.js: UI selector Normal/Hover/Focus (~160 líneas)
        - tabs.js: Utilidades de tabs con GbnIcons (~190 líneas)
        - config-updater.js: Lógica compleja de updateConfigValue (~280 líneas)
        - theme-propagation.js: Propagación de cambios del tema (~100 líneas)
      - GbnManager.php actualizado con dependencias de los nuevos scripts
      - Iconos migrados a GbnIcons.get('state.*') y GbnIcons.get('tab.*')
[x] 6.4 Actualizar panel-fields/*.js - COMPLETADO:
      - typography.js: Migrado a GbnIcons.get() (size, lineHeight, spacing, transform)
      - dimensions.js: Migrado a GbnIcons.get() (width, height, maxWidth, maxHeight)
      - utils.js: Migrado a GbnIcons.get() para iconos de spacing (superior, derecha, etc.)
      - Creados nuevos archivos de iconos: typography-icons.js, dimensions-icons.js, spacing-icons.js
      - Actualizado GbnManager.php con nuevas dependencias
[x] 6.5 Eliminar iconos inline obsoletos - COMPLETADO (reemplazados por llamadas al registro)
```

### Fase 7: Cleanup y Documentación ⏱️ 2 horas
```
[x] 7.1 Eliminar código duplicado
[x] 7.2 Actualizar documentación de componentes
[ ] 7.3 Crear guía de uso del IconRegistry
[ ] 7.4 Actualizar plan.md principal
```

### Fase 7.5: Refactorización de utils.js y GbnManager.php ⏱️ COMPLETADO (Dic 2025)

**utils.js (758 líneas → ~75 líneas + 8 módulos)**
```
[x] Crear deep-access.js       → getDeepValue, setDeepValue, deleteDeepValue, hasDeepValue
[x] Crear theme-defaults.js    → getThemeDefault, hasThemeDefault
[x] Crear css-map.js           → CONFIG_TO_CSS_MAP, UNITLESS_PROPERTIES, BROWSER_DEFAULTS, isBrowserDefault
[x] Crear computed-styles.js   → getComputedValue, getComputedValueForPath, getMultipleComputedValues
[x] Crear config-values.js     → getConfigValue, getResponsiveConfigValue, getValueSource
[x] Crear effective-value.js   → getEffectiveValue (lógica compleja de valor efectivo)
[x] Crear condition-handler.js → shouldShowField, shouldShowFieldMultiple
[x] Crear state-utils.js       → SUPPORTED_STATES, getStateConfig, hasStateStyles
[x] Crear helpers.js           → ICONS, parseSpacingValue, obtenerSchemaDelRole
[x] Refactorizar utils.js      → Orquestador que verifica módulos cargados
```

**GbnManager.php (855 líneas → ~296 líneas + ScriptManifest)**
```
[x] Crear Config/ScriptManifest.php con métodos por categoría:
    - getFrontendScripts()     → Scripts públicos (siempre cargados)
    - getIconScripts()         → Scripts del IconRegistry JS
    - getServiceScripts()      → Servicios del builder
    - getPanelFieldScripts()   → Módulos de panel-fields (refactorizados)
    - getRendererScripts()     → Renderers de componentes
    - getPanelRenderScripts()  → Módulos de panel-render
    - getThemeScripts()        → Módulos del theme settings
    - getUIScripts()           → Scripts de UI general
    - getBuilderScripts()      → Combina todos los del builder
[x] Actualizar GbnManager.php para usar ScriptManifest
[x] Eliminar ~560 líneas de definiciones inline
```

**Métricas:**
| Archivo            | Antes      | Después     | Reducción       |
| ------------------ | ---------- | ----------- | --------------- |
| `utils.js`         | 758 líneas | ~75 líneas  | -90%            |
| `GbnManager.php`   | 855 líneas | ~296 líneas | -65%            |
| Total módulos JS   | 1          | 9           | +8 módulos      |
| Total archivos PHP | 0 nuevo    | 1 nuevo     | +ScriptManifest |

### Fase 8: Estandarizar MenuComponent ⏱️ 2-3 horas
```
[x] 8.1 Migrar getSchema() de array literal a SchemaBuilder
[x] 8.2 Cambiar formato de opciones de 'value/label' a 'valor/etiqueta'
[x] 8.3 Normalizar tabs de minúsculas a Capitalizadas (configuracion → Configuración) para activar iconos automáticos
[x] 8.4 Agregar traits apropiados (HasTypography si existe o manual standard)
[x] 8.5 Cambiar operador de condición '===' a '==' para consistencia
[x] 8.6 Validar que el panel JS renderice correctamente
```

### Fase 9: Unificar Nombres de Campos ⏱️ 3-4 horas
```
[x] 9.1 Crear constantes de campos canónicos en SchemaConstants.php:
      - FIELD_LAYOUT = 'layout' (deprecar 'displayMode')
      - FIELD_JUSTIFY = 'justifyContent' (deprecar 'flexJustify')
      - FIELD_ALIGN = 'alignItems' (deprecar 'flexAlign')
[x] 9.2 Actualizar HasFlexbox para usar nombres canónicos
[x] 9.3 Crear FieldAliasMapper para compatibilidad hacia atrás
[x] 9.4 Actualizar StyleMapper para usar constantes (style-composer.js y layout-flex.js actualizados)
[x] 9.5 Migrar PostRenderComponent a nombres canónicos (actualizado componente y procesador)
[x] 9.6 Actualizar todos los templates existentes (buscar displayMode=) - PostRender actualizado, otros son dinámicos a través de AliasMapper
```

### Fase 10: Normalizar Tipos de Campos ⏱️ 2-3 horas
```
[ ] 10.1 Definir tipo canónico para 'gap': slider con unidad - HECHO (usando unit 'px')
[x] 10.2 Crear helper Option::gap() que estandarice el campo
[x] 10.3 Actualizar todos los usos de gap en componentes - COMPLETADO (HasLayoutOptions, HasFlexbox, MenuComponent, PostRenderComponent)
[ ] 10.4 Documentar convención de tipos para campos comunes
[ ] 10.5 Agregar validación en SchemaBuilder para tipos consistentes
```

### Fase 11: Auditar y Normalizar Condiciones ⏱️ 1-2 horas
```
[ ] 11.1 Documentar formato canónico de condiciones: [field, operator, value]
[x] 11.2 Actualizar Option::condition() para normalizar formatos y validar operadores - COMPLETADO
[x] 11.3 Migrar MenuComponent: cambiar `===` por `==` - COMPLETADO
[x] 11.4 Migrar condiciones de formato corto (2 params) a formato estándar - COMPLETADO
[ ] 11.5 Validar que condition-handler.js soporte todos los operadores - VALIDADO (Visto en el código)
[ ] 11.6 Agregar tests para todos los formatos de condición
```

### Fase 12: Modernización de MenuComponent ⏱️ 3 horas
```
[x] 12.1 Convertir array de opciones a `SchemaBuilder` - COMPLETADO
[x] 12.2 Implementar `HasTypography` en lugar de campos manuales - COMPLETADO
[x] 12.3 Estandarizar nombres de campos (flexDirection -> direction, flexWrap -> wrap) en SchemaConstants - COMPLETADO
[ ] 12.4 Validar compatibilidad con `MenuWalker`
```

### Fase 13: Enriquecimiento Visual (Form y Menú) ⏱️ 2-3 horas
```
[x] 13.1 FormComponent:
      - Agregar iconos a opciones de 'method' (GET/POST) en IconRegistry
      - Agregar iconos a opciones de 'ajaxSubmit' y 'honeypot' si se convierten a iconGroup
[x] 13.2 MenuComponent:
      - Agregar iconos a opciones de 'fontWeight' y 'textTransform' en IconRegistry
      - Convertir selects simples a iconGroups donde aporte valor
[ ] 13.3 Actualizar SchemaBuilder para permitir definir `tabIcon` desde PHP (opcional)
```

---

## ✅ Checklist de Verificación para el Usuario

### Fase 1-3: Centralización de Iconos y Refactor de Flexbox
- [x] **Iconos PHP (Backend)**: Verificar que los componentes que usan `HasFlexbox` (como Layout, Flexbox options) muestran los iconos correctamente en el panel.
- [ ] **Visualización**: Confirmar que visualmente los iconos son idénticos o mejores que antes (ahora vienen centralizados de `Glory/src/Gbn/Icons`).
- [x] **Tests Diagnóstico**: Ejecutar `php Glory/src/Gbn/Diagnostics/test_icons.php` y `php Glory/src/Gbn/Diagnostics/test_layout.php` para confirmar integridad del sistema (opcional).
- [x] **Archivos JS**: Verificar que existen `assets/js/ui/icons/index.js` y sus compañeros. (Nota: Aún no están conectados al runtime JS principal, eso es Fase 6).
- [x] **Funcionalidad (HasFlexbox)**: Al editar un bloque con opciones de layout, asegurar que los cambios se aplican correctamente (la lógica subyacente de `HasFlexbox` no cambió, solo la definición de iconos).

> **Nota importante:** Con los avances realizados, la estructura interna de `HasFlexbox` ha cambiado para usar `IconRegistry`. Si detecta iconos rotos (SVG no renderizado), verificar la caché y los logs de error PHP.

[x] 12.3 Estandarizar nombres de campos (layout horizontal/vertical -> direction)
[ ] 12.4 Validar compatibilidad con `MenuWalker`
```

---

## ⚠️ Consideraciones de Migración

### Compatibilidad Hacia Atrás

1. **Mantener nombres de campos existentes** mientras se crea el mapping:
   - `displayMode` en PostRender ↔ `layout` en traits
   
2. **Crear constantes de alias** para campos deprecados:
   ```php
   // En Renderer o Mapper
   const FIELD_ALIASES = [
       'displayMode' => 'layout',
       'flexDirection' => 'direction',
   ];
   ```

3. **No romper templates existentes** que ya tienen `opciones="displayMode: 'grid'"`

### Orden de Migración Recomendado

1. **Primero**: IconRegistry (no rompe nada, es aditivo)
2. **Segundo**: Traits existentes (refactor interno)
3. **Tercero**: PostRenderComponent (más complejo, requiere testing)
4. **Cuarto**: Archivos JS (depende de PHP completado)

---

## 📊 Métricas de Éxito

### Antes del Refactor
- ~150 definiciones de SVG inline en PHP
- ~80 definiciones de SVG inline en JS
- 0 iconos centralizados

### Después del Refactor
- 0 definiciones de SVG inline en componentes PHP
- 0 definiciones de SVG inline en archivos JS de renderers
- ~50-60 iconos únicos en IconRegistry
- 100% consistencia visual de iconos

---

## 🔗 Archivos Relacionados

- `plan.md` - Plan principal del proyecto
- `guia-crear-componente.md` - Guía de componentes
- `Traits/*.php` - Traits a refactorizar
- `Components/*/Component.php` - Componentes afectados

---

## 📝 Notas Adicionales

### Por qué no usar librería externa de iconos (Heroicons, Feather, etc.)

1. **Control total**: Podemos ajustar cada icono a nuestras necesidades
2. **Sin dependencias**: No agregamos peso adicional
3. **Consistencia**: Todos los iconos siguen el mismo estilo
4. **Personalización**: Fácil ajustar tamaños y estilos

### Decisión: Iconos como strings vs archivos SVG

**Elegido: Strings en constantes**
- Pros: No requiere file reading, más rápido
- Cons: Más difícil de editar visualmente

Alternativa futura: Migrar a archivos `.svg` si los iconos crecen mucho.

# Nota del usuario

dejo esto aqui como una lista de archivs a refactoriza

801	post-render.js
762	PostRenderProcessor.php
675	inspector.js
627	panel-core.js
575	renderer-traits.js
542	app.js
471	PostFieldComponent.php
434	form-submit.js
401	state-styles.js
377	FormSubmitHandler.php
362	applicator.js
361	context-menu.js
355	typography.js