<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title', 500);
            $table->string('slug', 520)->unique();
            $table->text('summary')->nullable();
            $table->text('ai_summary')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_tts')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('original_url', 700);
            $table->char('url_hash', 64)->unique();
            $table->string('author', 150)->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('listen_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'published_at'], 'idx_category_time');
            $table->index('published_at', 'idx_published');
            $table->fullText(['title', 'summary'], 'ft_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

