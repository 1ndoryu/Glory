/**
 * PageBuilder - Componente principal del Page Builder
 *
 * Este componente encapsula TODA la logica del Page Builder:
 * - Estado de edicion
 * - Manejo de bloques (mover, editar, eliminar, agregar)
 * - Guardado via REST API
 * - UI de edicion (toolbar, controles, modal)
 *
 * USO BASICO:
 * ```tsx
 * <PageBuilder
 *     blocks={blocksFromPhp}
 *     isAdmin={true}
 *     saveEndpoint="/wp-json/glory/v1/page-blocks/123"
 *     restNonce="abc123"
 * >
 *     {(blocks) => <MyCustomLayout blocks={blocks} />}
 * </PageBuilder>
 * ```
 *
 * USO CON RENDER DEFAULT:
 * ```tsx
 * <PageBuilder
 *     blocks={blocksFromPhp}
 *     isAdmin={true}
 *     saveEndpoint="/wp-json/glory/v1/page-blocks/123"
 *     restNonce="abc123"
 * />
 * ```
 *
 * El componente es completamente opcional. Si no pasas blocks,
 * simplemente no renderiza nada del Page Builder.
 */

import {BlockRenderer} from '../BlockRenderer';
import {BlockEditorModal} from '../BlockEditorModal';
import {PageBuilderToolbar} from './PageBuilderToolbar';
import {EditModeToggle} from './EditModeToggle';
import {AddBlockPanel} from './AddBlockPanel';
import type {BlockData} from '../types';
import {usePageBuilder} from '../hooks/usePageBuilder';

export interface PageBuilderProps {
    /** Bloques iniciales (de PHP) */
    blocks?: BlockData[] | null;
    /** Si el usuario puede editar */
    isAdmin?: boolean;
    /** Endpoint REST para guardar */
    saveEndpoint?: string | null;
    /** Nonce para autenticacion REST */
    restNonce?: string | null;
    /** Render prop para contenido custom */
    children?: (blocks: BlockData[], isEditMode: boolean) => React.ReactNode;
    /** Callback cuando cambian los bloques (sin guardar) */
    onBlocksChange?: (blocks: BlockData[]) => void;
    /** Callback despues de guardar exitosamente */
    onSaveSuccess?: () => void;
    /** Callback si hay error al guardar */
    onSaveError?: (error: string) => void;
    /** Desactivar el Page Builder completamente */
    disabled?: boolean;
    /** Tipos de bloque permitidos (default: todos) */
    allowedBlockTypes?: string[];
    /** Texto personalizado para el boton de editar */
    editButtonText?: string;
    /** Texto personalizado para el toolbar */
    toolbarTitle?: string;
}

export function PageBuilder({blocks: initialBlocks, isAdmin = false, saveEndpoint, restNonce, children, onBlocksChange, onSaveSuccess, onSaveError, disabled = false, allowedBlockTypes, editButtonText = 'Editar Pagina', toolbarTitle = 'Editando Pagina'}: PageBuilderProps): JSX.Element | null {
    const {
        blocks, isEditMode, setIsEditMode,
        selectedBlockId, setSelectedBlockId,
        editingBlockId, setEditingBlockId,
        isSaving, editingBlock,
        handleMoveUp, handleMoveDown, handleDeleteBlock,
        handleEditBlock, handleUpdateBlock, handleAddBlock, handleSave,
    } = usePageBuilder({initialBlocks, saveEndpoint, restNonce, onBlocksChange, onSaveSuccess, onSaveError});

    /* Verificar si puede editar */
    const canEdit = isAdmin && !!saveEndpoint;

    /* Si esta desactivado, no renderizar nada */
    if (disabled) return null;

    /* Renderizado */
    return (
        <>
            {/* Toolbar de edicion */}
            {canEdit && isEditMode && <PageBuilderToolbar onExit={() => setIsEditMode(false)} onSave={handleSave} isSaving={isSaving} title={toolbarTitle} />}

            {/* Boton flotante para activar edicion */}
            {!isEditMode && <EditModeToggle onActivate={() => setIsEditMode(true)} canEdit={canEdit} text={editButtonText} />}

            {/* Contenido */}
            <div style={{paddingTop: isEditMode && canEdit ? '48px' : '0'}}>{children ? children(blocks, isEditMode) : <BlockRenderer blocks={blocks} isEditMode={isEditMode && canEdit} selectedBlockId={selectedBlockId} onSelectBlock={setSelectedBlockId} onEditBlock={handleEditBlock} onMoveUp={handleMoveUp} onMoveDown={handleMoveDown} onDeleteBlock={handleDeleteBlock} />}</div>

            {/* Panel agregar bloque */}
            {canEdit && isEditMode && <AddBlockPanel onAddBlock={handleAddBlock} allowedTypes={allowedBlockTypes} />}

            {/* Modal de edicion */}
            {editingBlock && <BlockEditorModal isOpen={true} blockType={editingBlock.type} blockData={editingBlock.props as Record<string, unknown>} onSave={newProps => handleUpdateBlock(editingBlock.id, newProps)} onClose={() => setEditingBlockId(null)} />}
        </>
    );
}

export default PageBuilder;
