<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // 重命名is_active为status
            $table->string('status', 20)->default('inactive')->after('application_type');
            $table->json('required_plugins')->nullable()->after('table_schema');
            $table->json('default_settings')->nullable()->after('required_plugins');
            $table->boolean('has_update')->default(false)->after('default_settings');
            $table->string('available_version', 20)->nullable()->after('has_update');
        });

        // 迁移现有数据
        \Illuminate\Support\Facades\DB::statement(
            "UPDATE " . config('database.connections.mysql.prefix') . "themes 
             SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END"
        );

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('application_type');
        });

        // 恢复数据
        \Illuminate\Support\Facades\DB::statement(
            "UPDATE " . config('database.connections.mysql.prefix') . "themes 
             SET is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END"
        );

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['status', 'required_plugins', 'default_settings', 'has_update', 'available_version']);
        });
    }
};
