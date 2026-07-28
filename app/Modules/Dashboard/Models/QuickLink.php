<?php

namespace App\Modules\Dashboard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    protected $fillable = [
        'label',
        'url',
        'category',
        'icon',
        'description',
        'sort_order',
        'is_active',
        'opens_external',
        'staff_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opens_external' => 'boolean',
            'staff_visible' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForStaff(Builder $query): Builder
    {
        return $query->active()->where('staff_visible', true)->orderBy('sort_order')->orderBy('label');
    }

    public function href(): string
    {
        // Internal named routes: route:name
        if (str_starts_with($this->url, 'route:')) {
            $name = substr($this->url, 6);
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name);
            }
        }

        return $this->url;
    }
}
