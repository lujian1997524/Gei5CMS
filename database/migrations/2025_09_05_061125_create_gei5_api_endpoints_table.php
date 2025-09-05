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
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint_path', 255)->unique()->comment('API路径');
            $table->string('method', 10)->default('GET')->comment('HTTP方法');
            $table->string('controller', 255)->comment('控制器');
            $table->string('action', 255)->comment('方法名');
            $table->text('description')->nullable()->comment('端点描述');
            $table->json('parameters')->nullable()->comment('参数定义');
            $table->boolean('requires_auth')->default(false)->comment('是否需要认证');
            $table->string('permission_required', 255)->nullable()->comment('需要权限');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->string('version', 10)->default('v1')->comment('API版本');
            $table->timestamps();
            
            $table->index(['method', 'endpoint_path']);
            $table->index(['is_active']);
            $table->index(['version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
