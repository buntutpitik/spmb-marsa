<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateAccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if (! $user) {
                    return;
                }

                $currentPassword = (string) $this->input(
                    'current_password'
                );

                $newPassword = (string) $this->input(
                    'password'
                );

                if (
                    $currentPassword !== ''
                    && ! Hash::check(
                        $currentPassword,
                        $user->password
                    )
                ) {
                    $validator->errors()->add(
                        'current_password',
                        'Password saat ini tidak benar.'
                    );
                }

                if (
                    $currentPassword !== ''
                    && $newPassword !== ''
                    && Hash::check(
                        $newPassword,
                        $user->password
                    )
                ) {
                    $validator->errors()->add(
                        'password',
                        'Password baru harus berbeda dari password saat ini.'
                    );
                }
            },
        ];
    }
}