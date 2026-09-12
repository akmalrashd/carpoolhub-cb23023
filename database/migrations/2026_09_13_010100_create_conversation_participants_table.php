<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * left_at is set, not row-deleted, when a passenger cancels/is removed —
     * that keeps a "X left the trip" system message and the rest of the
     * group's read history coherent instead of silently erasing who used to
     * be here. Message-send access is denied once left_at is set; read
     * access continues until the conversation itself is purged.
     */
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_chat_admin')->default(false);

            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->foreignId('last_read_message_id')->nullable();

            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
