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
        Schema::table('gei5_files', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('gei5_folders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gei5_files', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });
    }
};
