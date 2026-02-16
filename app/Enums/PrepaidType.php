<?php

namespace App\Enums;

enum PrepaidType: string
{
    case Tickets = 'tickets';

    case Points = 'points';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
