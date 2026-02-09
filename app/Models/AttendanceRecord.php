<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'session_id',
        'student_id',
        'scanned_at',
        'status',
        'late_minutes',
        'token_slot',
        'scan_lat',
        'scan_lng',
        'distance_meters',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'late_minutes' => 'integer',
        'token_slot' => 'integer',
        'scan_lat' => 'float',
        'scan_lng' => 'float',
        'distance_meters' => 'float',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
