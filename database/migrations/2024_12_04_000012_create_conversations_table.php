<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: conversations
     * Artisan: php artisan make:migration create_conversations_table
     * Relations: 1:N messages; N:M users via conversation_participants
     * FKs: N/A
     * Indexes: subject, created_at
     * Constraints: unique subject optional, timestamps
     */

    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->enum('type', ['direct', 'group', 'support'])->default('direct');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
