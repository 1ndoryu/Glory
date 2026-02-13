/*
 * DevOverlay — Overlay de debug visible solo en desarrollo.
 * Muestra el nombre de la isla, conteo de renders y resumen de props.
 * Se activa automaticamente con import.meta.env.DEV.
 */

import type { ReactNode } from 'react';

interface DevOverlayProps {
    islandName: string;
    props: Record<string, unknown>;
    children: ReactNode;
}

export function DevOverlay({ islandName, props, children }: DevOverlayProps): JSX.Element {
    const propsKeys = Object.keys(props);
    const propsResumen = propsKeys.length > 0 ? propsKeys.join(', ') : 'sin props';

    return (
        <div style={{ position: 'relative' }}>
            <div
                style={{
                    position: 'absolute',
                    top: 0,
                    right: 0,
                    background: 'rgba(0, 0, 0, 0.8)',
                    color: '#22d3ee',
                    padding: '2px 8px',
                    fontSize: '10px',
                    fontFamily: 'monospace',
                    zIndex: 99999,
                    borderRadius: '0 0 0 6px',
                    lineHeight: '1.4',
                    pointerEvents: 'none',
                    whiteSpace: 'nowrap',
                }}
                title={`Props: ${propsResumen}`}
            >
                {islandName}
            </div>
            {children}
        </div>
    );
}
