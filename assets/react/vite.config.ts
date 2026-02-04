import {defineConfig, searchForWorkspaceRoot} from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import {resolve} from 'path';

// Configuracion para integracion con WordPress
// En desarrollo: Vite sirve los assets con HMR
// En produccion: Genera bundles optimizados en dist/

export default defineConfig(({mode}) => {
    const isDev = mode === 'development';

    return {
        plugins: [react(), tailwindcss()],

        // Base URL para los assets
        // En desarrollo: Vite dev server
        // En produccion: Ruta relativa al tema de WordPress
        // En desarrollo: Vite dev server (Debe ser la URL completa para que no busque en el dominio de WP)
        // En produccion: Ruta relativa al tema de WordPress
        base: isDev ? 'http://localhost:5173/' : '/wp-content/themes/glory/Glory/assets/react/dist/',

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
                }
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
                // Resolver dependencias de Capacitor desde App/React usando los paquetes instalados aqui
                '@codetrix-studio/capacitor-google-auth': resolve(__dirname, 'node_modules/@codetrix-studio/capacitor-google-auth'),
                '@capacitor/core': resolve(__dirname, 'node_modules/@capacitor/core'),
                '@capacitor/app': resolve(__dirname, 'node_modules/@capacitor/app')
            },
            // Asegurar que los modulos se resuelvan desde node_modules de Glory
            // Esto permite que App/React use las dependencias instaladas aqui
            dedupe: ['react', 'react-dom', 'lucide-react', 'framer-motion', 'zustand', '@editorjs/editorjs', '@editorjs/header', '@editorjs/paragraph', '@editorjs/list', '@editorjs/quote', '@editorjs/delimiter', '@editorjs/image', '@editorjs/embed', '@dnd-kit/core', '@dnd-kit/sortable', '@dnd-kit/utilities']
        }
    };
});
