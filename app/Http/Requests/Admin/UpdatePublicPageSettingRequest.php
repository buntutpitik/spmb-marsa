<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicPageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hero_title' => [
                'nullable',
                'string',
                'max:200',
            ],

            'hero_subtitle' => [
                'nullable',
                'string',
                'max:255',
            ],

            'hero_description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'announcement_title' => [
                'nullable',
                'string',
                'max:200',
            ],

            'announcement_body' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'requirements' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'registration_steps' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'reenrollment_information' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'show_announcement' => [
                'required',
                'boolean',
            ],

            'show_requirements' => [
                'required',
                'boolean',
            ],

            'show_registration_steps' => [
                'required',
                'boolean',
            ],

            'show_reenrollment_information' => [
                'required',
                'boolean',
            ],

            'show_contact' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hero_title.max' =>
                'Judul utama maksimal 200 karakter.',

            'hero_subtitle.max' =>
                'Subjudul maksimal 255 karakter.',

            'hero_description.max' =>
                'Deskripsi utama maksimal 2000 karakter.',

            'announcement_title.max' =>
                'Judul pengumuman maksimal 200 karakter.',

            'announcement_body.max' =>
                'Isi pengumuman maksimal 5000 karakter.',

            'requirements.max' =>
                'Informasi persyaratan maksimal 10000 karakter.',

            'registration_steps.max' =>
                'Informasi cara mendaftar maksimal 10000 karakter.',

            'reenrollment_information.max' =>
                'Informasi daftar ulang maksimal 10000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $textFields = [
            'hero_title',
            'hero_subtitle',
            'hero_description',
            'announcement_title',
            'announcement_body',
            'requirements',
            'registration_steps',
            'reenrollment_information',
        ];

        $normalized = [];

        foreach ($textFields as $field) {
            $normalized[$field] = $this->filled($field)
                ? trim((string) $this->input($field))
                : null;
        }

        $normalized['show_announcement'] =
            $this->boolean('show_announcement');

        $normalized['show_requirements'] =
            $this->boolean('show_requirements');

        $normalized['show_registration_steps'] =
            $this->boolean('show_registration_steps');

        $normalized['show_reenrollment_information'] =
            $this->boolean('show_reenrollment_information');

        $normalized['show_contact'] =
            $this->boolean('show_contact');

        $this->merge($normalized);
    }
}