<?php

namespace Glory\Manager;

/*
 * PageManager — Fachada pública de páginas gestionadas.
 *
 * Delega a:
 *   - PageDefinition: registro y almacenamiento de definiciones
 *   - PageTemplateInterceptor: interceptación de templates WP
 *   - PageSeoDefaults: valores SEO por defecto
 *   - PageProcessor: CRUD y helpers de contenido
 *   - PageReconciler: reconciliación de páginas obsoletas y front page
 *
 * Todos los métodos públicos se mantienen para retrocompatibilidad.
 * Refactorizado para cumplir SRP (max 300 líneas).
 */
class PageManager
{
    /* ── Fachada: delega a PageDefinition ── */

    public static function registerReactFullPages(array $slugs): void
    {
        PageDefinition::registerReactFullPages($slugs);
    }

    public static function isReactFullPage(string $slug): bool
    {
        return PageDefinition::isReactFullPage($slug);
    }

    public static function define(string $slug, ?string $handler = null, ?string $plantilla = null, array $roles = []): void
    {
        PageDefinition::define($slug, $handler, $plantilla, $roles);
    }

    public static function defineWithParent(string $parentSlug, string $slug, ?string $handler = null, array $roles = []): void
    {
        PageDefinition::defineWithParent($parentSlug, $slug, $handler, $roles);
    }

    public static function reactPage(string $slug, string $islandName, array|callable|null $props = null, array $roles = []): void
    {
        PageDefinition::reactPage($slug, $islandName, $props, $roles);
    }

    public static function renderReactIsland(): void
    {
        PageDefinition::renderReactIsland();
    }

    public static function getHandlerPorSlug(string $slug): ?string
    {
        return PageDefinition::getHandlerPorSlug($slug);
    }

    public static function getDefinicionPorSlug(string $slug): ?array
    {
        return PageDefinition::getDefinicionPorSlug($slug);
    }

    public static function setDefaultContentMode(string $mode): void
    {
        PageDefinition::setDefaultContentMode($mode);
    }

    public static function getDefaultContentMode(): string
    {
        return PageDefinition::getDefaultContentMode();
    }

    public static function getModoContenidoParaPagina(int $postId): string
    {
        return PageDefinition::getModoContenidoParaPagina($postId);
    }

    /* ── Fachada: delega a PageTemplateInterceptor ── */

    /**
     * Registra un slug como ruta dinámica (acepta segmentos hijos variables).
     * Ej: 'perfil' permite /perfil/{username}.
     */
    public static function registrarRutaDinamica(string $padreSlug): void
    {
        PageDefinition::registrarRutaDinamica($padreSlug);
    }

    public static function register(): void
    {
        PageTemplateInterceptor::register();
    }

    public static function getFuncionParaRenderizar(): mixed
    {
        return PageTemplateInterceptor::getFuncionParaRenderizar();
    }

    /* ── Fachada: delega a PageSeoDefaults ── */

    public static function setDefaultSeoMap(array $map): void
    {
        PageSeoDefaults::setDefaultSeoMap($map);
    }

    public static function getDefaultSeoForSlug(string $slug): array
    {
        return PageSeoDefaults::getDefaultSeoForSlug($slug);
    }

    /* ── Fachada: delega a PageProcessor (CRUD) ── */

    public static function procesarPaginasDefinidas(): void
    {
        PageProcessor::procesarPaginasDefinidas();
    }

    public static function renderHandlerParaCopiar(string $handler): string
    {
        return PageProcessor::renderHandlerParaCopiar($handler);
    }

    /* ── Fachada: delega a PageReconciler ── */

    public static function reconciliarPaginasGestionadas(): void
    {
        PageReconciler::reconciliarPaginasGestionadas();
    }
}
