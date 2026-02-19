<?php

/**
 * AssetMeta — Constantes centralizadas para meta keys de assets importados.
 *
 * Evita duplicación de strings '_glory_asset_source' y '_glory_asset_requested'
 * en AssetImporter, AssetResolver, PostSyncHandler, GalleryRepair y FeaturedImageRepair.
 *
 * @package Glory\Utility
 */

namespace Glory\Utility;

final class AssetMeta
{
    /* Meta key que almacena la ruta real resuelta del asset en el tema */
    const SOURCE = '_glory_asset_source';

    /* Meta key que almacena la referencia original solicitada (puede incluir alias) */
    const REQUESTED = '_glory_asset_requested';
}
