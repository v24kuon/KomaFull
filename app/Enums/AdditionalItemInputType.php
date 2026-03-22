<?php

namespace App\Enums;

enum AdditionalItemInputType: string
{
    case Text = 'text';

    case Number = 'number';

    case Select = 'select';

    case Checkbox = 'checkbox';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
