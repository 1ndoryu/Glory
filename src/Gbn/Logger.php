<?php

namespace Glory\Gbn;

class Logger
{
    public static function log(string $msg, $context = []): void
    {
        $file = __DIR__ . '/gbn.log';
        $date = date('Y-m-d H:i:s.u');
        $contextStr = !empty($context) ? ' | Contexto: ' . print_r($context, true) : '';
        $line = "[$date] [INFO] [GBN] $msg$contextStr\n";
        file_put_contents($file, $line, FILE_APPEND);
    }
}
