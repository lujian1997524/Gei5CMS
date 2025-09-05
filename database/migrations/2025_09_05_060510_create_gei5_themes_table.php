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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique()->comment('主题标识符');
            $table->string('name', 255)->comment('主题名称');
            $table->text('description')->nullable()->comment('主题描述');
            $table->string('version', 20)->comment('主题版本');
            $table->string('author', 255)->nullable()->comment('主题作者');
            $table->string('website', 255)->nullable()->comment('主题官网');
            $table->string('screenshot', 255)->nullable()->comment('主题截图');
            $table->string('application_type', 100)->default('universal')->comment('应用类型: blog,ecommerce,forum等');
            $table->boolean('is_active')->default(false)->comment('是否激活');
            $table->json('config')->nullable()->comment('主题配置');
            $table->json('table_schema')->nullable()->comment('主题业务表结构');
            $table->timestamp('installed_at')->nullable()->comment('安装时间');
            $table->timestamps();
            
            $table->index(['is_active']);
            $table->index(['application_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
