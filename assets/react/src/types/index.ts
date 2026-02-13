/*
 * Barrel export de todos los tipos del framework Glory.
 * Importar desde '@/types' para acceso centralizado.
 */

export type {
    WPMedia,
    WPTerm,
    WPCategory,
    WPTag,
    WPUser,
    WPPost,
    WPPage,
    WPMenuItem,
    WPMenu,
    WPTaxonomy,
    WPPagination,
    WPRestResponse,
    WPError,
} from './wordpress';

export type {
    GloryContentMap,
    GloryContext,
    GloryIslandBaseProps,
    GloryPageConfig,
    GloryOption,
    IslandRegistry,
} from './glory';

export type {
    ImageListResponse,
    ImageUrlResponse,
    RandomImageResponse,
    ImageAliasesResponse,
    PageBlock,
    PageBlocksResponse,
    NewsletterSubscribeRequest,
    NewsletterSubscribeResponse,
    MCPTokenResponse,
    MCPConfigResponse,
    GloryApiResponse,
    ApiRequestOptions,
} from './api';

export type {
    BlockDefinition,
    BlockField,
    BlockInstance,
    PageLayout,
} from './pageBuilder';
