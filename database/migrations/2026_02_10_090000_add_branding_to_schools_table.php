<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'display_name')) {
                $table->string('display_name', 120)->nullable()->after('name');
            }

            if (! Schema::hasColumn('schools', 'logo_url')) {
                $table->string('logo_url', 500)->nullable()->after('display_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'logo_url')) {
                $table->dropColumn('logo_url');
            }

            if (Schema::hasColumn('schools', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
