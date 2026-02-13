# useGloryOptions

Acceso tipado a las opciones del tema Glory.

## Firma

```typescript
function useGloryOptions<
    T extends Record<string, unknown> = Record<string, unknown>
>(): UseGloryOptionsResult<T>
```

## Retorno

```typescript
interface UseGloryOptionsResult<T> {
    options: T;                                    // Objeto completo de opciones
    get: <V = unknown>(key: string, defaultValue?: V) => V;  // Acceder por clave
    has: (key: string) => boolean;                 // Verificar existencia
}
```

## Uso basico

```tsx
import { useGloryOptions } from '@/hooks';

function MiIsla(): JSX.Element {
    const { get, has } = useGloryOptions();

    const colorPrimario = get('colorPrimario', '#3b82f6');
    const mostrarBanner = get<boolean>('mostrarBanner', false);

    return (
        <div>
            <p style={{ color: colorPrimario }}>Hola</p>
            {mostrarBanner && <Banner />}
            {has('apiKey') && <IntegracionExterna />}
        </div>
    );
}
```

## Con tipo personalizado

```tsx
interface MisOpciones {
    colorPrimario: string;
    mostrarBanner: boolean;
    maxProductos: number;
}

const { options } = useGloryOptions<MisOpciones>();
// options.colorPrimario ← tipado como string
```

## Fuente de datos

Lee de `GLORY_CONTEXT.options`, inyectado por PHP:

1. Del `GloryProvider` si esta disponible
2. Fallback a `window.GLORY_CONTEXT.options`

## Registrar opciones en PHP

```php
// Las opciones se inyectan via el contexto React
add_filter('glory_react_context', function($context) {
    $context['options'] = [
        'colorPrimario' => get_option('glory_color_primario', '#3b82f6'),
        'mostrarBanner' => get_option('glory_mostrar_banner', false),
    ];
    return $context;
});
```
