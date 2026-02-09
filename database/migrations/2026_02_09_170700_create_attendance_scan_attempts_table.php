<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_scan_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('attempted_at');
            $table->unsignedBigInteger('token_slot')->nullable();
            $table->enum('result', ['accepted', 'rejected']);
            $table->string('reason_code', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'session_id', 'attempted_at']);
            $table->index(['session_id', 'student_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scan_attempts');
    }
};
