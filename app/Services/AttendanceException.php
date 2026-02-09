<?php

namespace App\Services;

use RuntimeException;

class AttendanceException extends RuntimeException
{
    public static function rejected(string $reason): self
    {
        return new self($reason);
    }
}
