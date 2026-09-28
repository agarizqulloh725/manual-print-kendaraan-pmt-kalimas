<?php

namespace App\Http\Requests;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    use NormalizesBarcodeInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'voyage_no' => ['required', 'string', 'max:50'],
            'vessel_code' => ['nullable', 'string', 'max:50'],
            'vessel_name' => ['required', 'string', 'max:100'],
            'operator_name' => ['nullable', 'string', 'max:150'],
            'destination_port_code' => ['nullable', 'string', 'max:20'],
            'destination_port_name' => ['required', 'string', 'max:100'],
            'berth_name' => ['nullable', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'regex:/^[A-Z0-9 ]{3,15}$/'],
            'vehicle_class' => ['required', Rule::enum(VehicleClass::class)],
            'weight_mode' => ['required', Rule::enum(WeightMode::class)],
            'weight_kg' => [Rule::requiredIf($this->input('weight_mode') === WeightMode::Manual->value), 'nullable', 'integer', 'min:1', 'max:200000'],
            'vehicle_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'ticket_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'barcode_image' => ['nullable', 'image', 'mimes:png', 'max:2048'],
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
            'voyage_no' => 'kapal',
            'vessel_name' => 'kapal',
            'destination_port_name' => 'pelabuhan tujuan',
            'plate_number' => 'plat nomor',
            'vehicle_class' => 'golongan',
            'weight_mode' => 'mode berat',
            'weight_kg' => 'berat',
            'vehicle_photo' => 'foto kendaraan',
            'ticket_photo' => 'foto tiket',
            'barcode_image' => 'barcode',
            'barcode_value' => 'nilai barcode',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voyage_no.required' => 'Pilih kapal yang sedang beroperasi.',
            'plate_number.regex' => 'Plat nomor hanya boleh huruf, angka, dan spasi. Contoh: L 1234 XY.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'plate_number' => Str::of((string) $this->input('plate_number'))->upper()->squish()->toString(),
        ]);

        $this->normalizeBarcodeInput();
    }

    /**
     * The final weight in kilograms, falling back to the class estimate in automatic mode.
     */
    public function weightKg(): int
    {
        if ($this->enum('weight_mode', WeightMode::class) === WeightMode::Automatic) {
            return $this->enum('vehicle_class', VehicleClass::class)->defaultWeightKg();
        }

        return $this->integer('weight_kg');
    }
}
