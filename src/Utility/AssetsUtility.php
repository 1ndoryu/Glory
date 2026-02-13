<?php

namespace Glory\Utility;

/**
 * Fachada retrocompatible para assets del tema.
 * Delega todas las llamadas a AssetResolver, AssetImporter y AssetLister
 * para mantener la interfaz pública AssetsUtility::metodo() sin romper llamadas existentes.
 */
class AssetsUtility
{
    /* --- AssetResolver: inicialización y resolución de rutas --- */

    public static function init(): void
    {
        AssetResolver::init();
    }

    public static function registerAssetPath(string $alias, string $path): void
    {
        AssetResolver::registerAssetPath($alias, $path);
    }

    public static function parseAssetReference(string $reference): array
    {
        return AssetResolver::parseAssetReference($reference);
    }

    public static function assetExists(string $assetReference): bool
    {
        return AssetResolver::assetExists($assetReference);
    }

    public static function findExistingAttachmentIdForAsset(string $assetReference): ?int
    {
        return AssetResolver::findExistingAttachmentIdForAsset($assetReference);
    }


    /* --- AssetImporter: importación a Biblioteca de Medios --- */

    public static function get_attachment_id_from_asset(string $assetReference, bool $allowAliasFallback = true): ?int
    {
        return AssetImporter::get_attachment_id_from_asset($assetReference, $allowAliasFallback);
    }

    public static function importTemaAssets(): void
    {
        AssetImporter::importTemaAssets();
    }

    public static function importAssetsForAlias(string $alias): void
    {
        AssetImporter::importAssetsForAlias($alias);
    }


    /* --- AssetLister: listado, selección aleatoria y renderizado --- */

    public static function getRandomDefaultImageName(string $alias = 'glory'): ?string
    {
        return AssetLister::getRandomDefaultImageName($alias);
    }

    public static function getRandomUniqueImagesFromAlias(
        string $alias,
        int $cantidad,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        return AssetLister::getRandomUniqueImagesFromAlias($alias, $cantidad, $extensiones);
    }

    public static function listImagesForAlias(
        string $alias,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
    ): array {
        return AssetLister::listImagesForAlias($alias, $extensiones);
    }

    public static function listImagesForAliasWithMinSize(
        string $alias,
        int $minBytes = 0,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        return AssetLister::listImagesForAliasWithMinSize($alias, $minBytes, $extensiones);
    }

    public static function pickRandomImages(
        string $alias,
        int $cantidad,
        int $minBytes = 0,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        return AssetLister::pickRandomImages($alias, $cantidad, $minBytes, $extensiones);
    }

    public static function imagen(string $assetReference, array $atributos = []): void
    {
        AssetLister::imagen($assetReference, $atributos);
    }

    public static function imagenUrl(string $assetReference): ?string
    {
        return AssetLister::imagenUrl($assetReference);
    }
}
