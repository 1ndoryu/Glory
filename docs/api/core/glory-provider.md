# GloryProvider

Context global de React que centraliza los datos inyectados por PHP.

## Que hace

Envuelve automaticamente cada isla con un React Context que provee:

- **context** — `GloryContext` (siteUrl, nonce, locale, options...)
- **content** — `GloryContentMap` (contenido de WordPress)

## Uso automatico

No necesitas usar `GloryProvider` directamente. El motor de hidratacion lo envuelve automaticamente alrededor de cada isla.

## useGloryProvider()

Hook interno para acceder al valor del provider. Los hooks publicos lo usan internamente.

```typescript
import { useGloryProvider } from '@/core/GloryProvider';

const value = useGloryProvider();
// { context: GloryContext, content: GloryContentMap } | null
```

Retorna `null` si el componente no esta dentro de un `GloryProvider`.

## Valor del provider

```typescript
interface GloryProviderValue {
    context: GloryContext;
    content: GloryContentMap;
}
```

## Fuente de datos

Al montarse, lee:

1. `window.GLORY_CONTEXT` → merged con defaults
2. `window.__GLORY_CONTENT__` → contenido de WordPress

Los valores se calculan una sola vez con `useMemo([], [])`.

## Defaults

```typescript
const defaultContext: GloryContext = {
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
};
```

## Arbol de wrappers

```
GloryProvider
  └── context: { siteUrl, nonce, locale, ... }
  └── content: { blog: [...posts], portfolio: [...] }
      └── AppProvider? (tu provider personalizado)
            └── children (la isla)
```
