<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScanAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'session_id',
        'student_id',
        'attempted_at',
        'token_slot',
        'result',
        'reason_code',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'token_slot' => 'integer',
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
