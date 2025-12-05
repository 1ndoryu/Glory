# Guía: Crear un Nuevo Componente GBN

> [!IMPORTANT]
> Proceso paso a paso para crear componentes usando la arquitectura SOLID de GBN.

---

## Resumen Ejecutivo

| Paso | Archivo                             | Obligatorio                  |
| :--- | :---------------------------------- | :--------------------------- |
| 1    | `components/MyComp/MyComponent.php` | ✅ Clase con Schema           |
| 2    | `assets/js/ui/renderers/my-comp.js` | ✅ Renderer JS                |
| 3    | `GbnManager.php`                    | ✅ Registro del script        |
| 4    | `panel-render.js`                   | ✅ Resolver de estilos        |
| 5    | `services/content/roles.js`         | ⚠️ Solo si necesitas fallback |

---

## Paso 1: Clase PHP del Componente

### Template Base

```php
<?php

namespace Glory\Gbn\Components\MiComponente;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasTypography;
use Glory\Gbn\Traits\HasBorder;

/**
 * Mi Componente Personalizado
 * 
 * @role miComponente
 * @selector [gloryMiComponente]
 */
class MiComponenteComponent extends AbstractComponent
{
    use HasSpacing;
    use HasTypography;
    use HasBorder;

    public function getRole(): string
    {
        return 'miComponente';
    }

    public function getLabel(): string
    {
        return 'Mi Componente';
    }

    public function getIcon(): string
    {
        return '🎨'; // Emoji o SVG
    }

    public function getSelector(): string
    {
        return '[gloryMiComponente]';
    }

    public function buildSchema(SchemaBuilder $builder): void
    {
        // Tab 1: Contenido
        $builder->addOption(
            Option::text('titulo')
                ->label('Título')
                ->default('')
                ->tab('contenido')
        );

        // Tab 2: Estilo
        $builder->addOption(
            Option::color('color')
                ->label('Color de Texto')
                ->tab('estilo')
        );

        // Agregar opciones de traits
        $this->addSpacingOptions($builder, 'estilo');
        $this->addBorderOptions($builder, 'estilo');

        // Tab 3: Avanzado
        $builder->addOption(
            Option::textarea('custom_css')
                ->label('CSS Personalizado')
                ->tab('avanzado')
        );
    }
}
```

### Traits Disponibles

| Trait            | Método para agregar                     | Opciones que agrega                                 |
| :--------------- | :-------------------------------------- | :-------------------------------------------------- |
| `HasSpacing`     | `addSpacingOptions($builder, $tab)`     | padding, margin                                     |
| `HasTypography`  | `addTypographyOptions($builder, $tab)`  | font, size, weight, lineHeight                      |
| `HasBorder`      | `addBorderOptions($builder, $tab)`      | borderWidth, borderStyle, borderColor, borderRadius |
| `HasBackground`  | `addBackgroundOptions($builder, $tab)`  | backgroundImage, backgroundColor, size, position    |
| `HasFlexbox`     | `addFlexboxOptions($builder, $tab)`     | layout, direction, wrap, justify, align, gap        |
| `HasGrid`        | `addGridOptions($builder, $tab)`        | gridColumns, gridGap                                |
| `HasPositioning` | `addPositioningOptions($builder, $tab)` | position, zIndex, overflow                          |
| `HasCustomCSS`   | `addCustomCssOption($builder, $tab)`    | custom_css                                          |

---

## Paso 2: Renderer JS

### Template con Traits (Recomendado)

```javascript
;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para el componente.
     */
    function getStyles(config, block) {
        // Estilos comunes (typography, spacing, border, background)
        var styles = traits.getCommonStyles(config);
        
        // Propiedades específicas
        if (config.miPropiedad) {
            styles['mi-propiedad-css'] = config.miPropiedad;
        }
        
        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        
        // Propiedades específicas (contenido, atributos)
        if (path === 'titulo') {
            el.textContent = value || '';
            return true;
        }
        
        if (path === 'miPropiedad') {
            el.style.setProperty('--mi-var', value);
            return true;
        }
        
        // Delegar a traits (typography, spacing, border, etc.)
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.miComponente = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
```

### Factory para Componentes Simples

