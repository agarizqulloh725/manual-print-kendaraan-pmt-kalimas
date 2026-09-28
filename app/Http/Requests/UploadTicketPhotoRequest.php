<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadTicketPhotoRequest extends FormRequest
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
            'vehicle_photo' => 'foto kendaraan',
            'ticket_photo' => 'foto tiket',
            'barcode_image' => 'barcode',
            'barcode_value' => 'nilai barcode',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBarcodeInput();
    }
}
