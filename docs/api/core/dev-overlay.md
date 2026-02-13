# DevOverlay

Overlay de debug visible solo en desarrollo. Muestra informacion de cada isla en tiempo real.

## Que muestra

Un badge posicionado en la esquina superior derecha de cada isla:

- **Nombre** de la isla
- **Conteo de renders** (`#1`, `#2`, `#3`...)
- **Tooltip** con las props disponibles (al hover)

## Visual

```
┌──────────────────────────────────────────┐
│                              MiIsla #3   │ ← badge
│                                          │
│  [contenido de la isla]                  │
│                                          │
└──────────────────────────────────────────┘
```

Estilo: fondo oscuro, texto cyan (`#22d3ee`), monospace, `z-index: 99999`, `pointer-events: none`.

## Activacion

Se activa automaticamente cuando `import.meta.env.DEV` es `true` (modo desarrollo de Vite). En produccion no se renderiza.

## Props

```typescript
interface DevOverlayProps {
    islandName: string;
    props: Record<string, unknown>;
    children: ReactNode;
}
```

## Uso

No necesitas usarlo directamente. El motor de hidratacion lo envuelve automaticamente en desarrollo:

```tsx
// hydration.tsx (automatico)
if (import.meta.env.DEV) {
    islandContent = (
        <DevOverlay islandName={islandName} props={props}>
            {islandContent}
        </DevOverlay>
    );
}
```
