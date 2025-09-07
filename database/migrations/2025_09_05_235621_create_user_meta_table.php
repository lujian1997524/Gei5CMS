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
        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('meta_key', 255)->index();
            $table->longText('meta_value')->nullable();
            $table->string('meta_type', 50)->default('string'); // string, number, boolean, json, array
            $table->timestamps();
            
            // 索引优化
            $table->index(['user_id', 'meta_key']);
            $table->unique(['user_id', 'meta_key']); // 每个用户的每个键只能有一个值
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_meta');
    }
};
