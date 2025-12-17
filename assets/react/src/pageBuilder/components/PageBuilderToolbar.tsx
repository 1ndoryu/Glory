/**
 * PageBuilderToolbar - Toolbar de edicion del Page Builder
 *
 * Componente agnostico que muestra la barra de herramientas
 * cuando el Page Builder esta en modo edicion.
 *
 * Se puede personalizar via props:
 * - onExit: Callback al salir del modo edicion
 * - onSave: Callback al guardar cambios
 * - isSaving: Estado de guardado
 * - title: Titulo opcional
 */

interface PageBuilderToolbarProps {
    /** Callback al salir del modo edicion */
    onExit: () => void;
    /** Callback al guardar cambios */
    onSave: () => void;
    /** Si esta guardando actualmente */
    isSaving?: boolean;
    /** Titulo a mostrar (default: "Editando Pagina") */
    title?: string;
    /** Texto del boton salir (default: "Salir") */
    exitText?: string;
    /** Texto del boton guardar (default: "Guardar Cambios") */
    saveText?: string;
    /** Texto mientras guarda (default: "Guardando...") */
    savingText?: string;
}

export function PageBuilderToolbar({onExit, onSave, isSaving = false, title = 'Editando Pagina', exitText = 'Salir', saveText = 'Guardar Cambios', savingText = 'Guardando...'}: PageBuilderToolbarProps): JSX.Element {
    return (
        <div
            id="page-builder-toolbar"
            className="pb-toolbar"
            style={{
                position: 'fixed',
                top: '64px',
                left: 0,
                right: 0,
                zIndex: 40,
                background: 'rgba(10, 10, 10, 0.95)',
                backdropFilter: 'blur(12px)',
                borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
                padding: '12px 24px'
            }}>
            <div
                style={{
                    maxWidth: '1200px',
                    margin: '0 auto',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between'
                }}>
                <div style={{display: 'flex', alignItems: 'center', gap: '16px'}}>
                    <button
                        type="button"
                        onClick={onExit}
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: '#888',
                            cursor: 'pointer',
                            fontSize: '14px',
                            padding: '8px 0',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '4px'
                        }}>
                        ← {exitText}
                    </button>
                    <span
                        style={{
                            fontSize: '14px',
                            fontWeight: 500,
                            color: '#fff'
                        }}>
                        {title}
                    </span>
                </div>

                <button
                    type="button"
                    onClick={onSave}
                    disabled={isSaving}
                    style={{
                        background: '#fff',
                        border: 'none',
                        color: '#000',
                        padding: '10px 20px',
                        borderRadius: '8px',
                        fontSize: '14px',
                        fontWeight: 500,
                        cursor: isSaving ? 'not-allowed' : 'pointer',
                        opacity: isSaving ? 0.5 : 1,
                        transition: 'opacity 0.2s'
                    }}>
                    {isSaving ? savingText : saveText}
                </button>
            </div>
        </div>
    );
}

export default PageBuilderToolbar;
