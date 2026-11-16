<?php

namespace App\Http\Requests\Concerns;

trait CopiesParentAddress
{
    protected function copyParentAddressWhenRequested(): void
    {
        if (! $this->boolean('same_parent_address')) {
            return;
        }

        $fields = [
            'hamlet' => 'parent_hamlet',
            'rt' => 'parent_rt',
            'rw' => 'parent_rw',
            'village' => 'parent_village',
            'district' => 'parent_district',
            'city' => 'parent_city',
            'province' => 'parent_province',
            'postal_code' => 'parent_postal_code',
        ];

        $values = [];

        foreach ($fields as $student => $parent) {
            $values[$parent] = $this->input($student);
        }

        $this->merge($values);
    }
}
