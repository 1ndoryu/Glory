<?php

namespace Glory\Gbn\Schema;

class SchemaConstants
{
    // === Layout & Visualization ===
    public const FIELD_LAYOUT = 'layout';
    /** @deprecated Use FIELD_LAYOUT instead */
    public const FIELD_DISPLAY_MODE = 'displayMode';

    // === Flexbox Properties ===
    public const FIELD_JUSTIFY = 'justifyContent';
    /** @deprecated Use FIELD_JUSTIFY instead */
    public const FIELD_FLEX_JUSTIFY = 'flexJustify';

    public const FIELD_ALIGN = 'alignItems';
    /** @deprecated Use FIELD_ALIGN instead */
    public const FIELD_FLEX_ALIGN = 'flexAlign';

    public const FIELD_FLEX_DIRECTION = 'direction';
    public const FIELD_FLEX_WRAP = 'wrap';
    
    // === Grid Properties ===
    public const FIELD_GRID_COLUMNS = 'gridColumns';
    public const FIELD_GRID_GAP = 'gridGap';

    // === Spacing ===
    public const FIELD_GAP = 'gap';

    // === Mapping for Migrations ===
    public const LEGACY_KEY_MAP = [
        'displayMode' => self::FIELD_LAYOUT,
        'flexJustify' => self::FIELD_JUSTIFY,
        'flexAlign'   => self::FIELD_ALIGN,
        'flexDirection' => self::FIELD_FLEX_DIRECTION,
        'flexWrap'    => self::FIELD_FLEX_WRAP,
    ];
}
