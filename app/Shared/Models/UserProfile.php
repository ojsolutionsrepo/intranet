<?php

namespace App\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'photo_path',
        'photo_thumb_path',
        'phone',
        'extension',
        'location',
        'bio',
        'expertise',
        'start_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'start_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function thumbUrl(): ?string
    {
        $path = $this->photo_thumb_path ?: $this->photo_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
