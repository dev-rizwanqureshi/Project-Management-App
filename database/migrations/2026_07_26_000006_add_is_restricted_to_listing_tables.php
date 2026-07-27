<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['companies', 'users', 'workspaces', 'boards', 'cards'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('is_restricted')->default(false)->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['companies', 'users', 'workspaces', 'boards', 'cards'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_is_restricted_index");
                $table->dropColumn('is_restricted');
            });
        }
    }
};
