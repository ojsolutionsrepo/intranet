<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationHealth extends Model
{
    protected $table = 'integration_health';

    protected $fillable = [
        'name',
        'driver',
        'status',
        'circuit',
        'failure_count',
        'opened_at',
        'last_success_at',
        'last_failure_at',
        'last_sync_at',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }
}
