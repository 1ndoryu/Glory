# useGloryContext

Acceso al contexto global de WordPress: URLs, nonce, locale, estado de admin.

## Firma

```typescript
function useGloryContext(): GloryContext
```

## Retorno

```typescript
interface GloryContext {
    siteUrl?: string;     // URL del sitio WordPress
    themeUrl?: string;    // URL del tema activo
    restUrl?: string;     // Base URL de la REST API
    nonce?: string;       // Nonce para autenticacion
    isAdmin?: boolean;    // Si el usuario actual es admin
    userId?: number;      // ID del usuario actual
    locale?: string;      // Locale del sitio (ej: 'es')
    options?: Record<string, unknown>; // Opciones del tema
}
```

## Uso

```tsx
import { useGloryContext } from '@/hooks';

function MiIsla(): JSX.Element {
    const { siteUrl, nonce, isAdmin, locale } = useGloryContext();

    return (
        <div>
            <p>Sitio: {siteUrl}</p>
            <p>Locale: {locale}</p>
            {isAdmin && <p>Eres admin</p>}
        </div>
    );
}
```

## Fuente de datos

1. Lee del `GloryProvider` si esta disponible
2. Fallback a `window.GLORY_CONTEXT`
3. Valores por defecto si ninguno existe:

```typescript
{
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
}
```

## Extender el contexto

En PHP, usa el filtro `glory_react_context`:

```php
add_filter('glory_react_context', function($context) {
    $context['miDato'] = 'valor';
    return $context;
});
```

En TypeScript, accede con index signature:

```tsx
const ctx = useGloryContext();
const miDato = ctx['miDato'] as string;
```
