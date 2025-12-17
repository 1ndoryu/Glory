/**
 * EditModeToggle - Boton flotante para activar modo edicion
 *
 * Muestra un boton flotante que permite a los admins
 * entrar al modo de edicion del Page Builder.
 *
 * Solo se renderiza si canEdit es true.
 */

interface EditModeToggleProps {
    /** Callback al activar modo edicion */
    onActivate: () => void;
    /** Si el usuario puede editar */
    canEdit: boolean;
    /** Texto del boton (default: "Editar Pagina") */
    text?: string;
    /** Posicion del boton */
    position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left';
}

export function EditModeToggle({onActivate, canEdit, text = 'Editar Pagina', position = 'bottom-right'}: EditModeToggleProps): JSX.Element | null {
    if (!canEdit) return null;

    const positionStyles: Record<string, React.CSSProperties> = {
        'bottom-right': {bottom: '24px', right: '24px'},
        'bottom-left': {bottom: '24px', left: '24px'},
        'top-right': {top: '80px', right: '24px'},
        'top-left': {top: '80px', left: '24px'}
    };

    return (
        <button
            id="page-builder-edit-toggle"
            type="button"
            onClick={onActivate}
            style={{
                position: 'fixed',
                ...positionStyles[position],
                zIndex: 50,
                background: '#fff',
                color: '#000',
                padding: '12px 20px',
                borderRadius: '9999px',
                border: 'none',
                fontSize: '14px',
                fontWeight: 500,
                cursor: 'pointer',
                boxShadow: '0 4px 20px rgba(0, 0, 0, 0.3)',
                transition: 'transform 0.2s, box-shadow 0.2s'
            }}>
            {text}
        </button>
    );
}

export default EditModeToggle;
