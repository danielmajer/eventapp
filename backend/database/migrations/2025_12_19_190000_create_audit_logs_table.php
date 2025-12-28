<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // e.g., 'create', 'update', 'delete', 'auth.login', 'auth.logout'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('resource_type')->nullable(); // e.g., 'events', 'users', 'helpdesk_chats'
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('metadata')->nullable(); // JSON
            $table->timestamp('created_at');

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('action');
            $table->index('resource_type');
            $table->index('created_at');
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

