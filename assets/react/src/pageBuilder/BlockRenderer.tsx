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
                className="bloque-error"
                style={{
                    padding: '20px',
                    background: 'rgba(239, 68, 68, 0.1)',
                    border: '1px dashed #ef4444',
                    borderRadius: '8px',
                    color: '#ef4444',
                    textAlign: 'center'
                }}>
                Bloque no encontrado: {block.type}
            </div>
        );
    }

    const Component = definition.component;
    const blockContent = <Component data={block.props} blockId={block.id} isEditing={isEditMode} />;

    // En modo vista, renderizar directamente
    if (!isEditMode) {
        return (
            <div key={block.id} id={`block-${block.id}`}>
                {blockContent}
            </div>
        );
    }

    // En modo edición, envolver con controles
    return (
        <div
            key={block.id}
            id={`block-${block.id}`}
            className={`bloque-editable ${isSelected ? 'bloque-seleccionado' : ''}`}
            onClick={e => {
                e.stopPropagation();
                callbacks.onSelect?.();
            }}
            style={{
                position: 'relative',
                outline: isSelected ? '2px solid #3b82f6' : '1px dashed rgba(255,255,255,0.2)',
                outlineOffset: '4px',
                borderRadius: '8px',
                cursor: 'pointer',
                transition: 'outline 0.2s ease'
            }}>
            {/* Controles del bloque */}
            {isSelected && (
                <div
                    className="bloque-controles"
                    style={{
                        position: 'absolute',
                        top: '-40px',
                        left: '0',
                        display: 'flex',
                        gap: '4px',
                        background: '#1a1a1a',
                        padding: '6px 8px',
                        borderRadius: '6px',
                        border: '1px solid rgba(255,255,255,0.1)',
                        zIndex: 100
                    }}>
                    <span
                        style={{
                            color: '#888',
                            fontSize: '12px',
                            marginRight: '8px',
                            display: 'flex',
                            alignItems: 'center'
                        }}>
                        {definition.label}
                    </span>

                    <button
                        type="button"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onMoveUp?.();
                        }}
                        disabled={isFirst}
                        title="Mover arriba"
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: isFirst ? '#444' : '#fff',
                            cursor: isFirst ? 'not-allowed' : 'pointer',
                            padding: '4px 8px',
                            borderRadius: '4px',
                            fontSize: '14px'
                        }}>
                        ↑
                    </button>

                    <button
                        type="button"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onMoveDown?.();
                        }}
                        disabled={isLast}
                        title="Mover abajo"
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: isLast ? '#444' : '#fff',
                            cursor: isLast ? 'not-allowed' : 'pointer',
                            padding: '4px 8px',
                            borderRadius: '4px',
                            fontSize: '14px'
                        }}>
                        ↓
                    </button>

                    <button
                        type="button"
                        onClick={e => {
                            e.stopPropagation();
                            callbacks.onEdit?.();
                        }}
                        title="Editar bloque"
                        style={{
                            background: '#3b82f6',
                            border: 'none',
                            color: '#fff',
                            cursor: 'pointer',
                            padding: '4px 12px',
                            borderRadius: '4px',
                            fontSize: '12px',
                            fontWeight: 500
                        }}>
                        Editar
                    </button>

                    <button
                        type="button"
                        onClick={e => {
                            e.stopPropagation();
                            if (confirm('¿Eliminar este bloque?')) {
                                callbacks.onDelete?.();
                            }
                        }}
                        title="Eliminar bloque"
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: '#ef4444',
                            cursor: 'pointer',
                            padding: '4px 8px',
                            borderRadius: '4px',
                            fontSize: '14px'
                        }}>
                        🗑
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
            <div
                id="blocks-empty"
                style={{
                    padding: '60px 20px',
                    textAlign: 'center',
                    color: '#666'
                }}>
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
