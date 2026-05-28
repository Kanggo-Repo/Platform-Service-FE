<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'auth_provider')) {
                $table->string('auth_provider')->nullable()->after('email')->index();
            }

            if (! Schema::hasColumn('users', 'auth_subject')) {
                $table->string('auth_subject')->nullable()->after('auth_provider')->unique();
            }

            if (! Schema::hasColumn('users', 'role_snapshot')) {
                $table->json('role_snapshot')->nullable()->after('auth_subject');
            }

            if (! Schema::hasColumn('users', 'permission_snapshot')) {
                $table->json('permission_snapshot')->nullable()->after('role_snapshot');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];

            foreach (['auth_provider', 'auth_subject', 'role_snapshot', 'permission_snapshot', 'last_login_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
