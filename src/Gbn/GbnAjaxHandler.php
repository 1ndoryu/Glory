<?php

namespace Glory\Gbn;

use Glory\Gbn\Ajax\Registrar;

class GbnAjaxHandler
{
    public static function register(): void
    {
        Registrar::register();
    }
}
