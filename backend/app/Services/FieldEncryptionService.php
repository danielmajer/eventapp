<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;

class FieldEncryptionService
{
    /**
     * Get the encrypter instance using DB_FIELD_ENCRYPTION_KEY
     *
     * @return \Illuminate\Encryption\Encrypter
     */
    protected static function getEncrypter(): Encrypter
    {
        // Try to get key from config first (respects config cache)
        $key = config('database.field_encryption_key') ?? env('DB_FIELD_ENCRYPTION_KEY');
        
        // If DB_FIELD_ENCRYPTION_KEY is not set, use APP_KEY as fallback
        if (empty($key)) {
            $key = config('app.key');
            if (empty($key)) {
                // If no key is set, return the value as-is (no encryption)
                throw new \RuntimeException('DB_FIELD_ENCRYPTION_KEY or APP_KEY must be set for field encryption.');
            }
            // Remove 'base64:' prefix if present and decode
            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }
        } else {
            // If key is provided directly, ensure it's the right length
            // Keys longer than 32 chars will be hashed, shorter keys will be padded/hashed
        }

        // Ensure key is 32 characters for AES-256-CBC
        if (strlen($key) !== 32) {
            // Hash the key to get exactly 32 characters
            $key = substr(hash('sha256', $key), 0, 32);
        }

        return new Encrypter($key, 'AES-256-CBC');
    }

    /**
     * Encrypt a field value
     *
     * @param  mixed  $value
     * @return string|null
     */
    public static function encrypt($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            $encrypter = self::getEncrypter();
            return $encrypter->encryptString((string) $value);
        } catch (\RuntimeException $e) {
            // If key is not set, return value as-is (for backward compatibility)
            if (str_contains($e->getMessage(), 'must be set')) {
                \Illuminate\Support\Facades\Log::warning('Field encryption skipped: ' . $e->getMessage());
                return $value; // Return unencrypted if key not set
            }
            \Illuminate\Support\Facades\Log::error('Field encryption failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Field encryption failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Decrypt a field value
     *
     * @param  string|null  $value
     * @return string|null
     */
    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            $encrypter = self::getEncrypter();
            return $encrypter->decryptString($value);
        } catch (\RuntimeException $e) {
            // If key is not set, return value as-is (for backward compatibility)
            if (str_contains($e->getMessage(), 'must be set')) {
                // Don't log warning on every decrypt if key not set
                return $value; // Return unencrypted if key not set
            }
            \Illuminate\Support\Facades\Log::error('Field decryption error: ' . $e->getMessage());
            return $value;
        } catch (DecryptException $e) {
            // If decryption fails, might be unencrypted data (during migration)
            \Illuminate\Support\Facades\Log::warning('Field decryption failed, might be unencrypted: ' . $e->getMessage());
            return $value; // Return as-is if decryption fails
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Field decryption error: ' . $e->getMessage());
            return $value; // Return as-is on error
        }
    }

    /**
     * Check if a value is encrypted
     *
     * @param  string|null  $value
     * @return bool
     */
    public static function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // Encrypted values typically start with base64 encoded data
        // Laravel's Crypt produces values that are base64 encoded
        try {
            // Try to decode - if it's encrypted, it will have a specific structure
            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                return false;
            }
            
            // Laravel encrypted strings have a specific format
            // They contain JSON with iv, value, mac, and tag
            $json = json_decode($decoded, true);
            return isset($json['iv']) && isset($json['value']) && isset($json['mac']);
        } catch (\Exception $e) {
            return false;
        }
    }
}

