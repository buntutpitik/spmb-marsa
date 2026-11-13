<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePpdbPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:20',
            ],
            'year_start' => [
                'required',
                'integer',
                'between:2020,2100',
            ],
            'year_end' => [
                'required',
                'integer',
                'between:2021,2101',
                'gt:year_start',
            ],
            'registration_open' => [
                'nullable',
                'date',
            ],
            'registration_close' => [
                'nullable',
                'date',
                'after_or_equal:registration_open',
            ],
            'number_prefix' => [
                'required',
                'string',
                'max:30',
            ],
            'number_year' => [
                'required',
                'integer',
                'between:2020,2100',
            ],
            'number_digits' => [
                'required',
                'integer',
                'between:3,8',
            ],
            'include_major_code' => [
                'required',
                'boolean',
            ],
            'default_reenroll_fee' => [
                'required',
                'integer',
                'min:0',
            ],
            'copy_from_period_id' => [
                'nullable',
                'integer',
                Rule::exists('ppdb_periods', 'id')
                    ->whereNull('archived_at'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'number_prefix' => strtoupper(
                trim((string) $this->input('number_prefix'))
            ),
        ]);
    }
}
