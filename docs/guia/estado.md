# Estado con Zustand

Glory usa Zustand para estado global. Es simple, sin boilerplate, y funciona bien con React Islands.

## Crear un store

```typescript
// App/React/stores/useCarritoStore.ts

import { create } from 'zustand';

interface Producto {
    id: number;
    nombre: string;
    precio: number;
    cantidad: number;
}

interface CarritoState {
    items: Producto[];
    agregar: (producto: Producto) => void;
    eliminar: (id: number) => void;
    vaciar: () => void;
    total: () => number;
}

export const useCarritoStore = create<CarritoState>((set, get) => ({
    items: [],

    agregar: (producto) =>
        set((state) => {
            const existente = state.items.find((i) => i.id === producto.id);
            if (existente) {
                return {
                    items: state.items.map((i) =>
                        i.id === producto.id ? { ...i, cantidad: i.cantidad + 1 } : i,
                    ),
                };
            }
            return { items: [...state.items, { ...producto, cantidad: 1 }] };
        }),

    eliminar: (id) =>
        set((state) => ({
            items: state.items.filter((i) => i.id !== id),
        })),

    vaciar: () => set({ items: [] }),

    total: () => get().items.reduce((sum, i) => sum + i.precio * i.cantidad, 0),
}));
```

## Usar en una isla

```tsx
import { useCarritoStore } from '../stores/useCarritoStore';

export function CarritoIsland(): JSX.Element {
    const { items, eliminar, total } = useCarritoStore();

    return (
        <div id="seccionCarrito">
            <h2>Carrito ({items.length})</h2>
            {items.map((item) => (
                <div key={item.id}>
                    <span>{item.nombre} x{item.cantidad}</span>
                    <button onClick={() => eliminar(item.id)}>Quitar</button>
                </div>
            ))}
            <p>Total: ${total()}</p>
        </div>
    );
}
```

## Estado compartido entre islas

Zustand es global, asi que dos islas pueden compartir el mismo store:

```tsx
// HeaderIsland.tsx
const cantidadItems = useCarritoStore((state) => state.items.length);

// CarritoIsland.tsx
const { items, total } = useCarritoStore();
```

Ambas islas se actualizan cuando cambia el store.

## Cuanto usar

| Escenario | Solucion |
|-----------|----------|
| Estado local de un componente | `useState` |
| Estado de un formulario | `useState` o react-hook-form |
| Estado compartido entre componentes de la misma isla | `useState` en el componente padre + props |
| Estado compartido entre islas | **Zustand** |
| Datos del servidor | `useGloryContent` o `useWordPressApi` |
