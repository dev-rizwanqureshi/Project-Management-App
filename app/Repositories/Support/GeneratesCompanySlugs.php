<?php

namespace App\Repositories\Support;

use App\Models\Company;
use Illuminate\Support\Str;

trait GeneratesCompanySlugs
{
    protected function uniqueCompanySlug(string $companyName): string
    {
        $baseSlug = Str::slug($companyName) ?: 'company';
        $slug = $baseSlug;
        $counter = 2;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
