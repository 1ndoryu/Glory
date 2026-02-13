# Introduccion

Glory es un framework TypeScript-first para WordPress. React renderiza **todo** el frontend. PHP solo existe como puente minimo entre WordPress y React.

## Que NO es Glory

- **No es un tema PHP** que soporta React como extra
- **No es un framework hibrido** con templates PHP y componentes React mezclados
- **No tiene modo PHP-only** — React es el unico modo

## Que SI es Glory

Un framework donde:

- **TypeScript** es el lenguaje principal
- **React 18** renderiza toda la interfaz
- **WordPress** solo gestiona datos, usuarios, media y REST API
- **PHP** registra paginas, menus, opciones y sirve datos JSON tipados
- **Vite** con HMR hace el desarrollo rapido

## El flujo de datos

```
WordPress (Admin)
    │
    ├── Contenido (posts, pages, media)
    ├── Opciones del tema
    └── Menus de navegacion
         │
         ▼
PHP Bridge (Glory/src/)
    │
    ├── Registra paginas → PageManager::reactPage()
    ├── Sirve contenido → window.__GLORY_CONTENT__
    ├── Sirve contexto  → window.GLORY_CONTEXT
    └── REST API        → /glory/v1/*
         │
         ▼
React (Glory/assets/react/ + App/React/)
    │
    ├── Islas independientes (1 isla = 1 pagina/seccion)
    ├── Hooks tipados (useGloryContent, useGloryContext...)
    ├── Componentes reutilizables
    └── Estado con Zustand
```

## Stack

| Tecnologia | Version | Rol |
|------------|---------|-----|
| WordPress | 6.0+ | CMS (solo datos) |
| PHP | 8.0+ | Bridge minimo |
| React | 18 | Todo el UI |
| TypeScript | 5.6 | Tipado strict |
| Vite | 6 | Build + HMR |
| Tailwind CSS | 4 | Estilos (opt-in) |
| shadcn/ui | — | Componentes UI (opt-in) |
| Zustand | — | Estado global |
| ESLint | 9 | Linting |
| Prettier | — | Formato |

## Principios

1. **TypeScript es el lenguaje.** Si puedes hacerlo en TS, hazlo en TS.
2. **PHP solo para lo que WordPress obliga.** Hooks, filters, REST, SEO.
3. **Cada archivo < 300 lineas.** SRP estricto.
4. **Cero `any` en TypeScript.** ESLint lo reporta como error.
5. **Islas independientes.** Una isla rota no tumba las demas.
6. **Feature flags para todo lo opcional.** Nada forzado.
