# ErrorBoundary

Error boundary individual por isla. Captura errores de renderizado sin que una isla rota tumbe las demas.

## Props

```typescript
interface ErrorBoundaryProps {
    islandName: string;    // Nombre de la isla (para logs)
    fallback?: ReactNode;  // Fallback personalizado
    children: ReactNode;
}
```

## Comportamiento

### En desarrollo (`import.meta.env.DEV`)

Muestra una caja roja con:
- Nombre de la isla que fallo
- Mensaje de error
- Boton "Reintentar" que resetea el estado del boundary

### En produccion

Muestra un texto limpio: **"Contenido no disponible"**

### Fallback personalizado

```tsx
<IslandErrorBoundary
    islandName="MiIsla"
    fallback={<p>Ha ocurrido un error en esta seccion</p>}
>
    <MiIsla />
</IslandErrorBoundary>
```

## Uso automatico

No necesitas envolver tus islas manualmente. El motor de hidratacion lo hace automaticamente:

```
hydration.tsx → mountIsland() → IslandErrorBoundary envuelve cada isla
```

## Logging

Cada error se reporta a consola con:

```
[Glory] Error en isla "MiIsla": Error message
    at Component (file.tsx:42)
    at ...
```

## Independencia

Si `HeaderIsland` falla, `ContenidoIsland` y `FooterIsland` siguen funcionando normalmente. Cada isla tiene su propio boundary aislado.
