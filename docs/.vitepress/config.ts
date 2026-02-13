import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Glory Framework',
  description: 'Framework TypeScript-first para WordPress. React es el UI, WordPress solo maneja datos.',
  lang: 'es-ES',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'Glory',

    nav: [
      { text: 'Guia', link: '/guia/introduccion' },
      { text: 'API', link: '/api/hooks/use-glory-content' },
      { text: 'CLI', link: '/cli/comandos' },
      { text: 'PHP Bridge', link: '/php/paginas' },
      {
        text: 'GitHub',
        link: 'https://github.com/1ndoryu/glorytemplate'
      }
    ],

    sidebar: {
      '/guia/': [
        {
          text: 'Inicio',
          items: [
            { text: 'Introduccion', link: '/guia/introduccion' },
            { text: 'Quick Start', link: '/guia/quick-start' },
            { text: 'Estructura del Proyecto', link: '/guia/estructura' },
          ]
        },
        {
          text: 'Conceptos',
          items: [
            { text: 'Arquitectura', link: '/guia/arquitectura' },
            { text: 'React Islands', link: '/guia/react-islands' },
            { text: 'Feature Flags', link: '/guia/feature-flags' },
          ]
        },
        {
          text: 'Desarrollo',
          items: [
            { text: 'Crear una Isla', link: '/guia/crear-isla' },
            { text: 'Crear una Pagina', link: '/guia/crear-pagina' },
            { text: 'Componentes', link: '/guia/componentes' },
            { text: 'Hooks Personalizados', link: '/guia/hooks-personalizados' },
            { text: 'Estilos', link: '/guia/estilos' },
            { text: 'Estado con Zustand', link: '/guia/estado' },
            { text: 'Datos de WordPress', link: '/guia/datos-wordpress' },
          ]
        },
      ],

      '/api/': [
        {
          text: 'Hooks',
          items: [
            { text: 'useGloryContent', link: '/api/hooks/use-glory-content' },
            { text: 'useGloryContext', link: '/api/hooks/use-glory-context' },
            { text: 'useWordPressApi', link: '/api/hooks/use-wordpress-api' },
            { text: 'useGloryOptions', link: '/api/hooks/use-glory-options' },
            { text: 'useGloryMedia', link: '/api/hooks/use-glory-media' },
            { text: 'useIslandProps', link: '/api/hooks/use-island-props' },
          ]
        },
        {
          text: 'Core',
          items: [
            { text: 'IslandRegistry', link: '/api/core/island-registry' },
            { text: 'GloryProvider', link: '/api/core/glory-provider' },
            { text: 'ErrorBoundary', link: '/api/core/error-boundary' },
            { text: 'Hydration', link: '/api/core/hydration' },
            { text: 'DevOverlay', link: '/api/core/dev-overlay' },
          ]
        },
        {
          text: 'Tipos',
          items: [
            { text: 'WordPress', link: '/api/tipos/wordpress' },
            { text: 'Glory', link: '/api/tipos/glory' },
            { text: 'API', link: '/api/tipos/api' },
          ]
        },
      ],

      '/cli/': [
        {
          text: 'CLI',
          items: [
            { text: 'Comandos', link: '/cli/comandos' },
            { text: 'create island', link: '/cli/create-island' },
            { text: 'create page', link: '/cli/create-page' },
            { text: 'create component', link: '/cli/create-component' },
            { text: 'create hook', link: '/cli/create-hook' },
            { text: 'setup', link: '/cli/setup' },
            { text: 'new', link: '/cli/new' },
          ]
        },
      ],

      '/php/': [
        {
          text: 'PHP Bridge',
          items: [
            { text: 'Paginas', link: '/php/paginas' },
            { text: 'Menus', link: '/php/menus' },
            { text: 'Assets', link: '/php/assets' },
            { text: 'SEO', link: '/php/seo' },
            { text: 'Contenido', link: '/php/contenido' },
            { text: 'Opciones', link: '/php/opciones' },
            { text: 'REST API', link: '/php/rest-api' },
            { text: 'Feature Flags', link: '/php/feature-flags' },
          ]
        },
        {
          text: 'Avanzado',
          items: [
            { text: 'Lazy Loading', link: '/php/lazy-loading' },
            { text: 'SSG e Hidratacion', link: '/php/hidratacion' },
            { text: 'Arquitectura PHP', link: '/php/arquitectura-php' },
          ]
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/1ndoryu/glorytemplate' }
    ],

    search: {
      provider: 'local'
    },

    footer: {
      message: 'Glory Framework — TypeScript-first para WordPress',
    },

    outline: {
      label: 'En esta pagina'
    },

    docFooter: {
      prev: 'Anterior',
      next: 'Siguiente'
    },

    lastUpdated: {
      text: 'Actualizado'
    },

    returnToTopLabel: 'Volver arriba',
    sidebarMenuLabel: 'Menu',
    darkModeSwitchLabel: 'Tema',
  }
})
