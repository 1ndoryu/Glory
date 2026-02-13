# create island

Crea una isla React con estilos y registro automatico.

## Uso

```bash
npx glory create island <Nombre>
```

## Que genera

```
App/React/islands/{Nombre}Island.tsx   ← Componente tipado
App/React/styles/{nombre}.css          ← Archivo de estilos
App/React/appIslands.tsx               ← Import + registro (auto)
```

## Ejemplo

```bash
npx glory create island Contacto
```

### ContactoIsland.tsx

```tsx
import '../styles/contacto.css';

interface ContactoIslandProps {
    titulo?: string;
}

export function ContactoIsland({
    titulo = 'Contacto'
}: ContactoIslandProps): JSX.Element {
    return (
        <div id="seccionContacto" className="contenedorContacto">
            <h1>{titulo}</h1>
        </div>
    );
}

export default ContactoIsland;
```

### contacto.css

```css
.contenedorContacto {
    padding: var(--espaciado-medio, 2rem);
}
```

### appIslands.tsx (auto-actualizado)

```tsx
import {ContactoIsland} from './islands/ContactoIsland';

export const appIslands = {
    // ...existentes,
    ContactoIsland: ContactoIsland as React.ComponentType<Record<string, unknown>>,
};
```

## Comportamiento

- Si la isla ya existe, muestra error y no sobrescribe
- Si `appIslands.tsx` no existe, omite el registro automatico
- El CSS solo se crea si no existe previamente
- El nombre se convierte a PascalCase automaticamente
