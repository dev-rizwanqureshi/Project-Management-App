<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('board_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->string('email');
            $table->string('role', 30);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'email', 'accepted_at']);
            $table->index(['workspace_id', 'email', 'accepted_at']);
            $table->index(['board_id', 'email', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
