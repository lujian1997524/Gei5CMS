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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_slug', 100)->unique(); // 角色标识符，如 'blog_author', 'premium_member'
            $table->string('role_name', 255); // 角色名称
            $table->text('role_description')->nullable(); // 角色描述
            $table->json('permissions')->nullable(); // 角色权限（JSON格式）
            $table->string('theme_slug', 100)->nullable(); // 所属主题
            $table->boolean('is_active')->default(true); // 是否激活
            $table->integer('priority')->default(0); // 优先级（用于角色层级）
            $table->timestamps();
            
            // 索引
            $table->index(['theme_slug', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
