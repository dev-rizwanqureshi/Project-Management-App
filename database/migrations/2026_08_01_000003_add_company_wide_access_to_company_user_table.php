<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_user', function (Blueprint $table): void {
            $table->boolean('is_company_wide')->default(true)->after('status');
            $table->index(['company_id', 'user_id', 'status', 'is_company_wide']);
        });
    }

    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table): void {
            $table->dropIndex('company_user_company_id_user_id_status_is_company_wide_index');
            $table->dropColumn('is_company_wide');
        });
    }
};
