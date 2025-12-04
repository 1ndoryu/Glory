# Guía: Cómo Crear un Nuevo Componente GBN

> [!IMPORTANT]
> Esta guía documenta el proceso paso a paso para crear un nuevo componente en el sistema GBN.
> Fase 11: Refactorización SOLID - Los nuevos componentes deben usar los traits para reducir código.

## Resumen del Proceso

Crear un nuevo componente requiere modificar/crear los siguientes archivos:

| Archivo                             | Descripción                              | Obligatorio |
| ----------------------------------- | ---------------------------------------- | ----------- |
| `components/MyComp/MyComponent.php` | Clase del componente (Schema)            | ✅           |
| `assets/js/ui/renderers/my-comp.js` | Renderer JS (estilos en tiempo real)     | ✅           |
| `GbnManager.php`                    | Registro del script                      | ✅           |
| `services/content/roles.js`         | Fallback selector (solo si es necesario) | ⚠️           |

---

## Paso 1: Crear la Clase PHP del Componente

### Ubicación
```
Glory/src/Gbn/components/MiComponente/MiComponenteComponent.php
```

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
    // Usar traits para opciones reutilizables
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

| Trait            | Opciones que agrega                                       |
| ---------------- | --------------------------------------------------------- |
| `HasSpacing`     | padding (superior/derecha/inferior/izquierda), margin     |
| `HasTypography`  | font, size, weight, lineHeight, letterSpacing, transform  |
| `HasBorder`      | borderWidth, borderStyle, borderColor, borderRadius       |
| `HasBackground`  | backgroundImage, backgroundSize, backgroundPosition, etc. |
| `HasFlexbox`     | layout, direction, wrap, justify, align, gap              |
| `HasGrid`        | gridColumns, gridGap                                      |
| `HasPositioning` | position, zIndex, overflow                                |
| `HasCustomCSS`   | custom_css                                                |

---

## Paso 2: Crear el Renderer JS

### Ubicación
```
Glory/src/Gbn/assets/js/ui/renderers/mi-componente.js
```

### Template Base (Usando Traits)
```javascript
;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Usar traits centralizados (Fase 11)
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para el componente.
     */
    function getStyles(config, block) {
        // Obtener estilos comunes (typography, spacing, border, background)
        var styles = traits.getCommonStyles(config);
        
        // Agregar propiedades específicas de tu componente
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
        
        // Propiedades específicas del componente (contenido, atributos)
        if (path === 'titulo') {
            el.textContent = value || '';
            return true;
        }
        
        if (path === 'miPropiedad') {
            el.style.setProperty('--mi-var', value);
            return true;
        }
        
        // Delegar todo lo demás a traits (typography, spacing, border, etc.)
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar renderer
    Gbn.ui.renderers.miComponente = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
```

### Alternativa: Usar Factory para Componentes Simples
```javascript
// Si tu componente no necesita lógica especial, usa createRenderer:
Gbn.ui.renderers.miComponente = Gbn.ui.renderers.traits.createRenderer({
    getExtraStyles: function(config) {
        // Solo devuelve estilos adicionales específicos
        return {
            'mi-propiedad': config.miValor
        };
    },
    handleSpecialUpdate: function(block, path, value) {
        // Solo maneja paths específicos
        if (path === 'titulo') {
            block.element.textContent = value;
            return true;
        }
        return false;
    }
});
```

---

## Paso 3: Registrar el Script en GbnManager.php

### Agregar al array `$builderScripts`:
```php
'glory-gbn-ui-renderers-mi-componente' => [
    'file' => '/js/ui/renderers/mi-componente.js',
    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
],
```

### Agregar como dependencia de `panel-render`:
```php
'glory-gbn-ui-panel-render' => [
    'deps' => [
        // ... otros renderers ...
        'glory-gbn-ui-renderers-mi-componente',
    ],
],
```

---

## Paso 4: Agregar Resolver de Estilos (panel-render.js)

En `Glory/src/Gbn/assets/js/ui/panel-render.js`, agregar al objeto `styleResolvers`:

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

## Paso 5: (Opcional) Agregar Fallback Selector

Si tu componente usa un atributo HTML específico que no sigue el patrón estándar, agrégalo a `roles.js`:

```javascript
var FALLBACK_SELECTORS = {
    // ...
    miComponente: { 
        attribute: 'gloryMiComponente', 
        dataAttribute: 'data-gbn-mi-componente' 
    }
};
```

---

## Uso en HTML

Una vez creado, puedes usar tu componente así:

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

- [ ] Clase PHP creada con `getRole()`, `getLabel()`, `getSelector()`, `buildSchema()`
- [ ] Traits usados para opciones comunes (HasSpacing, HasBorder, etc.)
- [ ] Renderer JS creado con `getStyles()` y `handleUpdate()`
- [ ] Renderer usa `traits.getCommonStyles()` y `traits.handleCommonUpdate()`
- [ ] Script registrado en `GbnManager.php` con dependencias correctas
- [ ] Agregado como dependencia de `panel-render`
- [ ] Resolver agregado en `panel-render.js`
- [ ] Probado: el panel muestra los campos correctamente
- [ ] Probado: los cambios se aplican en tiempo real
- [ ] Probado: los cambios persisten al guardar

---

## Errores Comunes

### ❌ El panel no muestra campos
**Causa:** El componente no está registrado correctamente.
**Solución:** Verificar que `ComponentLoader::load()` escanea tu directorio.

### ❌ Los estilos no se aplican en tiempo real
**Causa:** El renderer no está registrado o `handleUpdate` no maneja el path.
**Solución:** 
1. Verificar dependencias en `GbnManager.php`
2. Verificar que el path está en `traits.handleCommonUpdate` o manejado manualmente

### ❌ Error "Gbn.ui.renderers.traits is undefined"
**Causa:** El script del renderer carga antes que `renderer-traits.js`.
**Solución:** Agregar `'glory-gbn-ui-renderers-traits'` a las dependencias.

### ❌ Los valores no persisten
**Causa:** El mapper `CONFIG_TO_CSS_MAP` no tiene la propiedad.
**Solución:** Agregar el mapeo en `utils.js` si es una propiedad nueva.

---

*Documento creado como parte de la Fase 11: Refactorización SOLID*
*Última actualización: Diciembre 2025*
