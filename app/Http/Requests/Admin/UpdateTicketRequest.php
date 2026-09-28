<?php

namespace App\Http\Requests\Admin;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use App\Http\Requests\NormalizesBarcodeInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    use NormalizesBarcodeInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'regex:/^[A-Z0-9 ]{3,15}$/'],
            'vehicle_class' => ['required', Rule::enum(VehicleClass::class)],
            'weight_mode' => ['required', Rule::enum(WeightMode::class)],
            'weight_kg' => ['required', 'integer', 'min:1', 'max:200000'],
            'destination_port_name' => ['required', 'string', 'max:100'],
            'barcode_value' => ['nullable', 'string', 'max:255'],
            'barcode_format' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plate_number' => 'plat nomor',
            'vehicle_class' => 'golongan',
            'weight_mode' => 'mode berat',
            'weight_kg' => 'berat',
            'destination_port_name' => 'pelabuhan tujuan',
            'barcode_value' => 'nilai barcode',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate_number.regex' => 'Plat nomor hanya boleh huruf, angka, dan spasi. Contoh: L 1234 XY.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'plate_number' => Str::of((string) $this->input('plate_number'))->upper()->squish()->toString(),
            'destination_port_name' => Str::of((string) $this->input('destination_port_name'))->upper()->squish()->toString(),
        ]);

        $this->normalizeBarcodeInput();
    }
}
