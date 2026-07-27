<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->renameRole('super_admin', 'admin', 'Admin');
        $this->renameRole('support', 'support_staff', 'Support Staff');

        DB::table('admins')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::table('admins')->where('role', 'support')->update(['role' => 'support_staff']);

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE admins MODIFY role VARCHAR(100) NOT NULL DEFAULT 'support_staff'");
        } else {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('role')->default('support_staff')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->renameRole('admin', 'super_admin', 'Super Admin');
        $this->renameRole('support_staff', 'support', 'Support');

        DB::table('admins')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('admins')->where('role', 'support_staff')->update(['role' => 'support']);

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE admins MODIFY role VARCHAR(100) NOT NULL DEFAULT 'support'");
        } else {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('role')->default('support')->change();
            });
        }
    }

    private function renameRole(string $fromSlug, string $toSlug, string $name): void
    {
        $from = DB::table('admin_roles')->where('slug', $fromSlug)->first();
        $to = DB::table('admin_roles')->where('slug', $toSlug)->first();

        if (! $from) {
            return;
        }

        if (! $to) {
            DB::table('admin_roles')
                ->where('id', $from->id)
                ->update([
                    'name' => $name,
                    'slug' => $toSlug,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('admins')
            ->where('admin_role_id', $from->id)
            ->update(['admin_role_id' => $to->id]);

        $permissionIds = DB::table('admin_permission_role')
            ->where('admin_role_id', $from->id)
            ->pluck('admin_permission_id');

        foreach ($permissionIds as $permissionId) {
            DB::table('admin_permission_role')->updateOrInsert(
                [
                    'admin_role_id' => $to->id,
                    'admin_permission_id' => $permissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('admin_roles')->where('id', $from->id)->delete();
    }
};
