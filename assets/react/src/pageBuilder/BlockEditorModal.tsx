/**
 * Glory Page Builder - Block Editor Modal
 *
 * Modal generico para editar las propiedades de un bloque.
 * Genera campos dinamicos basados en la definicion de `editableFields`
 * del bloque registrado en BlockRegistry.
 */

import {useState, useCallback} from 'react';
import {X, Plus, Trash2} from 'lucide-react';
import {BlockRegistry} from './BlockRegistry';
import type {EditableField} from './types';
import './styles/constructorPaginas.css';

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
    switch (field.type) {
        case 'text':
        case 'url':
        case 'number':
            return <input type={field.type === 'number' ? 'number' : 'text'} value={(value as string) || ''} onChange={e => onChange(field.type === 'number' ? Number(e.target.value) : e.target.value)} placeholder={field.placeholder} className="campoEntradaBase" />;

        case 'textarea':
            return (
                <textarea
                    value={(value as string) || ''}
                    onChange={e => onChange(e.target.value)}
                    placeholder={field.placeholder}
                    rows={4}
                    className="campoEntradaBase campoTextoArea"
                />
            );

        case 'select':
            return (
                <select
                    value={(value as string) || ''}
                    onChange={e => onChange(e.target.value)}
                    className="campoEntradaBase campoSelector">
                    <option value="">Seleccionar...</option>
                    {field.options?.map(opt => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
            );

        case 'icon':
            return <input type="text" value={(value as string) || ''} onChange={e => onChange(e.target.value)} placeholder={field.placeholder || 'Nombre del icono (ej: Zap, Star)'} className="campoEntradaBase" />;

        case 'array':
            return <ArrayFieldRenderer field={field} value={value as unknown[]} onChange={onChange} />;

        default:
            return <input type="text" value={(value as string) || ''} onChange={e => onChange(e.target.value)} placeholder={field.placeholder} className="campoEntradaBase" />;
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
        <div className="campoArrayContenedor">
            {items.map((item, index) => (
                <div key={index} className="campoArrayItem">
                    <div className="campoArrayItemCabecera">
                        <span className="campoArrayItemNumero">Item {index + 1}</span>
                        <button
                            type="button"
                            onClick={() => handleRemoveItem(index)}
                            className="botonEliminarItem">
                            <Trash2 size={14} />
                        </button>
                    </div>

                    <div className="campoArrayItemCampos">
                        {field.itemFields?.map(itemField => (
                            <div key={itemField.key}>
                                <label className="campoArraySubEtiqueta">
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
                className="botonAgregarItem">
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
    const [formData, setFormData] = useState<Record<string, unknown>>(() => ({...blockData}));

    /* Obtener definicion del bloque */
    const definition = BlockRegistry.get(blockType);

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
                className="modalOverlayError"
                onClick={onClose}>
                <div className="modalError">
                    Error: Tipo de bloque "{blockType}" no encontrado
                </div>
            </div>
        );
    }

    const editableFields = definition.editableFields || [];

    return (
        <div
            className="modalOverlay"
            role="dialog"
            aria-modal="true"
            aria-label={`Editar bloque: ${definition.label}`}
            onClick={onClose}
            onKeyDown={handleKeyDown}>
            <div
                className="modalContenido"
                onClick={e => e.stopPropagation()}>
                {/* Header */}
                <div className="modalCabecera">
                    <h2 className="modalTitulo">
                        Editar: {definition.label}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="botonCerrarModal">
                        <X size={20} />
                    </button>
                </div>

                {/* Body - Campos */}
                <div className="modalCuerpo">
                    {editableFields.length === 0 ? (
                        <p className="modalSinCampos">Este bloque no tiene campos editables configurados.</p>
                    ) : (
                        <div className="modalCamposContenedor">
                            {editableFields.map(field => (
                                <div key={field.key} className="campo-grupo">
                                    <label className="campoEtiqueta">
                                        {field.label}
                                    </label>
                                    <FieldRenderer field={field} value={formData[field.key]} onChange={value => handleFieldChange(field.key, value)} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="modalPie">
                    <button
                        type="button"
                        onClick={onClose}
                        className="botonCancelar">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={handleSave}
                        className="botonGuardar">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    );
}

export default BlockEditorModal;
