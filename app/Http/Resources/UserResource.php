<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'role_id' => $this->role_id,
            'permissions' => $this->permissionSlugs(),
            'company_membership' => $this->whenLoaded('activeCompanyMembership', fn () => $this->activeCompanyMembership ? [
                'id' => $this->activeCompanyMembership->id,
                'company_id' => $this->activeCompanyMembership->company_id,
                'role' => $this->activeCompanyMembership->role,
                'role_id' => $this->activeCompanyMembership->role_id,
                'status' => $this->activeCompanyMembership->status,
                'joined_at' => $this->activeCompanyMembership->joined_at?->toISOString(),
                'left_at' => $this->activeCompanyMembership->left_at?->toISOString(),
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'email' => $this->company->email,
                'phone' => $this->company->phone,
                'website' => $this->company->website,
                'industry' => $this->company->industry,
                'team_size' => $this->company->team_size,
                'address_line' => $this->company->address_line,
                'city' => $this->company->city,
                'state' => $this->company->state,
                'country' => $this->company->country,
                'postal_code' => $this->company->postal_code,
                'timezone' => $this->company->timezone,
                'description' => $this->company->description,
                'logo' => $this->company->logo,
                'trial_ends_at' => $this->company->trial_ends_at?->toISOString(),
                'created_at' => $this->company->created_at?->toISOString(),
                'updated_at' => $this->company->updated_at?->toISOString(),
            ]),
        ];
    }
}
