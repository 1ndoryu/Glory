/*
 * Barrel export del modulo core de Glory.
 * Importar desde '@/core' para acceso centralizado.
 */

export { islandRegistry } from './IslandRegistry';
export type { IslandComponent, IslandLoader, ResolvedIsland } from './IslandRegistry';
export { IslandErrorBoundary } from './ErrorBoundary';
export { GloryProvider } from './GloryProvider';
export { useGloryProvider } from './useGloryProvider';
export type { GloryProviderValue } from './gloryContext';
export { initializeIslands } from './hydration';
export type { InitOptions } from './hydration';
export { DevOverlay } from './DevOverlay';
