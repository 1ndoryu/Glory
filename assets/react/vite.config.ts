import {defineConfig, searchForWorkspaceRoot, type Plugin} from 'vite';
import react from '@vitejs/plugin-react';
import {resolve} from 'path';

// Configuracion para integracion con WordPress
// En desarrollo: Vite sirve los assets con HMR
// En produccion: Genera bundles optimizados en dist/

/* [183A-46] Plugin que resuelve modulos externos (Tauri, Capacitor legacy) como stubs
 * vacios durante dev. Sin esto, Vite dev server falla porque los paquetes no estan
 * instalados en Glory/assets/react/ (solo existen en desktop/). En build se manejan
 * via rollupOptions.external. */
const MODULOS_EXTERNOS = [
    '@capacitor/local-notifications',
    '@tauri-apps/plugin-notification',
    '@tauri-apps/plugin-fs',
    '@tauri-apps/plugin-shell',
    '@tauri-apps/api/app',
];

function pluginModulosExternos(): Plugin {
    return {
        name: 'glory-external-stubs',
        enforce: 'pre',
        resolveId(id) {
            if (MODULOS_EXTERNOS.some(m => id === m || id.startsWith(m + '/'))) {
                return '\0external:' + id;
            }
            return null;
        },
        load(id) {
            if (id.startsWith('\0external:')) {
                return 'export default {};';
            }
            return null;
        },
    };
}

export default defineConfig(({mode}) => {
    const isDev = mode === 'development';

    return {
        plugins: [pluginModulosExternos(), react()],

        // Base URL para los assets
        // En desarrollo: Vite dev server
        // En produccion: Ruta relativa al tema de WordPress
        // En desarrollo: Vite dev server (Debe ser la URL completa para que no busque en el dominio de WP)
        // En produccion: Ruta relativa al tema de WordPress
        base: isDev ? 'http://localhost:5173/' : '/wp-content/themes/glorytemplate/Glory/assets/react/dist/',

        build: {
            // Directorio de salida
            outDir: 'dist',

            // Genera manifest.json para que PHP sepa que archivos cargar
            manifest: true,

            // Configuracion de Rollup
            rollupOptions: {
                input: {
                    // Entry point principal para las islas React
                    main: resolve(__dirname, 'src/main.tsx')
                },
                output: {
                    // Nombres de archivo predecibles para facilitar el enqueue en PHP
                    entryFileNames: 'assets/[name]-[hash].js',
                    chunkFileNames: 'assets/[name]-[hash].js',
                    assetFileNames: 'assets/[name]-[hash].[ext]'
                },
                /*
                 * Módulos externos que no deben bundlearse
                 * Se resuelven en runtime (plugins Tauri opcionales, Capacitor legacy)
                 */
                external: MODULOS_EXTERNOS
            },

            // Limpia el directorio antes de cada build
            emptyOutDir: true
        },

        server: {
            // Puerto para el dev server
            port: 5173,
            origin: 'http://localhost:5173',

            // Permite conexiones desde cualquier origen (necesario para Local by Flywheel)
            cors: true,

            // Escucha en todas las interfaces de red
            host: true,

            // Permitir servir archivos fuera de la raiz del proyecto (para App/React)
            fs: {
                allow: [
                    // Busca en el directorio actual y arriba hasta la raiz del tema
                    searchForWorkspaceRoot(process.cwd()),
                    '../../../App/React',
                    '../../../App/Assets',
                    '../../../Mezclador',
                    '../images'
                ]
            },

            // Configuracion de HMR para funcionar con dominios .local
            hmr: {
                // El host sera localhost porque Vite corre en tu maquina
                host: 'localhost',
                port: 5173,
                protocol: 'ws'
            }
        },

        resolve: {
            alias: {
                '@': resolve(__dirname, 'src'),
                // Alias para importar componentes especificos del proyecto desde App/React
                '@app': resolve(__dirname, '../../../App/React'),
                // C184: Mezclador (Mini DAW) aislado del App principal
                '@mezclador': resolve(__dirname, '../../../Mezclador'),
                // Resolver dependencias de Capacitor desde App/React usando los paquetes instalados aqui
                '@codetrix-studio/capacitor-google-auth': resolve(__dirname, 'node_modules/@codetrix-studio/capacitor-google-auth'),
                '@capacitor/core': resolve(__dirname, 'node_modules/@capacitor/core'),
                '@capacitor/app': resolve(__dirname, 'node_modules/@capacitor/app'),
                // soundtouchjs instalado aqui pero usado desde Mezclador/ (fuera del arbol de node_modules)
                'soundtouchjs': resolve(__dirname, 'node_modules/soundtouchjs')
            },
            // Asegurar que los modulos se resuelvan desde node_modules de Glory
            // Esto permite que App/React use las dependencias instaladas aqui
            dedupe: ['react', 'react-dom', 'lucide-react', 'framer-motion', 'zustand', '@editorjs/editorjs', '@editorjs/header', '@editorjs/paragraph', '@editorjs/list', '@editorjs/quote', '@editorjs/delimiter', '@editorjs/image', '@editorjs/embed', '@dnd-kit/core', '@dnd-kit/sortable', '@dnd-kit/utilities']
        }
    };
});
