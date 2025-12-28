<?php

namespace App\Traits;

use App\Services\AuditLogService;

trait Auditable
{
    /**
     * Boot the trait
     */
    public static function bootAuditable(): void
    {
        // Log creation
        static::created(function ($model) {
            $user = auth()->user();
            if ($user) {
                AuditLogService::logCreate(
                    $user,
                    $model->getTable(),
                    $model->id,
                    ['attributes' => $model->getAttributes()]
                );
            }
        });

        // Log updates
        static::updated(function ($model) {
            $user = auth()->user();
            if ($user) {
                AuditLogService::logUpdate(
                    $user,
                    $model->getTable(),
                    $model->id,
                    [
                        'changes' => $model->getChanges(),
                        'original' => $model->getOriginal(),
                    ]
                );
            }
        });

        // Log deletion
        static::deleted(function ($model) {
            $user = auth()->user();
            if ($user) {
                AuditLogService::logDelete(
                    $user,
                    $model->getTable(),
                    $model->id,
                    ['attributes' => $model->getAttributes()]
                );
            }
        });
    }
}

