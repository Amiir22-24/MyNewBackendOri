<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: messages
     * Artisan: php artisan make:migration create_messages_table
     * Relations: N:1 conversation_id/sender_id; polymorphic reply_to
     * FKs: conversation_id constrained('conversations'), sender_id ('users')
     * Indexes: conversation_id + created_at, sender_id
     * Constraints: json attachments nullable
     */

    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->string('reply_to_type')->nullable();
            $table->json('attachments')->nullable(); // photos/files
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
            $table->index(['reply_to_type', 'reply_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
