<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('audio_file_id')->nullable()->constrained('audio_files')->nullOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_listened_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'article_id'], 'uq_user_article');
            $table->index(['user_id', 'last_listened_at'], 'idx_recent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_history');
    }
};

