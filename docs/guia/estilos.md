# Estilos

Glory soporta CSS puro y Tailwind CSS (opt-in). No hay CSS inline ni CSS-in-JS.

## CSS puro

Cada isla tiene su propio archivo `.css` en `App/React/styles/`:

```css
/* App/React/styles/contacto.css */

.contenedorContacto {
    padding: var(--espaciado-medio, 2rem);
    max-width: var(--ancho-contenedor, 1200px);
    margin: 0 auto;
}

.tituloContacto {
    font-size: var(--fuente-titulo, 2rem);
    color: var(--color-primario, #1a1a1a);
}
```

### Variables CSS

Usa variables CSS para valores reutilizables. Centraliza en un archivo de variables:

```css
/* App/React/styles/variables.css */

:root {
    --color-primario: #3b82f6;
    --color-secundario: #64748b;
    --espaciado-chico: 0.5rem;
    --espaciado-medio: 1rem;
    --espaciado-grande: 2rem;
    --fuente-base: 1rem;
    --fuente-titulo: 2rem;
    --ancho-contenedor: 1200px;
}
```

### Importar en la isla

```tsx
import '../styles/contacto.css';

export function ContactoIsland(): JSX.Element {
    return (
        <div className="contenedorContacto">
            <h1 className="tituloContacto">Contacto</h1>
        </div>
    );
}
```

## Tailwind CSS

Activar con feature flag:

```php
// App/Config/control.php
GloryFeatures::enable('tailwind');
```

O via CLI:

```bash
node Glory/cli/glory.mjs setup --tailwind
```

Usa clases de Tailwind directamente:

```tsx
export function ContactoIsland(): JSX.Element {
    return (
        <div className="max-w-4xl mx-auto p-8">
            <h1 className="text-3xl font-bold text-gray-900">Contacto</h1>
        </div>
    );
}
```

## Convenciones

| Regla | Ejemplo |
|-------|---------|
| Nombres en **español** | `.contenedorPrincipal`, `.botonActivo` |
| Formato **camelCase** | `.tarjetaProducto`, no `.tarjeta-producto` |
| **Prohibido** CSS inline | Nada de `style={{ ... }}` |
| Usar **variables** para colores/spacing | `var(--color-primario)` |
| Buscar clases existentes antes de crear | Reutilizar > duplicar |

::: warning Sin CSS inline
El unico CSS inline permitido es en componentes del **framework** (error boundaries, dev overlay). En tu codigo todo va en archivos `.css`.
:::
