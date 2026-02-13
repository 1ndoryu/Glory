# Menus

Glory gestiona menus de WordPress y los expone como datos tipados para React.

## Registrar un menu

```php
// App/Content/menu.php
use Glory\Manager\MenuManager;

MenuManager::register('principal', [
    'name' => 'Menu Principal',
    'items' => [
        ['title' => 'Inicio', 'url' => '/'],
        ['title' => 'Blog', 'url' => '/blog'],
        ['title' => 'Contacto', 'url' => '/contacto'],
    ]
]);
```

## Activar el feature

```php
// App/Config/control.php
GloryFeatures::enable('menu');
```

## Consumir en React

Los menus se inyectan en `window.__GLORY_CONTENT__` y se acceden con `useGloryContent`:

```tsx
import { useWordPressApi } from '@/hooks';
import type { WPMenu } from '@/types';

function NavIsland(): JSX.Element {
    const { data: menu } = useWordPressApi<WPMenu>('/wp/v2/menus/principal');

    return (
        <nav>
            {menu?.items.map(item => (
                <a key={item.id} href={item.url}>{item.title}</a>
            ))}
        </nav>
    );
}
```

## Tipos disponibles

```typescript
interface WPMenu {
    id: number;
    name: string;
    slug: string;
    items: WPMenuItem[];
}

interface WPMenuItem {
    id: number;
    title: string;
    url: string;
    target?: string;
    children?: WPMenuItem[];
}
```

## Clases internas

| Clase | Responsabilidad | Lineas |
|-------|----------------|--------|
| `MenuManager` | API publica, registro y resolucion | ~145 |
| `MenuDefinition` | Estructura de datos del menu | ~161 |
| `MenuSync` | Sincroniza menus PHP con WordPress | ~268 |
| `MenuNormalizer` | Normaliza items del menu | ~199 |
