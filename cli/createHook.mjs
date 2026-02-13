import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { getProjectRoot, toPascalCase, toCamelCase, fileExists, log } from './utils.mjs';

/*
 * Genera el contenido del hook (.ts)
 */
function generarPlantilla(nombre) {
    const pascal = toPascalCase(nombre);
    /* Asegurar prefijo "use" */
    const hookName = nombre.startsWith('use') ? toCamelCase(nombre) : `use${pascal}`;
    const resultName = `${pascal}Result`;

    return `/**
 * Hook: ${hookName}
 * TO-DO: describir que hace este hook
 */

import { useState, useEffect } from 'react';

interface ${resultName} {
    /* TO-DO: definir tipo de retorno */
    data: unknown;
    isLoading: boolean;
    error: string | null;
}

export function ${hookName}(): ${resultName} {
    const [data, setData] = useState<unknown>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        /* TO-DO: implementar logica del hook */
        setData(null);
        setIsLoading(false);
    }, []);

    return { data, isLoading, error };
}
`;
}

/*
 * Comando principal: crea un hook en App/React/hooks/
 */
export function createHook(nombre) {
    const root = getProjectRoot();
    const pascal = toPascalCase(nombre);
    const hookName = nombre.startsWith('use') ? toCamelCase(nombre) : `use${pascal}`;
    const ruta = resolve(root, `App/React/hooks/${hookName}.ts`);

    if (fileExists(ruta)) {
        log(`El hook ${hookName} ya existe`, 'error');
        return false;
    }

    mkdirSync(dirname(ruta), { recursive: true });
    writeFileSync(ruta, generarPlantilla(nombre), 'utf-8');
    log(`Hook creado: App/React/hooks/${hookName}.ts`, 'success');

    return true;
}
