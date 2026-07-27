<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('companies', 'industry')) {
                $table->string('industry')->nullable()->after('website');
            }

            if (! Schema::hasColumn('companies', 'team_size')) {
                $table->string('team_size')->nullable()->after('industry');
            }

            if (! Schema::hasColumn('companies', 'address_line')) {
                $table->string('address_line')->nullable()->after('team_size');
            }

            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('address_line');
            }

            if (! Schema::hasColumn('companies', 'state')) {
                $table->string('state')->nullable()->after('city');
            }

            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country')->nullable()->after('state');
            }

            if (! Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }

            if (! Schema::hasColumn('companies', 'timezone')) {
                $table->string('timezone')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('companies', 'description')) {
                $table->text('description')->nullable()->after('timezone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            foreach ([
                'phone',
                'website',
                'industry',
                'team_size',
                'address_line',
                'city',
                'state',
                'country',
                'postal_code',
                'timezone',
                'description',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
