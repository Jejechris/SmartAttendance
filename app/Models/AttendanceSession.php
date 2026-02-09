<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'started_at',
        'ended_at',
        'late_tolerance_minutes',
        'qr_dynamic',
        'qr_rotate_seconds',
        'location_validation',
        'center_lat',
        'center_lng',
        'radius_meters',
        'session_secret',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'qr_dynamic' => 'boolean',
        'location_validation' => 'boolean',
        'center_lat' => 'float',
        'center_lng' => 'float',
        'radius_meters' => 'integer',
        'late_tolerance_minutes' => 'integer',
        'qr_rotate_seconds' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    public function scanAttempts(): HasMany
    {
        return $this->hasMany(AttendanceScanAttempt::class, 'session_id');
    }

    public function lateLimitAt(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->started_at)->addMinutes($this->late_tolerance_minutes);
    }
}
