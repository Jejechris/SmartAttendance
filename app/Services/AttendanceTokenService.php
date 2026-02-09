<?php

namespace App\Services;

use App\Models\AttendanceSession;
use Carbon\CarbonImmutable;

class AttendanceTokenService
{
    public function generateForSession(AttendanceSession $session, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();

        if (!$session->qr_dynamic) {
            $payload = [
                'sid' => $session->id,
                'slot' => 0,
                'exp' => CarbonImmutable::instance($session->ended_at)->timestamp,
            ];

            return [
                'token' => $this->signPayload($session, $payload),
                'slot' => 0,
                'expires_at' => CarbonImmutable::createFromTimestamp($payload['exp']),
            ];
        }

        $rotateSeconds = max(15, (int) $session->qr_rotate_seconds);
        $slot = intdiv($now->timestamp, $rotateSeconds);
        $expiresAt = CarbonImmutable::createFromTimestamp(($slot + 1) * $rotateSeconds);

        $payload = [
            'sid' => $session->id,
            'slot' => $slot,
            'exp' => $expiresAt->timestamp,
        ];

        return [
            'token' => $this->signPayload($session, $payload),
            'slot' => $slot,
            'expires_at' => $expiresAt,
        ];
    }

    public function verifyForSession(
        AttendanceSession $session,
        string $token,
        int $allowedPastSlots = 1,
        ?CarbonImmutable $now = null
    ): array {
        $now = $now ?? CarbonImmutable::now();

        [$encodedPayload, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if (!$encodedPayload || !$signature) {
            return ['valid' => false, 'reason' => 'invalid_token_format'];
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if (!$payloadJson) {
            return ['valid' => false, 'reason' => 'invalid_payload'];
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || !isset($payload['sid'], $payload['slot'], $payload['exp'])) {
            return ['valid' => false, 'reason' => 'invalid_payload_content'];
        }

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $session->session_secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'reason' => 'invalid_signature'];
        }

        if ((int) $payload['sid'] !== (int) $session->id) {
            return ['valid' => false, 'reason' => 'session_mismatch'];
        }

        $exp = (int) $payload['exp'];
        if ($now->timestamp > $exp) {
            return ['valid' => false, 'reason' => 'expired_token', 'slot' => (int) $payload['slot']];
        }

        if ($session->qr_dynamic) {
            $rotateSeconds = max(15, (int) $session->qr_rotate_seconds);
            $currentSlot = intdiv($now->timestamp, $rotateSeconds);
            $slot = (int) $payload['slot'];
            if ($slot < ($currentSlot - max(0, $allowedPastSlots)) || $slot > ($currentSlot + 1)) {
                return ['valid' => false, 'reason' => 'invalid_slot', 'slot' => $slot];
            }
        }

        return [
            'valid' => true,
            'slot' => (int) $payload['slot'],
            'expires_at' => CarbonImmutable::createFromTimestamp($exp),
        ];
    }

    private function signPayload(AttendanceSession $session, array $payload): string
    {
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedPayload, $session->session_secret);

        return $encodedPayload . '.' . $signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padded = str_pad(strtr($value, '-_', '+/'), strlen($value) + ((4 - strlen($value) % 4) % 4), '=', STR_PAD_RIGHT);
        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
