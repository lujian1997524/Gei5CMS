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
        Schema::create('user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('user_roles')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $adminUsersTable = config('database.connections.mysql.prefix', '') . 'admin_users';
            $table->foreignId('assigned_by')->nullable()->constrained($adminUsersTable)->onDelete('set null'); // 分配者
            $table->timestamp('expires_at')->nullable(); // 角色过期时间（可选）
            $table->json('role_meta')->nullable(); // 角色相关的额外数据
            $table->timestamps();
            
            // 唯一约束：每个用户每个角色只能有一条记录
            $table->unique(['user_id', 'role_id']);
            
            // 索引
            $table->index(['user_id', 'expires_at']);
            $table->index('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role_assignments');
    }
};
