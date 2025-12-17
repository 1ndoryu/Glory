/**
 * Glory Page Builder - Block Editor Modal
 *
 * Modal generico para editar las propiedades de un bloque.
 * Genera campos dinamicos basados en la definicion de `editableFields`
 * del bloque registrado en BlockRegistry.
 */

import {useState, useCallback, useEffect} from 'react';
import {X, Plus, Trash2} from 'lucide-react';
import {BlockRegistry} from './BlockRegistry';
import type {EditableField} from './types';

interface BlockEditorModalProps {
    /** Si el modal esta abierto */
    isOpen: boolean;
    /** Tipo de bloque a editar */
    blockType: string;
    /** Datos actuales del bloque */
    blockData: Record<string, unknown>;
    /** Callback al guardar */
    onSave: (newData: Record<string, unknown>) => void;
    /** Callback al cerrar/cancelar */
    onClose: () => void;
}

/*
 * Componente de campo individual
 */
interface FieldRendererProps {
    field: EditableField;
    value: unknown;
    onChange: (newValue: unknown) => void;
}

function FieldRenderer({field, value, onChange}: FieldRendererProps): JSX.Element {
    const baseInputStyles: React.CSSProperties = {
        width: '100%',
        padding: '10px 12px',
        background: '#1a1a1a',
        border: '1px solid rgba(255,255,255,0.1)',
        borderRadius: '6px',
        color: '#fff',
        fontSize: '14px',
        outline: 'none',
        transition: 'border-color 0.2s'
    };

    switch (field.type) {
        case 'text':
        case 'url':
        case 'number':
            return <input type={field.type === 'number' ? 'number' : 'text'} value={(value as string) || ''} onChange={e => onChange(field.type === 'number' ? Number(e.target.value) : e.target.value)} placeholder={field.placeholder} style={baseInputStyles} />;

        case 'textarea':
            return (
                <textarea
                    value={(value as string) || ''}
                    onChange={e => onChange(e.target.value)}
                    placeholder={field.placeholder}
                    rows={4}
                    style={{
                        ...baseInputStyles,
                        resize: 'vertical',
                        minHeight: '80px'
                    }}
                />
            );

        case 'select':
            return (
                <select
                    value={(value as string) || ''}
                    onChange={e => onChange(e.target.value)}
                    style={{
                        ...baseInputStyles,
                        cursor: 'pointer'
                    }}>
                    <option value="">Seleccionar...</option>
                    {field.options?.map(opt => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
            );

        case 'icon':
            return <input type="text" value={(value as string) || ''} onChange={e => onChange(e.target.value)} placeholder={field.placeholder || 'Nombre del icono (ej: Zap, Star)'} style={baseInputStyles} />;

        case 'array':
            return <ArrayFieldRenderer field={field} value={value as unknown[]} onChange={onChange} />;

        default:
            return <input type="text" value={(value as string) || ''} onChange={e => onChange(e.target.value)} placeholder={field.placeholder} style={baseInputStyles} />;
    }
}

/*
 * Renderizador de campos tipo array
 */
interface ArrayFieldRendererProps {
    field: EditableField;
    value: unknown[];
    onChange: (newValue: unknown[]) => void;
}

function ArrayFieldRenderer({field, value, onChange}: ArrayFieldRendererProps): JSX.Element {
    const items = Array.isArray(value) ? value : [];

    const handleItemChange = (index: number, itemField: string, newValue: unknown) => {
        const newItems = [...items];
        newItems[index] = {
            ...(newItems[index] as Record<string, unknown>),
            [itemField]: newValue
        };
        onChange(newItems);
    };

    const handleAddItem = () => {
        const newItem: Record<string, unknown> = {};
        field.itemFields?.forEach(f => {
            newItem[f.key] = '';
        });
        onChange([...items, newItem]);
    };

    const handleRemoveItem = (index: number) => {
        const newItems = items.filter((_, i) => i !== index);
        onChange(newItems);
    };

    return (
        <div className="campo-array" style={{display: 'flex', flexDirection: 'column', gap: '12px'}}>
            {items.map((item, index) => (
                <div
                    key={index}
                    className="item-array"
                    style={{
                        background: 'rgba(255,255,255,0.02)',
                        border: '1px solid rgba(255,255,255,0.05)',
                        borderRadius: '8px',
                        padding: '12px'
                    }}>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            marginBottom: '10px'
                        }}>
                        <span style={{fontSize: '12px', color: '#888'}}>Item {index + 1}</span>
                        <button
                            type="button"
                            onClick={() => handleRemoveItem(index)}
                            style={{
                                background: 'transparent',
                                border: 'none',
                                color: '#ef4444',
                                cursor: 'pointer',
                                padding: '4px'
                            }}>
                            <Trash2 size={14} />
                        </button>
                    </div>

                    <div style={{display: 'flex', flexDirection: 'column', gap: '10px'}}>
                        {field.itemFields?.map(itemField => (
                            <div key={itemField.key}>
                                <label
                                    style={{
                                        display: 'block',
                                        fontSize: '11px',
                                        color: '#888',
                                        marginBottom: '4px',
                                        textTransform: 'uppercase'
                                    }}>
                                    {itemField.label}
                                </label>
                                <FieldRenderer field={itemField} value={(item as Record<string, unknown>)[itemField.key]} onChange={newValue => handleItemChange(index, itemField.key, newValue)} />
                            </div>
                        ))}
                    </div>
                </div>
            ))}

            <button
                type="button"
                onClick={handleAddItem}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    padding: '10px',
                    background: 'rgba(255,255,255,0.05)',
                    border: '1px dashed rgba(255,255,255,0.2)',
                    borderRadius: '6px',
                    color: '#888',
                    cursor: 'pointer',
                    fontSize: '13px',
                    transition: 'all 0.2s'
                }}>
                <Plus size={14} />
                Agregar item
            </button>
        </div>
    );
}

