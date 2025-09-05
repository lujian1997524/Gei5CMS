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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('permission_name', 255)->unique()->comment('权限名称');
            $table->string('permission_slug', 255)->unique()->comment('权限标识符');
            $table->text('description')->nullable()->comment('权限描述');
            $table->string('group_name', 100)->default('general')->comment('权限分组');
            $table->boolean('is_system')->default(false)->comment('是否系统权限');
            $table->timestamps();
            
            $table->index(['group_name']);
            $table->index(['is_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
