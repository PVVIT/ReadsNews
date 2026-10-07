<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->foreignId('default_voice_id')->nullable()->constrained('voices')->nullOnDelete();
            $table->decimal('default_speed', 3, 2)->default(1.00);
            $table->boolean('autoplay_next')->default(true);
            $table->boolean('voice_control_on')->default(false);
            $table->enum('theme', ['light', 'dark', 'system'])->default('system');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};

