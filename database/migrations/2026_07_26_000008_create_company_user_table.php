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
        $this->makeUserCompanyFieldsFlexible();

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'status']);
        });

        $this->copyCurrentUsersToCompanyMemberships();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }

    private function makeUserCompanyFieldsFlexible(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(100) NOT NULL DEFAULT 'member'");
            DB::statement("ALTER TABLE workspace_user MODIFY role VARCHAR(100) NOT NULL DEFAULT 'member'");
            DB::statement("ALTER TABLE board_user MODIFY role VARCHAR(100) NOT NULL DEFAULT 'member'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });

        Schema::table('workspace_user', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });

        Schema::table('board_user', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });
    }

    private function copyCurrentUsersToCompanyMemberships(): void
    {
        DB::table('users')
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->get(['id', 'company_id', 'role', 'role_id', 'created_at', 'updated_at'])
            ->each(function (object $user): void {
                DB::table('company_user')->updateOrInsert(
                    [
                        'company_id' => $user->company_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => $user->role ?? 'member',
                        'role_id' => $user->role_id,
                        'status' => 'active',
                        'joined_at' => $user->created_at,
                        'left_at' => null,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ],
                );
            });
    }
};
