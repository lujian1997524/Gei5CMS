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
        $tableName = config('database.connections.mysql.prefix') . 'admin_user_permissions';
        $adminUsersTable = config('database.connections.mysql.prefix') . 'admin_users';
        $permissionsTable = config('database.connections.mysql.prefix') . 'permissions';
        
        Schema::create($tableName, function (Blueprint $table) use ($adminUsersTable, $permissionsTable) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained($adminUsersTable)->onDelete('cascade');
            $table->foreignId('permission_id')->constrained($permissionsTable)->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['admin_user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('database.connections.mysql.prefix') . 'admin_user_permissions';
        Schema::dropIfExists($tableName);
    }
};
