/**
 * Glory Page Builder - Tipos TypeScript
 *
 * Define la estructura de datos para el sistema de bloques.
 * Estos tipos son usados tanto por Glory como por los proyectos.
 */

/**
 * Datos de un bloque individual
 */
export interface BlockData<T = Record<string, unknown>> {
    /** Identificador unico del bloque */
    id: string;
    /** Tipo de bloque (debe estar registrado en BlockRegistry) */
    type: string;
    /** Props especificos del bloque */
    props: T;
}

/**
 * Estructura completa de una pagina
 */
export interface PageData {
    /** Timestamp de ultima modificacion */
    time?: number;
    /** Lista ordenada de bloques */
    blocks: BlockData[];
    /** Version del schema */
    version?: string;
}

/**
 * Props que recibe cada componente de bloque
 */
export interface BlockComponentProps<T = Record<string, unknown>> {
    /** Datos del bloque */
    data: T;
    /** ID unico del bloque */
    blockId: string;
    /** Si el bloque esta en modo edicion */
    isEditing?: boolean;
    /** Callback para actualizar los datos del bloque */
    onUpdate?: (newData: T) => void;
}

/**
 * Tipos de campo para el editor de bloque
 */
export type FieldType = 'text' | 'textarea' | 'url' | 'number' | 'select' | 'icon' | 'array';

/**
 * Definicion de un campo editable
 */
export interface EditableField {
    /** Key del campo en props */
    key: string;
    /** Label visible para el usuario */
    label: string;
    /** Tipo de campo */
    type: FieldType;
    /** Placeholder opcional */
    placeholder?: string;
    /** Opciones para campos tipo select */
    options?: Array<{value: string; label: string}>;
    /** Para campos array, definicion de los campos de cada item */
    itemFields?: EditableField[];
}

/**
 * Definicion de un tipo de bloque
 */
export interface BlockDefinition<T = Record<string, unknown>> {
    /** Nombre interno del bloque */
    type: string;
    /** Nombre visible para el usuario */
    label: string;
    /** Icono del bloque (nombre de Lucide icon) */
    icon?: string;
    /** Componente React que renderiza el bloque */
    component: React.ComponentType<BlockComponentProps<T>>;
    /** Props por defecto al crear un nuevo bloque */
    defaultProps: T;
    /** Campos editables en el modal */
    editableFields?: EditableField[];
    /** Componente de edicion (opcional, si difiere del principal) */
    editComponent?: React.ComponentType<BlockComponentProps<T>>;
}

/**
 * Contexto del Page Builder
 */
export interface PageBuilderContextType {
    /** Si esta en modo edicion */
    isEditMode: boolean;
    /** Bloque actualmente seleccionado */
    selectedBlockId: string | null;
    /** Seleccionar un bloque */
    selectBlock: (id: string | null) => void;
    /** Actualizar datos de un bloque */
    updateBlock: (id: string, newProps: Record<string, unknown>) => void;
    /** Mover bloque hacia arriba */
    moveBlockUp: (id: string) => void;
    /** Mover bloque hacia abajo */
    moveBlockDown: (id: string) => void;
    /** Eliminar bloque */
    deleteBlock: (id: string) => void;
    /** Agregar nuevo bloque */
    addBlock: (type: string, afterId?: string) => void;
    /** Guardar cambios */
    saveChanges: () => Promise<void>;
    /** Estado de guardado */
    isSaving: boolean;
}
