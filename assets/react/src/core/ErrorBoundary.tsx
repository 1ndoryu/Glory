/*
 * Error Boundary individual por isla.
 * Captura errores de renderizado sin que una isla rota tumbe las demas.
 * En desarrollo muestra detalles del error; en produccion, un fallback limpio.
 */

import { Component, type ReactNode, type ErrorInfo } from 'react';

interface ErrorBoundaryProps {
    islandName: string;
    fallback?: ReactNode;
    children: ReactNode;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
}

export class IslandErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    state: ErrorBoundaryState = { hasError: false, error: null };

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error(
            `[Glory] Error en isla "${this.props.islandName}":`,
            error,
            info.componentStack,
        );
    }

    render(): ReactNode {
        if (!this.state.hasError) {
            return this.props.children;
        }

        /* Fallback personalizado del usuario */
        if (this.props.fallback) {
            return this.props.fallback;
        }

        /* En desarrollo: mostrar detalles del error */
        if (import.meta.env.DEV) {
            return (
                <div
                    style={{
                        padding: '16px',
                        border: '2px solid #ef4444',
                        borderRadius: '8px',
                        background: '#fef2f2',
                        color: '#991b1b',
                        fontFamily: 'monospace',
                        fontSize: '13px',
                    }}
                >
                    <strong>Error en isla &quot;{this.props.islandName}&quot;</strong>
                    <pre
                        style={{
                            margin: '8px 0 0',
                            whiteSpace: 'pre-wrap',
                            wordBreak: 'break-word',
                            fontSize: '12px',
                        }}
                    >
                        {this.state.error?.message}
                    </pre>
                    <button
                        onClick={() => this.setState({ hasError: false, error: null })}
                        style={{
                            marginTop: '8px',
                            padding: '4px 12px',
                            border: '1px solid #dc2626',
                            borderRadius: '4px',
                            background: '#fee2e2',
                            color: '#991b1b',
                            cursor: 'pointer',
                            fontSize: '12px',
                        }}
                    >
                        Reintentar
                    </button>
                </div>
            );
        }

        /* En produccion: fallback limpio */
        return (
            <div
                style={{
                    padding: '12px',
                    textAlign: 'center',
                    color: '#6b7280',
                    fontSize: '14px',
                }}
            >
                Contenido no disponible
            </div>
        );
    }
}
