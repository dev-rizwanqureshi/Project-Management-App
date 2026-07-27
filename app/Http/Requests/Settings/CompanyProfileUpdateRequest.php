<?php

namespace App\Http\Requests\Settings;

use App\Concerns\CompanyProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class CompanyProfileUpdateRequest extends FormRequest
{
    use CompanyProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return $this->companyProfileRules();
    }
}
