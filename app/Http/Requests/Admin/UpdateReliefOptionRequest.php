<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReliefOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reliefOption = $this->route('reliefOption');

        return [
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('relief_options', 'name')
                    ->ignore($reliefOption?->id),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }
}