<?php

namespace App\Concerns;

trait CompanyProfileValidationRules
{
    /**
     * @return array<string, list<string>>
     */
    protected function companyProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'team_size' => ['nullable', 'string', 'max:120'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
