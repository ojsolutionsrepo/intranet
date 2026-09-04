<?php

namespace App\Modules\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'storage_ref',
        'disk',
        'drive_file_id',
        'drive_revision_id',
        'original_filename',
        'mime',
        'size',
        'checksum_sha256',
        'extracted_text',
        'changelog',
        'uploaded_by',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isCurrent(): bool
    {
        return $this->document?->current_version_id === $this->id;
    }
}
