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
        Schema::create('hooks', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 255)->comment('钩子标签');
            $table->string('callback', 255)->comment('回调函数');
            $table->integer('priority')->default(10)->comment('执行优先级');
            $table->string('plugin_slug', 255)->nullable()->comment('所属插件');
            $table->string('hook_type', 50)->default('action')->comment('钩子类型: action, filter');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->timestamps();
            
            $table->index(['tag']);
            $table->index(['priority']);
            $table->index(['plugin_slug']);
            $table->index(['hook_type']);
            
            // 外键约束
            $table->foreign('plugin_slug')->references('slug')->on('plugins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hooks');
    }
};
