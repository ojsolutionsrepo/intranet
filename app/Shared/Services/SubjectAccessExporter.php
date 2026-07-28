<?php

namespace App\Shared\Services;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\News\Models\Post;
use App\Shared\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * GDPR subject-access export (UR-ADM-07 / Gate 5E).
 */
final class SubjectAccessExporter
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Build a ZIP of personal data for the given user. Returns storage path.
     */
    public function export(User $subject, User $requester): string
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'requested_by' => $requester->only(['id', 'email', 'name']),
            'subject' => $subject->only(['id', 'name', 'email', 'is_active', 'created_at', 'updated_at', 'manager_id']),
            'roles' => $subject->getRoleNames()->values()->all(),
            'departments' => $subject->departments()->get(['departments.id', 'departments.name', 'departments.slug'])->toArray(),
            'teams' => $subject->teams()->get(['teams.id', 'teams.name', 'teams.slug'])->toArray(),
            'profile' => $subject->profile?->toArray(),
            'news_authored' => Post::query()->where('author_id', $subject->id)->get(['id', 'title', 'slug', 'status', 'published_at'])->toArray(),
            'documents_owned' => Document::query()->where('owner_id', $subject->id)->get(['id', 'title', 'visibility', 'created_at'])->toArray(),
            'audit_events' => AuditLog::query()
                ->where('user_id', $subject->id)
                ->orderByDesc('id')
                ->limit(2000)
                ->get()
                ->toArray(),
        ];

        $dir = 'gdpr-exports/'.$subject->id;
        Storage::disk('local')->makeDirectory($dir);
        $jsonPath = $dir.'/subject-access.json';
        Storage::disk('local')->put($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zipRel = $dir.'/subject-access-'.$subject->id.'-'.now()->format('YmdHis').'.zip';
        $zipAbs = Storage::disk('local')->path($zipRel);
        $zip = new ZipArchive;
        if ($zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create subject-access ZIP.');
        }
        $zip->addFromString('subject-access.json', Storage::disk('local')->get($jsonPath));
        $zip->addFromString('README.txt', "OJ Intranet subject-access export\nGenerated: ".now()->toDateTimeString()."\nContact: ".config('gdpr.privacy_contact')."\n");
        $zip->close();

        $this->audit->log('gdpr.subject_access_export', $subject, null, [
            'path' => $zipRel,
            'requested_by' => $requester->id,
        ], $requester->id);

        return $zipRel;
    }
}
