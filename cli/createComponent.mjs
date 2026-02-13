import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { getProjectRoot, toPascalCase, fileExists, log } from './utils.mjs';

/*
 * Genera el contenido del componente (.tsx)
 */
function generarPlantilla(nombre) {
    const pascal = toPascalCase(nombre);

    return `/**
 * Componente: ${pascal}
 * TO-DO: describir el proposito de este componente
 */

import type { ReactNode } from 'react';

interface ${pascal}Props {
    children?: ReactNode;
}

export function ${pascal}({ children }: ${pascal}Props): JSX.Element {
    return (
        <div className="contenedor${pascal}">
            {children}
        </div>
    );
}
`;
}

/*
 * Comando principal: crea un componente en App/React/components/
 */
export function createComponent(nombre) {
    const root = getProjectRoot();
    const pascal = toPascalCase(nombre);
    const ruta = resolve(root, `App/React/components/${pascal}.tsx`);

    if (fileExists(ruta)) {
        log(`El componente ${pascal} ya existe`, 'error');
        return false;
    }

    mkdirSync(dirname(ruta), { recursive: true });
    writeFileSync(ruta, generarPlantilla(nombre), 'utf-8');
    log(`Componente creado: App/React/components/${pascal}.tsx`, 'success');

    return true;
}
