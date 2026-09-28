<?php

namespace App\Http\Requests;

/**
 * A barcode format only makes sense alongside a value; drop it when the value was cleared.
 */
trait NormalizesBarcodeInput
{
    protected function normalizeBarcodeInput(): void
    {
        if (! $this->has('barcode_value')) {
            return;
        }

        if (blank($this->input('barcode_value'))) {
            $this->merge(['barcode_value' => null, 'barcode_format' => null]);

            return;
        }

        $this->merge(['barcode_format' => $this->input('barcode_format') ?: null]);
    }
}
