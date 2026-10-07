<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('voice_id')->constrained('voices')->cascadeOnDelete();
            $table->decimal('speed', 3, 2)->default(1.00);
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('characters_count')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'voice_id', 'speed'], 'uq_article_voice_speed');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};

