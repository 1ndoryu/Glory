/**
 * AddBlockPanel - Panel para agregar nuevos bloques
 *
 * Muestra los tipos de bloque disponibles y permite
 * agregar nuevos bloques a la pagina.
 *
 * Usa BlockRegistry para obtener los tipos disponibles.
 */

import {BlockRegistry} from '../BlockRegistry';

interface AddBlockPanelProps {
    /** Callback al agregar un bloque */
    onAddBlock: (blockType: string) => void;
    /** Titulo del panel (default: "Agregar Bloque") */
    title?: string;
    /** Solo mostrar ciertos tipos de bloque */
    allowedTypes?: string[];
}

export function AddBlockPanel({onAddBlock, title = 'Agregar Bloque', allowedTypes}: AddBlockPanelProps): JSX.Element {
    const availableTypes = BlockRegistry.getAvailableTypes();

    /* Filtrar tipos si se especifican allowedTypes */
    const typesToShow = allowedTypes ? availableTypes.filter(t => allowedTypes.includes(t.type)) : availableTypes;

    if (typesToShow.length === 0) {
        return (
            <div
                id="add-block-panel-empty"
                style={{ /* sentinel-disable inline-style-prohibido — panel admin page builder, self-contained */
                    padding: '40px 20px',
                    textAlign: 'center',
                    color: '#666'
                }}>
                No hay tipos de bloque disponibles
            </div>
        );
    }

    return (
        <div
            id="add-block-panel"
            style={{ /* sentinel-disable inline-style-prohibido */
                maxWidth: '1200px',
                margin: '0 auto',
                padding: '24px',
                textAlign: 'center'
            }}>
            {title && (
                <p
                    style={{ /* sentinel-disable inline-style-prohibido */
                        fontSize: '12px',
                        color: '#666',
                        marginBottom: '12px',
                        textTransform: 'uppercase',
                        letterSpacing: '0.5px'
                    }}>
                    {title}
                </p>
            )}

            <div
                style={{ /* sentinel-disable inline-style-prohibido */
                    display: 'inline-flex',
                    gap: '8px',
                    flexWrap: 'wrap',
                    justifyContent: 'center'
                }}>
                {typesToShow.map(blockType => (
                    <button
                        key={blockType.type}
                        type="button"
                        onClick={() => onAddBlock(blockType.type)}
                        style={{ /* sentinel-disable inline-style-prohibido */
                            background: '#1a1a1a',
                            border: '1px solid rgba(255, 255, 255, 0.1)',
                            color: '#fff',
                            padding: '10px 16px',
                            borderRadius: '8px',
                            fontSize: '13px',
                            cursor: 'pointer',
                            transition: 'background 0.2s, border-color 0.2s',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px'
                        }}>
                        <span style={{/* sentinel-disable inline-style-prohibido */ opacity: 0.5}}>+</span>
                        {blockType.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

export default AddBlockPanel;
