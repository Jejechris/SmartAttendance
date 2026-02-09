<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0);

            $table->boolean('qr_dynamic')->default(true);
            $table->unsignedSmallInteger('qr_rotate_seconds')->default(30);

            $table->boolean('location_validation')->default(false);
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_meters')->nullable();

            $table->char('session_secret', 64);
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'started_at']);
            $table->index(['school_id', 'class_id', 'started_at']);
            $table->index(['teacher_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
