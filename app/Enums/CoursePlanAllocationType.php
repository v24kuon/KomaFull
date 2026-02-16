<?php

namespace App\Enums;

enum CoursePlanAllocationType: string
{
    case Total = 'total';

    case PerCategory = 'per_category';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
