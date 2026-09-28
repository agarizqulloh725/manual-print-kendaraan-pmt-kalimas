<?php

namespace App\Enums;

/**
 * RoRo vehicle classes (golongan kendaraan) with a default weight estimate
 * used when the operator chooses the "Otomatis" weight mode.
 */
enum VehicleClass: string
{
    case I = 'I';
    case II = 'II';
    case III = 'III';
    case IVA = 'IVA';
    case IVB = 'IVB';
    case VA = 'VA';
    case VB = 'VB';
    case VIA = 'VIA';
    case VIB = 'VIB';
    case VII = 'VII';
    case VIII = 'VIII';
    case IX = 'IX';

    public function label(): string
    {
        return match ($this) {
            self::I => 'Golongan I - Sepeda',
            self::II => 'Golongan II - Sepeda Motor < 500cc',
            self::III => 'Golongan III - Sepeda Motor > 500cc',
            self::IVA => 'Golongan IVA - Mobil Penumpang',
            self::IVB => 'Golongan IVB - Mobil Barang / Pick Up',
            self::VA => 'Golongan VA - Bus Sedang',
            self::VB => 'Golongan VB - Truk Sedang',
            self::VIA => 'Golongan VIA - Bus Besar',
            self::VIB => 'Golongan VIB - Truk Besar',
            self::VII => 'Golongan VII - Truk Tronton (10-12 m)',
            self::VIII => 'Golongan VIII - Truk Tronton (12-16 m)',
            self::IX => 'Golongan IX - Kendaraan > 16 m',
        };
    }

    public function defaultWeightKg(): int
    {
        return match ($this) {
            self::I => 100,
            self::II => 200,
            self::III => 350,
            self::IVA => 2000,
            self::IVB => 4000,
            self::VA => 8000,
            self::VB => 12000,
            self::VIA => 16000,
            self::VIB => 20000,
            self::VII => 30000,
            self::VIII => 40000,
            self::IX => 50000,
        };
    }
}
