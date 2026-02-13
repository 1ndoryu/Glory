# SSG e Hidratacion

Glory soporta dos modos de montaje: CSR (default) e hidratacion SSG.

## CSR (Client-Side Rendering)

Default. El contenedor HTML esta vacio. React renderiza desde cero.

```html
<div data-island="MiIsla" data-props='{"titulo":"Hola"}'></div>
```

React llama `createRoot()` y monta el componente.

## SSG (Hidratacion)

El HTML fue pre-renderizado (por PHP o un proceso SSG). React "hidrata" el HTML existente sin recrear el DOM.

```html
<div data-island="MiIsla" data-hydrate="true">
    <h1>Hola</h1> <!-- HTML ya renderizado -->
</div>
```

React llama `hydrateRoot()` y adjunta event handlers al HTML existente.

## Deteccion automatica

Glory decide automaticamente el modo:

```
if (data-hydrate="true" AND contenido no vacio AND no es placeholder)
    → hydrateRoot()
else
    → createRoot()
```

El placeholder `<!-- react-island-loading -->` se descarta.

## Fallback

Si la hidratacion falla (mismatch entre server HTML y render de React):

```
1. hydrateRoot() → error
2. console.warn('[Glory] Fallback a CSR para "MiIsla"')
3. container.innerHTML = ''
4. createRoot() → monta desde cero
```

La isla sigue funcionando, solo pierde los beneficios de hidratacion para esa carga.

## Cuando usar SSG

| Escenario | Modo |
|-----------|------|
| Paginas dinamicas con datos del usuario | CSR |
| Landing pages estaticas | SSG |
| Paginas que necesitan SEO y performance | SSG |
| Dashboards, paneles admin | CSR |

## Implementar SSG

1. Pre-renderizar el HTML de la isla (via PHP o un proceso de build)
2. Inyectar el HTML dentro del contenedor `data-island`
3. Agregar `data-hydrate="true"` al contenedor
4. Asegurar que el componente React produce el mismo HTML
