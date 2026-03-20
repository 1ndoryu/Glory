/*
 * Error Boundary individual por isla.
 * Captura errores de renderizado sin que una isla rota tumbe las demas.
 * En desarrollo muestra detalles del error; en produccion, un fallback limpio.
 */

import { Component, type ReactNode, type ErrorInfo } from 'react';

interface ErrorBoundaryProps {
    islandName: string;
    fallback?: ReactNode;
    /** [193A-46] Cambiar este valor resetea el error boundary automáticamente.
     * Útil para keep-alive SPA: al re-navegar a una isla, el orden cambia
     * y el boundary se auto-limpia sin remontaje. */
    resetKey?: number;
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

    /* [193A-46] Auto-reset cuando cambia resetKey (re-navegación SPA keep-alive) */
    componentDidUpdate(prevProps: ErrorBoundaryProps): void {
        if (
            this.state.hasError &&
            this.props.resetKey !== undefined &&
            prevProps.resetKey !== this.props.resetKey
        ) {
            this.setState({ hasError: false, error: null });
        }
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

        /* En produccion: fallback limpio con boton reintentar */
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
                <br />
                <button
                    onClick={() => this.setState({ hasError: false, error: null })}
                    style={{
                        marginTop: '8px',
                        padding: '4px 12px',
                        border: '1px solid #4b5563',
                        borderRadius: '4px',
                        background: 'transparent',
                        color: '#9ca3af',
                        cursor: 'pointer',
                        fontSize: '12px',
                    }}
                >
                    Reintentar
                </button>
            </div>
        );
    }
}
