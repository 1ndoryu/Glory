import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default tseslint.config(
    /* Archivos ignorados */
    { ignores: ['dist/**', 'node_modules/**', 'scripts/**'] },

    /* Base: reglas JS recomendadas */
    js.configs.recommended,

    /* TypeScript: reglas recomendadas con type-checking */
    ...tseslint.configs.recommended,

    /* Configuracion global para archivos TS/TSX */
    {
        files: ['**/*.{ts,tsx}'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2022,
            },
        },
        plugins: {
            'react-hooks': reactHooks,
            'react-refresh': reactRefresh,
        },
        rules: {
            /* React Hooks */
            ...reactHooks.configs.recommended.rules,

            /* React Refresh: solo exportar componentes */
            'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],

            /* TypeScript */
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],

            /* Buenas practicas */
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'prefer-const': 'error',
            'no-var': 'error',
        },
    },

    /* Incluir archivos de App/React via la misma config */
    {
        files: ['../../../App/React/**/*.{ts,tsx}'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2022,
            },
        },
    },

    /* Prettier: deshabilita reglas que conflictan con formato */
    prettier,
);
