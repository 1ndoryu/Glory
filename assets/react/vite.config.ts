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
        base: isDev ? '/' : '/wp-content/themes/glory/Glory/assets/react/dist/',

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

            // Permite conexiones desde cualquier origen (necesario para Local by Flywheel)
            cors: true,

            // Escucha en todas las interfaces de red
            host: true,

            // Permitir servir archivos fuera de la raiz del proyecto (para App/React)
            fs: {
                allow: [
                    // Busca en el directorio actual y arriba hasta la raiz del tema
                    searchForWorkspaceRoot(process.cwd()),
                    '../../../App/React'
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
                '@app': resolve(__dirname, '../../../App/React')
            },
            // Asegurar que los modulos se resuelvan desde node_modules de Glory
            // Esto permite que App/React use las dependencias instaladas aqui
            dedupe: ['react', 'react-dom', 'lucide-react', 'framer-motion', '@editorjs/editorjs', '@editorjs/header', '@editorjs/paragraph', '@editorjs/list', '@editorjs/quote', '@editorjs/delimiter', '@editorjs/image', '@editorjs/embed']
        }
    };
});
