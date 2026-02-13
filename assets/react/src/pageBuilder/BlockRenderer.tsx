/**
 * Glory Page Builder - Block Renderer
 *
 * Componente que toma un array de bloques (JSON) y los renderiza
 * usando los componentes registrados en BlockRegistry.
 *
 * Soporta dos modos:
 * - Vista: Renderiza los bloques normalmente
 * - Edición: Envuelve cada bloque con controles (mover, editar, eliminar)
 */

import {BlockRegistry} from './BlockRegistry';
import type {BlockData} from './types';
import './styles/constructorPaginas.css';

interface BlockRendererProps {
    /** Array de bloques a renderizar */
    blocks: BlockData[];
    /** Si está en modo edición */
    isEditMode?: boolean;
    /** ID del bloque actualmente seleccionado */
    selectedBlockId?: string | null;
    /** Callback cuando se selecciona un bloque */
    onSelectBlock?: (id: string) => void;
    /** Callback para editar un bloque */
    onEditBlock?: (id: string) => void;
    /** Callback para mover bloque arriba */
    onMoveUp?: (id: string) => void;
    /** Callback para mover bloque abajo */
    onMoveDown?: (id: string) => void;
    /** Callback para eliminar bloque */
    onDeleteBlock?: (id: string) => void;
}

/**
 * Renderiza un bloque individual
 */
function renderBlock(
    block: BlockData,
    isEditMode: boolean,
    isSelected: boolean,
    isFirst: boolean,
    isLast: boolean,
    callbacks: {
        onSelect?: () => void;
        onEdit?: () => void;
        onMoveUp?: () => void;
        onMoveDown?: () => void;
        onDelete?: () => void;
    }
): JSX.Element | null {
    const definition = BlockRegistry.get(block.type);

    if (!definition) {
        console.error(`[BlockRenderer] Tipo de bloque "${block.type}" no registrado`);
        return (
            <div
                key={block.id}
                id={`block-error-${block.id}`}
                className="bloqueError">
                Bloque no encontrado: {block.type}
            </div>
        );
    }

    const Component = definition.component;
    const blockContent = <Component data={block.props} blockId={block.id} isEditing={isEditMode} />;

    /* En modo vista, renderizar directamente */
    if (!isEditMode) {
        return (
            <div key={block.id} id={`block-${block.id}`}>
                {blockContent}
            </div>
        );
    }

    /* En modo edición, envolver con controles */
    const claseBloque = isSelected ? 'bloqueEditable bloqueSeleccionado' : 'bloqueEditable';

    return (
        <div
            key={block.id}
            id={`block-${block.id}`}
            className={claseBloque}
            onClick={e => {
                e.stopPropagation();
                callbacks.onSelect?.();
            }}>
            {/* Controles del bloque */}
            {isSelected && (
                <div className="bloqueControles">
                    <span className="bloqueControlEtiqueta">
                        {definition.label}
                    </span>

                    <button
                        type="button"
                        className="botonControlBloque"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onMoveUp?.();
                        }}
                        disabled={isFirst}
                        title="Mover arriba"
                        aria-label="Mover bloque arriba">
                        ↑
                    </button>

                    <button
                        type="button"
                        className="botonControlBloque"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onMoveDown?.();
                        }}
                        disabled={isLast}
                        title="Mover abajo"
                        aria-label="Mover bloque abajo">
                        ↓
                    </button>

                    <button
                        type="button"
                        className="botonEditarBloque"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onEdit?.();
                        }}
                        title="Editar bloque">
                        Editar
                    </button>

                    <button
                        type="button"
                        className="botonEliminarBloque"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onDelete?.();
                        }}
                        title="Eliminar bloque"
                        aria-label="Eliminar bloque">
                        X
                    </button>
                </div>
            )}

            {/* Contenido del bloque */}
            {blockContent}
        </div>
    );
}

/**
 * BlockRenderer Component
 */
export function BlockRenderer({blocks, isEditMode = false, selectedBlockId = null, onSelectBlock, onEditBlock, onMoveUp, onMoveDown, onDeleteBlock}: BlockRendererProps): JSX.Element {
    if (!blocks || blocks.length === 0) {
        return (
            <div id="blocks-empty" className="bloquesVacio">
                {isEditMode ? 'No hay bloques. Haz clic en "Agregar Bloque" para comenzar.' : 'Esta página no tiene contenido.'}
            </div>
        );
    }

    return (
        <div id="blocks-container" className="bloques-contenedor">
            {blocks.map((block, index) =>
                renderBlock(block, isEditMode, selectedBlockId === block.id, index === 0, index === blocks.length - 1, {
                    onSelect: () => onSelectBlock?.(block.id),
                    onEdit: () => onEditBlock?.(block.id),
                    onMoveUp: () => onMoveUp?.(block.id),
                    onMoveDown: () => onMoveDown?.(block.id),
                    onDelete: () => onDeleteBlock?.(block.id)
                })
            )}
        </div>
    );
}

export default BlockRenderer;
