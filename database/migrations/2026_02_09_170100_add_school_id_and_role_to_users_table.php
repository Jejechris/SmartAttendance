<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'school_admin', 'teacher', 'student'])->default('student')->after('email');
            }
        });

        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'school_id')) {
                    $table->index(['school_id', 'role'], 'users_school_role_idx');
                }
            });
        } catch (\Throwable) {
            // Index sudah ada di beberapa deployment lama.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'school_id')) {
                try {
                    $table->dropForeign(['school_id']);
                } catch (\Throwable) {
                }
                try {
                    $table->dropIndex('users_school_role_idx');
                } catch (\Throwable) {
                }
                $table->dropColumn('school_id');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
