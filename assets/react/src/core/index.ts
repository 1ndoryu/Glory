/*
 * Barrel export del modulo core de Glory.
 * Importar desde '@/core' para acceso centralizado.
 */

export { islandRegistry } from './IslandRegistry';
export type { IslandComponent, IslandLoader, ResolvedIsland } from './IslandRegistry';
export { IslandErrorBoundary } from './ErrorBoundary';
export { GloryProvider, useGloryProvider } from './GloryProvider';
export type { GloryProviderValue } from './GloryProvider';
export { initializeIslands } from './hydration';
export type { InitOptions } from './hydration';
export { DevOverlay } from './DevOverlay';
