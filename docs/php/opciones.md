# Opciones

El sistema de opciones permite al usuario configurar el tema desde el panel de WordPress.

## Arquitectura

```
OpcionRegistry → OpcionRepository → OpcionManager → Panel Admin
```

| Clase | Responsabilidad |
|-------|----------------|
| `OpcionRegistry` | Define opciones disponibles (tipos, defaults, labels) |
| `OpcionRepository` | Lee/escribe valores en `wp_options` |
| `OpcionManager` | API publica para gestionar opciones |

## Registrar opciones

```php
// App/Config/opcionesTema.php

// Las opciones se definen declarativamente
// y se muestran automaticamente en el panel de admin
```

## Acceder desde React

```tsx
import { useGloryOptions } from '@/hooks';

function MiIsla(): JSX.Element {
    const { get, has } = useGloryOptions();

    const color = get('colorPrimario', '#3b82f6');

    return <div style={{ color }}>Hola</div>;
}
```

Las opciones llegan via `window.GLORY_CONTEXT.options`.

## Panel de admin

`OpcionPanelController`, `PanelRenderer` y `PanelDataProvider` renderizan automaticamente el panel de opciones en WordPress Admin basandose en las opciones registradas.

## Activar

```php
GloryFeatures::enable('opcionManagerSync');
```
