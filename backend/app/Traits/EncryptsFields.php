<?php

namespace App\Traits;

use App\Services\FieldEncryptionService;

trait EncryptsFields
{
    /**
     * Get the list of fields that should be encrypted
     *
     * @return array
     */
    public function getEncryptedFields(): array
    {
        return $this->encrypted ?? [];
    }

    /**
     * Encrypt fields before saving
     *
     * @param  array  $attributes
     * @return array
     */
    protected function encryptAttributes(array $attributes): array
    {
        $encryptedFields = $this->getEncryptedFields();

        foreach ($encryptedFields as $field) {
            if (isset($attributes[$field]) && !FieldEncryptionService::isEncrypted($attributes[$field])) {
                $attributes[$field] = FieldEncryptionService::encrypt($attributes[$field]);
            }
        }

        return $attributes;
    }

    /**
     * Decrypt fields after retrieving
     *
     * @param  array  $attributes
     * @return array
     */
    protected function decryptAttributes(array $attributes): array
    {
        $encryptedFields = $this->getEncryptedFields();

        foreach ($encryptedFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = FieldEncryptionService::decrypt($attributes[$field]);
            }
        }

        return $attributes;
    }

    /**
     * Boot the trait
     */
    public static function bootEncryptsFields(): void
    {
        // Encrypt before saving (handles all save operations)
        static::saving(function ($model) {
            $encryptedFields = $model->getEncryptedFields();
            foreach ($encryptedFields as $field) {
                // Check if field exists in attributes (including mass assignment)
                if (array_key_exists($field, $model->attributes)) {
                    $value = $model->attributes[$field];
                    // Only encrypt if value is not null, not empty, and not already encrypted
                    if ($value !== null && $value !== '' && !FieldEncryptionService::isEncrypted($value)) {
                        try {
                            $model->attributes[$field] = FieldEncryptionService::encrypt($value);
                        } catch (\Exception $e) {
                            // If encryption fails (e.g., key not set), keep original value
                            \Illuminate\Support\Facades\Log::warning("Failed to encrypt field {$field}: " . $e->getMessage());
                        }
                    }
                }
            }
        });

        // Decrypt after retrieving
        static::retrieved(function ($model) {
            $encryptedFields = $model->getEncryptedFields();
            foreach ($encryptedFields as $field) {
                if (array_key_exists($field, $model->attributes)) {
                    try {
                        $model->attributes[$field] = FieldEncryptionService::decrypt($model->attributes[$field]);
                    } catch (\Exception $e) {
                        // If decryption fails, keep original value (might be unencrypted)
                        \Illuminate\Support\Facades\Log::debug("Failed to decrypt field {$field}: " . $e->getMessage());
                    }
                }
            }
        });

        // Also handle when attributes are set directly
        static::creating(function ($model) {
            $encryptedFields = $model->getEncryptedFields();
            foreach ($encryptedFields as $field) {
                if (array_key_exists($field, $model->attributes)) {
                    $value = $model->attributes[$field];
                    if ($value !== null && $value !== '' && !FieldEncryptionService::isEncrypted($value)) {
                        try {
                            $model->attributes[$field] = FieldEncryptionService::encrypt($value);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Failed to encrypt field {$field} on create: " . $e->getMessage());
                        }
                    }
                }
            }
        });
    }
}

