<?php

namespace App\Enums;

enum WeightMode: string
{
    case Manual = 'manual';
    case Automatic = 'otomatis';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automatic => 'Otomatis',
        };
    }
}