/*
 * Modal principal de edicion
 */
export function BlockEditorModal({isOpen, blockType, blockData, onSave, onClose}: BlockEditorModalProps): JSX.Element | null {
    const [formData, setFormData] = useState<Record<string, unknown>>({});

    // Obtener definicion del bloque
    const definition = BlockRegistry.get(blockType);

    // Inicializar formData cuando se abre el modal
    useEffect(() => {
        if (isOpen && blockData) {
            setFormData({...blockData});
        }
    }, [isOpen, blockData]);

    const handleFieldChange = useCallback((key: string, value: unknown) => {
        setFormData(prev => ({
            ...prev,
            [key]: value
        }));
    }, []);

    const handleSave = useCallback(() => {
        onSave(formData);
        onClose();
    }, [formData, onSave, onClose]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose();
            }
        },
        [onClose]
    );

    if (!isOpen) return null;

    if (!definition) {
        return (
            <div
                className="modal-overlay"
                style={{
                    position: 'fixed',
                    inset: 0,
                    background: 'rgba(0,0,0,0.8)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000
                }}
                onClick={onClose}>
                <div
                    style={{
                        background: '#111',
                        padding: '24px',
                        borderRadius: '12px',
                        color: '#ef4444'
                    }}>
                    Error: Tipo de bloque "{blockType}" no encontrado
                </div>
            </div>
        );
    }

    const editableFields = definition.editableFields || [];

    return (
        <div
            className="modal-overlay"
            style={{
                position: 'fixed',
                inset: 0,
                background: 'rgba(0,0,0,0.85)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                zIndex: 1000,
                padding: '20px'
            }}
            onClick={onClose}
            onKeyDown={handleKeyDown}>
            <div
                className="modal-contenido"
                onClick={e => e.stopPropagation()}
                style={{
                    background: '#0a0a0a',
                    borderRadius: '16px',
                    border: '1px solid rgba(255,255,255,0.1)',
                    width: '100%',
                    maxWidth: '600px',
                    maxHeight: '90vh',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden'
                }}>
                {/* Header */}
                <div
                    className="modal-header"
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: '20px 24px',
                        borderBottom: '1px solid rgba(255,255,255,0.1)'
                    }}>
                    <h2
                        style={{
                            margin: 0,
                            fontSize: '18px',
                            fontWeight: 600,
                            color: '#fff'
                        }}>
                        Editar: {definition.label}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        style={{
                            background: 'transparent',
                            border: 'none',
                            color: '#888',
                            cursor: 'pointer',
                            padding: '4px'
                        }}>
                        <X size={20} />
                    </button>
                </div>

                {/* Body - Campos */}
                <div
                    className="modal-body"
                    style={{
                        padding: '24px',
                        overflowY: 'auto',
                        flex: 1
                    }}>
                    {editableFields.length === 0 ? (
                        <p style={{color: '#666', textAlign: 'center'}}>Este bloque no tiene campos editables configurados.</p>
                    ) : (
                        <div style={{display: 'flex', flexDirection: 'column', gap: '20px'}}>
                            {editableFields.map(field => (
                                <div key={field.key} className="campo-grupo">
                                    <label
                                        style={{
                                            display: 'block',
                                            fontSize: '13px',
                                            fontWeight: 500,
                                            color: '#ccc',
                                            marginBottom: '8px'
                                        }}>
                                        {field.label}
                                    </label>
                                    <FieldRenderer field={field} value={formData[field.key]} onChange={value => handleFieldChange(field.key, value)} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div
                    className="modal-footer"
                    style={{
                        display: 'flex',
                        justifyContent: 'flex-end',
                        gap: '12px',
                        padding: '20px 24px',
                        borderTop: '1px solid rgba(255,255,255,0.1)'
                    }}>
                    <button
                        type="button"
                        onClick={onClose}
                        style={{
                            padding: '10px 20px',
                            borderRadius: '8px',
                            border: '1px solid rgba(255,255,255,0.1)',
                            background: 'transparent',
                            color: '#888',
                            cursor: 'pointer',
                            fontSize: '14px'
                        }}>
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={handleSave}
                        style={{
                            padding: '10px 24px',
                            borderRadius: '8px',
                            border: 'none',
                            background: '#3b82f6',
                            color: '#fff',
                            cursor: 'pointer',
                            fontSize: '14px',
                            fontWeight: 500
                        }}>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    );
}

export default BlockEditorModal;
