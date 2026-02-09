<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classesAsStudent(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_students', 'student_id', 'class_id')
            ->withPivot(['school_id', 'is_active'])
            ->withTimestamps();
    }

    public function teachingSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'teacher_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function decidedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'decided_by');
    }

    public function studentViolations(): HasMany
    {
        return $this->hasMany(StudentViolation::class, 'student_id');
    }

    public function createdViolations(): HasMany
    {
        return $this->hasMany(StudentViolation::class, 'created_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['teacher', 'school_admin'], true);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'school_admin';
    }

    public function canManageDiscipline(): bool
    {
        return in_array($this->role, ['teacher', 'school_admin'], true);
    }
}
