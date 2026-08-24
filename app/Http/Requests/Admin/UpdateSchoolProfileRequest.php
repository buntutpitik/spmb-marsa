<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'npsn' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'village' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'province' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'whatsapp' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'website' => [
                'nullable',
                'url',
                'max:150',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'Nama sekolah wajib diisi.',

            'name.max' =>
                'Nama sekolah maksimal 150 karakter.',

            'npsn.max' =>
                'NPSN maksimal 30 karakter.',

            'address.max' =>
                'Alamat maksimal 2000 karakter.',

            'postal_code.max' =>
                'Kode pos maksimal 10 karakter.',

            'phone.max' =>
                'Nomor telepon maksimal 30 karakter.',

            'whatsapp.max' =>
                'Nomor WhatsApp maksimal 30 karakter.',

            'email.email' =>
                'Format email tidak valid.',

            'email.max' =>
                'Email maksimal 150 karakter.',

            'website.url' =>
                'Format website tidak valid.',

            'website.max' =>
                'Website maksimal 150 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
                $this->filled('name')
                    ? trim(
                        (string) $this->input('name')
                    )
                    : null,

            'npsn' =>
                $this->filled('npsn')
                    ? trim(
                        (string) $this->input('npsn')
                    )
                    : null,

            'address' =>
                $this->filled('address')
                    ? trim(
                        (string) $this->input('address')
                    )
                    : null,

            'village' =>
                $this->filled('village')
                    ? trim(
                        (string) $this->input('village')
                    )
                    : null,

            'district' =>
                $this->filled('district')
                    ? trim(
                        (string) $this->input('district')
                    )
                    : null,

            'city' =>
                $this->filled('city')
                    ? trim(
                        (string) $this->input('city')
                    )
                    : null,

            'province' =>
                $this->filled('province')
                    ? trim(
                        (string) $this->input('province')
                    )
                    : null,

            'postal_code' =>
                $this->filled('postal_code')
                    ? trim(
                        (string) $this->input(
                            'postal_code'
                        )
                    )
                    : null,

            'phone' =>
                $this->filled('phone')
                    ? trim(
                        (string) $this->input('phone')
                    )
                    : null,

            'whatsapp' =>
                $this->filled('whatsapp')
                    ? trim(
                        (string) $this->input(
                            'whatsapp'
                        )
                    )
                    : null,

            'email' =>
                $this->filled('email')
                    ? strtolower(
                        trim(
                            (string) $this->input('email')
                        )
                    )
                    : null,

            'website' =>
                $this->filled('website')
                    ? trim(
                        (string) $this->input(
                            'website'
                        )
                    )
                    : null,
        ]);
    }
}