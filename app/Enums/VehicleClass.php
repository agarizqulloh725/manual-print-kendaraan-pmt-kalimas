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

    /**
     * Estimated weight in tonnes.
     */
    public function defaultWeightTon(): float
    {
        return match ($this) {
            self::I => 0.1,
            self::II => 0.2,
            self::III => 0.35,
            self::IVA => 2.0,
            self::IVB => 4.0,
            self::VA => 8.0,
            self::VB => 12.0,
            self::VIA => 16.0,
            self::VIB => 20.0,
            self::VII => 30.0,
            self::VIII => 40.0,
            self::IX => 50.0,
        };
    }
}
