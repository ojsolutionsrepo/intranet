<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

class SearchZeroResult extends Model
{
    protected $table = 'search_zero_results';

    protected $fillable = [
        'user_id',
        'query',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }
}
