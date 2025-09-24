<?php

namespace Glory\Integration\Avada\Options;

final class Normalizer
{
    public static function normalizeOptionValueForRead(string $id, $value)
    {
        if (self::isBooleanOption($id)) {
            if (is_bool($value)) {
                return $value;
            }
            if (is_int($value)) {
                return $value === 1;
            }
            if (is_string($value)) {
                $v = strtolower(trim($value));
                if ($v === '1' || $v === 'true' || $v === 'on' || $v === 'yes') { return true; }
                if ($v === '0' || $v === 'false' || $v === 'off' || $v === 'no' || $v === '') { return false; }
            }
            if (is_null($value)) { return false; }
            if (is_array($value)) { return !empty($value); }
        }
        return $value;
    }

    public static function normalizeOptionValueForWrite(string $id, $value)
    {
        if (self::isBooleanOption($id)) {
            $read = self::normalizeOptionValueForRead($id, $value);
            return $read ? 1 : 0;
        }
        return $value;
    }

    public static function isBooleanOption(string $id): bool
    {
        if (substr($id, -9) === '_activado') { return true; }
        $tipo = Discovery::getOptionType($id);
        if ($tipo && in_array(strtolower($tipo), ['toggle','checkbox','switch'], true)) {
            return true;
        }
        return false;
    }
}