```javascript
// Si no necesitas lógica especial:
Gbn.ui.renderers.miComponente = Gbn.ui.renderers.traits.createRenderer({
    getExtraStyles: function(config) {
        return {
            'mi-propiedad': config.miValor
        };
    },
    handleSpecialUpdate: function(block, path, value) {
        if (path === 'titulo') {
            block.element.textContent = value;
            return true;
        }
        return false;
    }
});
```

---

## Paso 3: Registro en GbnManager.php

### Agregar al array `$builderScripts`

```php
'glory-gbn-ui-renderers-mi-componente' => [
    'file' => '/js/ui/renderers/mi-componente.js',
    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
],
```

### Agregar como dependencia de `panel-render`

```php
'glory-gbn-ui-panel-render' => [
    'deps' => [
        // ... otros renderers ...
        'glory-gbn-ui-renderers-mi-componente',
    ],
],
```

---

## Paso 4: Resolver en panel-render.js

Agregar al objeto `styleResolvers`:

```javascript
var styleResolvers = {
    // ... otros resolvers ...
    miComponente: function(config, block) {
        return Gbn.ui.renderers.miComponente 
            ? Gbn.ui.renderers.miComponente.getStyles(config, block) 
            : {};
    }
};
```

---

## Paso 5: (Opcional) Fallback Selector

Si tu componente usa un atributo HTML no estándar:

```javascript
// En roles.js
var FALLBACK_SELECTORS = {
    miComponente: { 
        attribute: 'gloryMiComponente', 
        dataAttribute: 'data-gbn-mi-componente' 
    }
};
```

---

## Uso en HTML

```html
<!-- Uso básico -->
<div gloryMiComponente>Contenido</div>

<!-- Con opciones inline -->
<div gloryMiComponente opciones="titulo: 'Mi Título', color: '#ff0000'">
    Contenido
</div>

<!-- Con clases CSS -->
<div gloryMiComponente class="mi-clase-custom">
    Contenido
</div>
```

---

## Checklist de Verificación

- [ ] Clase PHP con `getRole()`, `getLabel()`, `getSelector()`, `buildSchema()`
- [ ] Traits usados para opciones comunes
- [ ] Renderer JS con `getStyles()` y `handleUpdate()`
- [ ] Renderer usa `traits.getCommonStyles()` y `traits.handleCommonUpdate()`
- [ ] Script registrado en `GbnManager.php` con dependencias
- [ ] Agregado como dependencia de `panel-render`
- [ ] Resolver agregado en `panel-render.js`
- [ ] **PRUEBAS:**
  - [ ] Panel muestra campos correctamente
  - [ ] Cambios se aplican en tiempo real
  - [ ] Cambios persisten al guardar
  - [ ] Funciona en frontend sin GBN

---

## Errores Comunes y Soluciones

| Error                   | Causa                              | Solución                                 |
| :---------------------- | :--------------------------------- | :--------------------------------------- |
| Panel no muestra campos | Componente no registrado           | Verificar `ComponentLoader::load()`      |
| Estilos no se aplican   | Renderer no registrado             | Verificar deps en `GbnManager.php`       |
| `traits is undefined`   | Script carga antes que traits      | Agregar `renderer-traits` a dependencias |
| Valores no persisten    | Falta mapeo en `CONFIG_TO_CSS_MAP` | Agregar mapeo en `utils.js`              |

---

## Patrones Avanzados

### Componente con Contenido Dinámico

```php
// En buildSchema()
$builder->addOption(
    Option::select('tipo')
        ->label('Tipo de Contenido')
        ->choices([
            'texto' => 'Texto Plano',
            'html' => 'HTML Rico',
            'imagen' => 'Imagen'
        ])
        ->tab('contenido')
);

$builder->addOption(
    Option::rich_text('contenido')
        ->label('Contenido')
        ->condition('tipo', '===', 'html')  // Solo visible si tipo = html
        ->tab('contenido')
);
```

### Componente con Estados Hover/Focus

```javascript
// En handleUpdate()
if (path.startsWith('_states.hover.')) {
    var prop = path.split('.').pop();
    Gbn.services.stateStyles.setStateProperty(block, 'hover', prop, value);
    return true;
}
```

### Componente con Custom CSS

```php
// Usar trait
use Glory\Gbn\Traits\HasCustomCSS;

// En buildSchema()
$this->addCustomCssOption($builder, 'avanzado');
```

---

**Versión:** 2.0 (Optimizada)  
**Relacionado:** `reglas.md`, `documentación-gbn.md`, `plan.md`
