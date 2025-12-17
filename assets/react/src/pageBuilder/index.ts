/**
 * Glory Page Builder - Exportaciones Públicas
 *
 * Este archivo exporta todas las herramientas del Page Builder
 * para ser consumidas por los proyectos.
 *
 * Importar desde App:
 * ```
 * import { PageBuilder, BlockRegistry, ... } from '@/pageBuilder';
 * ```
 *
 * USO SIMPLE:
 * ```tsx
 * <PageBuilder
 *     blocks={blocksFromPhp}
 *     isAdmin={isAdmin}
 *     saveEndpoint={saveEndpoint}
 *     restNonce={restNonce}
 * />
 * ```
 */

// Tipos
export type {BlockData, PageData, BlockComponentProps, BlockDefinition, EditableField, FieldType, PageBuilderContextType} from './types';

// Registro de bloques
export {BlockRegistry} from './BlockRegistry';

// Renderizador de bloques
export {BlockRenderer} from './BlockRenderer';

// Modal de edicion de bloques
export {BlockEditorModal} from './BlockEditorModal';

// Componente principal y subcomponentes
export {PageBuilder, PageBuilderToolbar, EditModeToggle, AddBlockPanel} from './components';
export type {PageBuilderProps} from './components';

// Layouts
export {PageLayout} from './layouts';
export type {PageLayoutProps, NavLink, SocialLink} from './layouts';
