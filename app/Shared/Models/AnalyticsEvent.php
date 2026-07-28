<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'payload',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
