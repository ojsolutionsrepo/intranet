<?php

namespace App\Shared\Support;

use App\Shared\Services\AuditLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            app(AuditLogger::class)->log('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model): void {
            app(AuditLogger::class)->log(
                'updated',
                $model,
                $model->getOriginal(),
                $model->getChanges(),
            );
        });

        static::deleted(function ($model): void {
            app(AuditLogger::class)->log('deleted', $model, $model->getOriginal(), null);
        });
    }
}
