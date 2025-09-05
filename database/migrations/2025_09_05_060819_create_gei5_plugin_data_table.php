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
        Schema::create('plugin_data', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 255)->comment('插件标识符');
            $table->string('data_key', 255)->comment('数据键名');
            $table->longText('data_value')->nullable()->comment('数据值');
            $table->timestamps();
            
            $table->unique(['plugin_slug', 'data_key'], 'unique_plugin_key');
            $table->index(['plugin_slug']);
            
            // 外键约束
            $table->foreign('plugin_slug')->references('slug')->on('plugins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_data');
    }
};
