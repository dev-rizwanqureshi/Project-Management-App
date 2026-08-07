<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'users.manage')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $adminRoleIds = DB::table('roles')
            ->where('slug', 'admin')
            ->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('slug', 'users.manage')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('permission_role')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', DB::table('roles')->where('slug', 'admin')->select('id'))
            ->delete();
    }
};
