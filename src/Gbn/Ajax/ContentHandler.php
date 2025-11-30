<?php

namespace Glory\Gbn\Ajax;

use Glory\Gbn\Ajax\Handlers\OptionsHandler;
use Glory\Gbn\Ajax\Handlers\PreviewHandler;
use Glory\Gbn\Ajax\Handlers\ConfigHandler;
use Glory\Gbn\Logger;

class ContentHandler
{
    public static function saveOptions(): void
    {
        OptionsHandler::saveOptions();
    }

    public static function previewBlock(): void
    {
        PreviewHandler::previewBlock();
    }

    public static function saveConfig(): void
    {
        ConfigHandler::saveConfig();
    }

    public static function restorePage(): void
    {
        ConfigHandler::restorePage();
    }

    /**
     * @deprecated Use Logger::log instead
     */
    private static function log(string $msg, $context = []): void
    {
        Logger::log($msg, $context);
    }
}


