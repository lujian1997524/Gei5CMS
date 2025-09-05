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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 255)->unique()->comment('设置键名');
            $table->longText('setting_value')->nullable()->comment('设置值');
            $table->string('setting_group', 100)->default('general')->comment('设置分组');
            $table->enum('setting_type', ['string', 'integer', 'boolean', 'json', 'text'])->default('string')->comment('设置类型');
            $table->text('description')->nullable()->comment('设置描述');
            $table->boolean('is_autoload')->default(true)->comment('是否自动加载');
            $table->timestamps();
            
            $table->index(['setting_group']);
            $table->index(['is_autoload']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
