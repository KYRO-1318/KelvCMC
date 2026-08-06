<?php

namespace App\Support;

/**
 * Minimal, dependency-free TOTP (RFC 6238) implementation used for 2FA.
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 */
class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    public function getCode(string $secret, ?int $at = null): string
    {
        $at ??= time();
        $counter = pack('N*', 0, (int) floor($at / 30));
        $hash = hash_hmac('sha1', $counter, $this->base32Decode($secret), true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit code allowing a time window of +/- $window steps.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $time = time();

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->getCode($secret, $time + ($i * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(str_replace('=', '', $secret));
        $bits = 0;
        $value = 0;
        $decoded = '';

        foreach (str_split($secret) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);

            if ($position === false) {
                continue;
            }

            $value = ($value << 5) | $position;
            $bits += 5;

            if ($bits >= 8) {
                $decoded .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $decoded;
    }
}
