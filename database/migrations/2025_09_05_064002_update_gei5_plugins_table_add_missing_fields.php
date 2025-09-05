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
        Schema::table('plugins', function (Blueprint $table) {
            $table->json('dependencies')->nullable()->after('config');
            $table->string('service_type', 50)->default('general')->after('dependencies');
            $table->integer('priority')->default(10)->after('service_type');
            $table->boolean('has_update')->default(false)->after('priority');
            $table->string('available_version', 20)->nullable()->after('has_update');
        });
    }

    public function down(): void
    {
        Schema::table('plugins', function (Blueprint $table) {
            $table->dropColumn(['dependencies', 'service_type', 'priority', 'has_update', 'available_version']);
        });
    }
};
