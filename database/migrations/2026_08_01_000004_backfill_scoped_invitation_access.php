<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->scopedInvitationMemberships()->each(function (array $invitation): void {
            $hasCompanyInvitation = DB::table('invitations')
                ->where('company_id', $invitation['company_id'])
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation['email'])])
                ->whereNotNull('accepted_at')
                ->whereNull('workspace_id')
                ->whereNull('board_id')
                ->exists();

            if ($hasCompanyInvitation) {
                return;
            }

            DB::table('users')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation['email'])])
                ->pluck('id')
                ->each(function (mixed $userId) use ($invitation): void {
                    DB::table('company_user')
                        ->where('company_id', $invitation['company_id'])
                        ->where('user_id', $userId)
                        ->where('status', 'active')
                        ->update(['is_company_wide' => false]);
                });
        });
    }

    public function down(): void
    {
        $this->scopedInvitationMemberships()->each(function (array $invitation): void {
            $hasCompanyInvitation = DB::table('invitations')
                ->where('company_id', $invitation['company_id'])
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation['email'])])
                ->whereNotNull('accepted_at')
                ->whereNull('workspace_id')
                ->whereNull('board_id')
                ->exists();

            if ($hasCompanyInvitation) {
                return;
            }

            DB::table('users')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation['email'])])
                ->pluck('id')
                ->each(function (mixed $userId) use ($invitation): void {
                    DB::table('company_user')
                        ->where('company_id', $invitation['company_id'])
                        ->where('user_id', $userId)
                        ->where('status', 'active')
                        ->update(['is_company_wide' => true]);
                });
        });
    }

    /**
     * @return Collection<int, array{company_id: int, email: string}>
     */
    private function scopedInvitationMemberships(): Collection
    {
        return DB::table('invitations')
            ->whereNotNull('accepted_at')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('workspace_id')
                    ->orWhereNotNull('board_id');
            })
            ->select(['company_id', 'email'])
            ->distinct()
            ->get()
            ->map(static fn (object $invitation): array => [
                'company_id' => (int) data_get($invitation, 'company_id'),
                'email' => (string) data_get($invitation, 'email'),
            ]);
    }
};
