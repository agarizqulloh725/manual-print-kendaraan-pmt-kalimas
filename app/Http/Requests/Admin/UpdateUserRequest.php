<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^08\d{8,12}$/', Rule::unique('users', 'phone')->ignore($this->route('user'))],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ];
    }

    /**
     * An administrator may not lock themselves out.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->route('user')->is($this->user())) {
                    return;
                }

                if ($this->input('role') !== UserRole::Admin->value) {
                    $validator->errors()->add('role', 'Anda tidak bisa menurunkan peran akun Anda sendiri.');
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor HP',
            'role' => 'peran',
            'is_active' => 'status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP tidak valid. Contoh: 081234567890.',
            'phone.unique' => 'Nomor HP sudah terdaftar.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => User::normalizePhone($this->input('phone')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
