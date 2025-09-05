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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique()->comment('插件标识符');
            $table->string('name', 255)->comment('插件名称');
            $table->text('description')->nullable()->comment('插件描述');
            $table->string('version', 20)->comment('插件版本');
            $table->string('author', 255)->nullable()->comment('插件作者');
            $table->string('author_email', 255)->nullable()->comment('作者邮箱');
            $table->string('website', 255)->nullable()->comment('插件官网');
            $table->string('requires_php_version', 20)->nullable()->comment('要求PHP版本');
            $table->string('requires_cms_version', 20)->nullable()->comment('要求CMS版本');
            $table->enum('status', ['active', 'inactive', 'broken'])->default('inactive')->comment('插件状态');
            $table->boolean('auto_update')->default(false)->comment('自动更新');
            $table->json('config')->nullable()->comment('插件配置');
            $table->timestamp('installed_at')->nullable()->comment('安装时间');
            $table->timestamps();
            
            $table->index(['status']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
