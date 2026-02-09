<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('scanned_at')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'alpha']);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedBigInteger('token_slot')->nullable();

            $table->decimal('scan_lat', 10, 7)->nullable();
            $table->decimal('scan_lng', 10, 7)->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
            $table->index(['school_id', 'student_id', 'created_at']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
