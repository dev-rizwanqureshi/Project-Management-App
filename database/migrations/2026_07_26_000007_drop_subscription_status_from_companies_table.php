<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'subscription_status')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('subscription_status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'subscription_status')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->enum('subscription_status', ['trial', 'active', 'suspended', 'cancelled'])->default('trial')->after('logo');
        });
    }
};
