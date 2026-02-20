/**
 * usePageBuilder
 *
 * Hook para lógica del componente PageBuilder.
 * Maneja estado de bloques, modo edición, guardado vía REST API
 * y operaciones CRUD sobre bloques (mover, editar, eliminar, agregar).
 */

import {useState, useCallback, useMemo} from 'react';
import {BlockRegistry} from '../BlockRegistry';
import type {BlockData} from '../types';

interface UsePageBuilderParams {
    initialBlocks: BlockData[] | null | undefined;
    saveEndpoint?: string | null;
    restNonce?: string | null;
    onBlocksChange?: (blocks: BlockData[]) => void;
    onSaveSuccess?: () => void;
    onSaveError?: (error: string) => void;
}

export function usePageBuilder({initialBlocks, saveEndpoint, restNonce, onBlocksChange, onSaveSuccess, onSaveError}: UsePageBuilderParams) {
    const [blocks, setBlocks] = useState<BlockData[]>(initialBlocks || []);
    const [isEditMode, setIsEditMode] = useState(false);
    const [selectedBlockId, setSelectedBlockId] = useState<string | null>(null);
    const [editingBlockId, setEditingBlockId] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    /* Mover bloque hacia arriba */
    const handleMoveUp = useCallback((id: string) => {
        setBlocks(prev => {
            const index = prev.findIndex(b => b.id === id);
            if (index <= 0) return prev;
            const newBlocks = [...prev];
            [newBlocks[index - 1], newBlocks[index]] = [newBlocks[index], newBlocks[index - 1]];
            onBlocksChange?.(newBlocks);
            return newBlocks;
        });
    }, [onBlocksChange]);

    /* Mover bloque hacia abajo */
    const handleMoveDown = useCallback((id: string) => {
        setBlocks(prev => {
            const index = prev.findIndex(b => b.id === id);
            if (index < 0 || index >= prev.length - 1) return prev;
            const newBlocks = [...prev];
            [newBlocks[index], newBlocks[index + 1]] = [newBlocks[index + 1], newBlocks[index]];
            onBlocksChange?.(newBlocks);
            return newBlocks;
        });
    }, [onBlocksChange]);

    /* Eliminar bloque por ID */
    const handleDeleteBlock = useCallback((id: string) => {
        setBlocks(prev => {
            const newBlocks = prev.filter(b => b.id !== id);
            onBlocksChange?.(newBlocks);
            return newBlocks;
        });
        setSelectedBlockId(null);
    }, [onBlocksChange]);

    /* Abrir modal de edición para un bloque */
    const handleEditBlock = useCallback((id: string) => {
        setEditingBlockId(id);
    }, []);

    /* Actualizar props de un bloque específico */
    const handleUpdateBlock = useCallback((blockId: string, newProps: Record<string, unknown>) => {
        setBlocks(prev => {
            const newBlocks = prev.map(block =>
                block.id === blockId ? {...block, props: newProps} : block
            );
            onBlocksChange?.(newBlocks);
            return newBlocks;
        });
    }, [onBlocksChange]);

    /* Agregar nuevo bloque al final */
    const handleAddBlock = useCallback((type: string) => {
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
    }, [onBlocksChange]);

    /* Guardar bloques vía REST API */
    const handleSave = useCallback(async () => {
        if (!saveEndpoint) {
            console.warn('[PageBuilder] No hay endpoint de guardado configurado');
            return;
        }

        setIsSaving(true);
        try {
            const headers: Record<string, string> = {'Content-Type': 'application/json'};
            if (restNonce) headers['X-WP-Nonce'] = restNonce;

            const response = await fetch(saveEndpoint, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify({blocks})
            });

            const data = await response.json();

            if (response.ok && data.success) {
                if (import.meta.env.DEV) console.warn('[PageBuilder] Guardado exitoso:', data);
                onSaveSuccess?.();
            } else {
                const errorMsg = data.message || 'Error al guardar';
                if (import.meta.env.DEV) console.error('[PageBuilder] Error del servidor:', data);
                onSaveError?.(errorMsg);
            }
        } catch (error) {
            if (import.meta.env.DEV) console.error('[PageBuilder] Error de red:', error);
            onSaveError?.('Error de conexion al guardar');
        } finally {
            setIsSaving(false);
        }
    }, [blocks, saveEndpoint, restNonce, onSaveSuccess, onSaveError]);

    /* Bloque actualmente en edición */
    const editingBlock = useMemo(() => {
        if (!editingBlockId) return null;
        return blocks.find(b => b.id === editingBlockId) || null;
    }, [editingBlockId, blocks]);

    return {
        blocks,
        isEditMode,
        setIsEditMode,
        selectedBlockId,
        setSelectedBlockId,
        editingBlockId,
        setEditingBlockId,
        isSaving,
        editingBlock,
        handleMoveUp,
        handleMoveDown,
        handleDeleteBlock,
        handleEditBlock,
        handleUpdateBlock,
        handleAddBlock,
        handleSave,
    };
}
