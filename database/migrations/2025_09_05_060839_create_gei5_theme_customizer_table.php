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
        Schema::create('theme_customizer', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug', 255)->comment('主题标识符');
            $table->string('setting_key', 255)->comment('设置键名');
            $table->longText('setting_value')->nullable()->comment('设置值');
            $table->timestamps();
            
            $table->unique(['theme_slug', 'setting_key'], 'unique_theme_setting');
            $table->index(['theme_slug']);
            
            // 外键约束
            $table->foreign('theme_slug')->references('slug')->on('themes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_customizer');
    }
};
