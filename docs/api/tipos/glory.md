# Tipos Glory

Tipos del framework para el puente PHP → React.

## GloryContentMap

Contenido inyectado en `window.__GLORY_CONTENT__`. Cada clave es un identificador registrado en PHP.

```typescript
type GloryContentMap = Record<string, WPPost[]>;
```

Ejemplo: `{ blog: [...posts], portfolio: [...projects] }`

## GloryContext

Contexto global inyectado en `window.GLORY_CONTEXT`. Extendible via filtro PHP.

```typescript
interface GloryContext {
    siteUrl?: string;
    themeUrl?: string;
    restUrl?: string;
    nonce?: string;
    isAdmin?: boolean;
    userId?: number;
    locale?: string;
    options?: Record<string, unknown>;
    [key: string]: unknown;  // Extensible
}
```

## GloryIslandBaseProps

Props base que toda isla puede recibir.

```typescript
interface GloryIslandBaseProps {
    [key: string]: unknown;
}
```

## GloryPageConfig

Configuracion de una pagina React registrada en PHP.

```typescript
interface GloryPageConfig {
    slug: string;
    islandName: string;
    title: string;
    parentSlug?: string;
    roles?: string[];
    props?: Record<string, unknown>;
}
```

## GloryOption

Estructura de una opcion del sistema de opciones.

```typescript
interface GloryOption<T = unknown> {
    key: string;
    value: T;
    default: T;
    label?: string;
    type?: 'text' | 'number' | 'boolean' | 'select' | 'color' | 'image';
    group?: string;
}
```

## IslandRegistry (tipo)

Mapa de nombre a componente.

```typescript
type IslandRegistry = Record<string, React.ComponentType<Record<string, unknown>>>;
```

## Window globals

Declaracion de las variables globales inyectadas por PHP:

```typescript
declare global {
    interface Window {
        __GLORY_CONTENT__?: GloryContentMap;
        GLORY_CONTEXT?: GloryContext;
    }
}
```
