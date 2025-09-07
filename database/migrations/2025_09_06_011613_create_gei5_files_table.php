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
        Schema::create('gei5_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('extension', 10);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['folder_id']);
            $table->index(['uploaded_by']);
            $table->index(['mime_type']);
            $table->index(['extension']);
            $table->index(['created_at']);
            
            $adminUsersTable = config('database.connections.mysql.prefix', '') . 'admin_users';
            $table->foreign('uploaded_by')->references('id')->on($adminUsersTable)->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gei5_files');
    }
};
