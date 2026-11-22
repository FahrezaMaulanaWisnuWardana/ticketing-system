<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Dependency-free RFC 6238 TOTP (SHA-1, 6 digits, 30 second period).
 */
final class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function otpauthUrl(string $email, string $secret, string $issuer = 'Ticketing'): string
    {
        $label = rawurlencode($issuer.':'.$email);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
    }

    public function verify(string $code, string $secret, ?int $at = null): bool
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return false;
        }

        $timestamp = $at ?? time();
        for ($window = -1; $window <= 1; $window++) {
            $expected = $this->code($secret, $timestamp + ($window * self::PERIOD));
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(static fn (): string => strtoupper(substr(bin2hex(random_bytes(8)), 0, 10)), range(1, $count));
    }

    private function code(string $secret, int $timestamp): string
    {
        $counter = intdiv($timestamp, self::PERIOD);
        $message = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $message, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $binary, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0'))];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(rtrim($encoded, '='));
        $bits = '';
        foreach (str_split($encoded) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                throw new RuntimeException('Invalid base32 secret.');
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr((int) bindec($chunk));
            }
        }

        return $bytes;
    }
}
