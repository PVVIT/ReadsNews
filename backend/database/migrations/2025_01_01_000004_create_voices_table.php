<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voices', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('code', 100);
            $table->string('name', 100);
            $table->string('language', 10)->default('vi-VN');
            $table->enum('gender', ['male', 'female', 'neutral'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'code'], 'uq_provider_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voices');
    }
};

