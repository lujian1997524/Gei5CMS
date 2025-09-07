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
        Schema::create('gei5_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->integer('sort_order')->default(0);
            $table->json('permissions')->nullable();
            $table->timestamps();
            
            $table->index(['parent_id']);
            $table->index(['created_by']);
            $table->index(['sort_order']);
            $table->unique(['slug', 'parent_id']);
            
            $table->foreign('parent_id')->references('id')->on('gei5_folders')->onDelete('cascade');
            $adminUsersTable = config('database.connections.mysql.prefix', '') . 'admin_users';
            $table->foreign('created_by')->references('id')->on($adminUsersTable)->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gei5_folders');
    }
};
