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

import {useState, useCallback, useMemo} from 'react';
import {BlockRegistry} from '../BlockRegistry';
import {BlockRenderer} from '../BlockRenderer';
import {BlockEditorModal} from '../BlockEditorModal';
import {PageBuilderToolbar} from './PageBuilderToolbar';
import {EditModeToggle} from './EditModeToggle';
import {AddBlockPanel} from './AddBlockPanel';
import type {BlockData} from '../types';

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
    /* Si esta desactivado o no hay bloques iniciales, no renderizar nada */
    if (disabled) return null;

    /* Estado */
    const [blocks, setBlocks] = useState<BlockData[]>(initialBlocks || []);
    const [isEditMode, setIsEditMode] = useState(false);
    const [selectedBlockId, setSelectedBlockId] = useState<string | null>(null);
    const [editingBlockId, setEditingBlockId] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    /* Verificar si puede editar */
    const canEdit = isAdmin && !!saveEndpoint;

    /* Handlers de bloques */
    const handleMoveUp = useCallback(
        (id: string) => {
            setBlocks(prev => {
                const index = prev.findIndex(b => b.id === id);
                if (index <= 0) return prev;
                const newBlocks = [...prev];
                [newBlocks[index - 1], newBlocks[index]] = [newBlocks[index], newBlocks[index - 1]];
                onBlocksChange?.(newBlocks);
                return newBlocks;
            });
        },
        [onBlocksChange]
    );

    const handleMoveDown = useCallback(
        (id: string) => {
            setBlocks(prev => {
                const index = prev.findIndex(b => b.id === id);
                if (index < 0 || index >= prev.length - 1) return prev;
                const newBlocks = [...prev];
                [newBlocks[index], newBlocks[index + 1]] = [newBlocks[index + 1], newBlocks[index]];
                onBlocksChange?.(newBlocks);
                return newBlocks;
            });
        },
        [onBlocksChange]
    );

    const handleDeleteBlock = useCallback(
        (id: string) => {
            setBlocks(prev => {
                const newBlocks = prev.filter(b => b.id !== id);
                onBlocksChange?.(newBlocks);
                return newBlocks;
            });
            setSelectedBlockId(null);
        },
        [onBlocksChange]
    );

    const handleEditBlock = useCallback((id: string) => {
        setEditingBlockId(id);
    }, []);

    const handleUpdateBlock = useCallback(
        (blockId: string, newProps: Record<string, unknown>) => {
            setBlocks(prev => {
                const newBlocks = prev.map(block => (block.id === blockId ? {...block, props: newProps} : block));
                onBlocksChange?.(newBlocks);
                return newBlocks;
            });
        },
        [onBlocksChange]
    );

    const handleAddBlock = useCallback(
        (type: string) => {
            const defaultProps = BlockRegistry.getDefaultProps(type);
            const newBlock: BlockData = {
                id: `${type}-${Date.now()}`,
                type,
                props: defaultProps
            };
            setBlocks(prev => {
                const newBlocks = [...prev, newBlock];
                onBlocksChange?.(newBlocks);
                return newBlocks;
            });
        },
        [onBlocksChange]
    );

    /* Handler de guardado */
    const handleSave = useCallback(async () => {
        if (!saveEndpoint) {
            console.warn('[PageBuilder] No hay endpoint de guardado configurado');
            return;
        }

        setIsSaving(true);
        try {
            const headers: Record<string, string> = {
                'Content-Type': 'application/json'
            };

            if (restNonce) {
                headers['X-WP-Nonce'] = restNonce;
            }

            const response = await fetch(saveEndpoint, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({blocks})
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('[PageBuilder] Guardado exitoso:', data);
                onSaveSuccess?.();
                alert('Cambios guardados correctamente');
            } else {
                console.error('[PageBuilder] Error del servidor:', data);
                const errorMsg = data.message || 'Error al guardar';
                onSaveError?.(errorMsg);
                alert(errorMsg);
            }
        } catch (error) {
            console.error('[PageBuilder] Error de red:', error);
            const errorMsg = 'Error de conexion al guardar';
            onSaveError?.(errorMsg);
            alert(errorMsg);
        } finally {
            setIsSaving(false);
        }
    }, [blocks, saveEndpoint, restNonce, onSaveSuccess, onSaveError]);

    /* Bloque siendo editado */
    const editingBlock = useMemo(() => {
        if (!editingBlockId) return null;
        return blocks.find(b => b.id === editingBlockId) || null;
    }, [editingBlockId, blocks]);

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
