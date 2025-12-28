<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('helpdesk_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helpdesk_chat_id')->constrained('helpdesk_chats')->onDelete('cascade');
            $table->string('sender_type'); // user, agent, bot
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_messages');
    }
};


