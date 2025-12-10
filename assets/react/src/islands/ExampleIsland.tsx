/**
 * ExampleIsland - Componente de ejemplo para probar la integracion React + Tailwind
 *
 * Este es un componente basico que demuestra:
 * - Recepcion de props desde PHP
 * - Estado local de React
 * - Interactividad
 * - Estilos con Tailwind CSS
 */

import {useState} from 'react';

interface ExampleIslandProps {
    title?: string;
    initialCount?: number;
    message?: string;
}

export function ExampleIsland({title = 'React funcionando en WordPress', initialCount = 0, message = 'Este componente fue hidratado desde PHP'}: ExampleIslandProps): JSX.Element {
    const [count, setCount] = useState(initialCount);

    return (
        <div className="p-6 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-sans transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl shadow-lg">
            <h3 className="m-0 mb-3 text-2xl font-semibold">{title}</h3>

            <p className="m-0 mb-4 opacity-90 text-base">{message}</p>

            <div className="flex items-center gap-3">
                <button onClick={() => setCount(c => c - 1)} className="px-4 py-2 text-lg border-none rounded-lg bg-white/20 text-white cursor-pointer transition-colors hover:bg-white/30">
                    -
                </button>

                <span className="text-2xl font-bold min-w-[60px] text-center">{count}</span>

                <button onClick={() => setCount(c => c + 1)} className="px-4 py-2 text-lg border-none rounded-lg bg-white/20 text-white cursor-pointer transition-colors hover:bg-white/30">
                    +
                </button>
            </div>

            <p className="mt-4 mb-0 text-xs opacity-70">Estado gestionado por React + Tailwind CSS</p>
        </div>
    );
}
