<?php

namespace App\Modules\Documents\Services;

use App\Models\User;
use App\Modules\Documents\Contracts\DocumentStorageAdapter;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentVersion;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DocumentService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'md'];

    public function __construct(
        private readonly DocumentStorageAdapter $storage,
        private readonly AudienceResolver $audience,
        private readonly AuditLogger $audit,
        private readonly \App\Shared\Contracts\VirusScanner $virusScanner,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{document: Document, duplicate_warning: DocumentVersion|null}
     */
    public function upload(User $uploader, array $data, UploadedFile $file): array
    {
        $this->assertSafeUpload($file);

        $scan = $this->virusScanner->scan($file->getRealPath());
        if (! ($scan['clean'] ?? false)) {
            throw new \RuntimeException('Upload blocked by virus scanner: '.($scan['message'] ?? 'infected'));
        }

        $checksum = hash_file('sha256', $file->getRealPath());
        $duplicate = DocumentVersion::query()->where('checksum_sha256', $checksum)->first();

        $contents = (string) file_get_contents($file->getRealPath());
        $ext = strtolower($file->getClientOriginalExtension());
        $path = sprintf('%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);

        $stored = $this->storage->put($path, $contents, [
            'mime' => $this->mimeFromExtension($ext),
        ]);

        $document = DB::transaction(function () use ($uploader, $data, $file, $checksum, $stored, $contents, $ext) {
            $document = Document::query()->create([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'owner_id' => $uploader->id,
                'visibility' => $data['visibility'] ?? 'inherit',
                'audience' => $this->audience->normalize($data['audience'] ?? []),
                'is_policy' => (bool) ($data['is_policy'] ?? false),
                'mandatory_ack' => (bool) ($data['mandatory_ack'] ?? false),
                'review_at' => $data['review_at'] ?? null,
            ]);

            $version = $this->createVersion([
                'document_id' => $document->id,
                'version_number' => 1,
                'storage_ref' => $stored['ref'],
                'disk' => $stored['disk'],
                'drive_file_id' => $stored['drive_file_id'] ?? null,
                'drive_revision_id' => $stored['drive_revision_id'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'mime' => $stored['mime'] ?? $this->mimeFromExtension($ext),
                'size' => $stored['size'],
                'checksum_sha256' => $checksum,
                'extracted_text' => $this->extractText($contents, $ext),
                'changelog' => $data['changelog'] ?? 'Initial upload',
                'uploaded_by' => $uploader->id,
            ]);

            $document->update(['current_version_id' => $version->id]);

            return $document->fresh(['currentVersion', 'owner', 'category']);
        });

        $this->audit->log('document.uploaded', $document, null, [
            'title' => $document->title,
            'version' => 1,
            'checksum' => $checksum,
        ]);

        return ['document' => $document, 'duplicate_warning' => $duplicate];
    }

    /**
     * @return array{version: DocumentVersion, duplicate_warning: DocumentVersion|null}
     */
    public function uploadNewVersion(Document $document, User $uploader, UploadedFile $file, ?string $changelog = null): array
    {
        $this->assertSafeUpload($file);

        $checksum = hash_file('sha256', $file->getRealPath());
        $duplicate = DocumentVersion::query()
            ->where('checksum_sha256', $checksum)
            ->where('document_id', '!=', $document->id)
            ->first();

        $contents = (string) file_get_contents($file->getRealPath());
        $ext = strtolower($file->getClientOriginalExtension());
        $path = sprintf('%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);
        $stored = $this->storage->newVersion($path, $contents, ['mime' => $this->mimeFromExtension($ext)]);

        $next = (int) $document->versions()->max('version_number') + 1;

        $version = $this->createVersion([
            'document_id' => $document->id,
            'version_number' => $next,
            'storage_ref' => $stored['ref'],
            'disk' => $stored['disk'],
            'drive_file_id' => $stored['drive_file_id'] ?? null,
            'drive_revision_id' => $stored['drive_revision_id'] ?? null,
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $stored['mime'] ?? $this->mimeFromExtension($ext),
            'size' => $stored['size'],
            'checksum_sha256' => $checksum,
            'extracted_text' => $this->extractText($contents, $ext),
            'changelog' => $changelog ?: "Version {$next}",
            'uploaded_by' => $uploader->id,
        ]);

        $document->update(['current_version_id' => $version->id]);

        // New mandatory policy version resets acknowledgement expectation.
        if ($document->is_policy && $document->mandatory_ack) {
            // Acknowledgements remain on old versions; compliance checks current version only.
        }

        $this->audit->log('document.version_uploaded', $document, null, [
            'version' => $next,
            'checksum' => $checksum,
        ]);

        return ['version' => $version, 'duplicate_warning' => $duplicate];
    }

    public function restoreVersionAsNew(Document $document, DocumentVersion $old, User $actor): DocumentVersion
    {
        $contents = $this->storage->get($old->storage_ref);
        $ext = pathinfo($old->original_filename, PATHINFO_EXTENSION) ?: 'bin';
        $path = sprintf('%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);
        $stored = $this->storage->newVersion($path, $contents, ['mime' => $old->mime]);

        $next = (int) $document->versions()->max('version_number') + 1;

        $version = $this->createVersion([
            'document_id' => $document->id,
            'version_number' => $next,
            'storage_ref' => $stored['ref'],
            'disk' => $stored['disk'],
            'drive_file_id' => $stored['drive_file_id'] ?? null,
            'drive_revision_id' => $stored['drive_revision_id'] ?? null,
            'original_filename' => $old->original_filename,
            'mime' => $old->mime,
            'size' => $stored['size'],
            'checksum_sha256' => $old->checksum_sha256,
            'extracted_text' => $this->sanitizeExtractedText($old->extracted_text),
            'changelog' => "Restored from v{$old->version_number}",
            'uploaded_by' => $actor->id,
        ]);

        $document->update(['current_version_id' => $version->id]);
        $this->audit->log('document.version_restored', $document, [
            'from' => $old->version_number,
        ], [
            'to' => $next,
        ]);

        return $version;
    }

    public function trash(Document $document): void
    {
        $document->update(['trashed_at' => now()]);
        $document->delete();
        $this->audit->log('document.trashed', $document);
    }

    public function restoreFromTrash(Document $document): void
    {
        $document->restore();
        $document->update(['trashed_at' => null]);
        $this->audit->log('document.restored_from_trash', $document);
    }

    public function purgeExpiredTrash(): int
    {
        $expired = Document::onlyTrashed()
            ->where('trashed_at', '<=', now()->subDays(30))
            ->get();

        foreach ($expired as $document) {
            foreach ($document->versions as $version) {
                $this->storage->delete($version->storage_ref);
            }
            $document->forceDelete();
        }

        return $expired->count();
    }

    public function download(Document $document, DocumentVersion $version, User $user): string
    {
        if (! $document->isVisibleTo($user)) {
            $this->audit->log('document.download_denied', $document, null, ['version' => $version->version_number]);
            abort(403);
        }

        $this->audit->log('document.downloaded', $document, null, [
            'version' => $version->version_number,
            'checksum' => $version->checksum_sha256,
        ]);

        return $this->storage->get($version->storage_ref);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Document>
     */
    public function searchBody(string $phrase, User $user)
    {
        return Document::query()
            ->with(['currentVersion', 'category', 'owner'])
            ->notTrashed()
            ->whereHas('versions', function ($q) use ($phrase): void {
                $q->whereNotNull('extracted_text')
                    ->where('extracted_text', 'like', '%'.$phrase.'%');
            })
            ->get()
            ->filter(fn (Document $doc) => $doc->isVisibleTo($user))
            ->values();
    }

    private function assertSafeUpload(UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            abort(422, 'File extension not allowed.');
        }

        $real = $file->getRealPath();
        if (! is_string($real) || $real === '' || ! is_readable($real)) {
            abort(422, 'Uploaded file is missing or unreadable.');
        }

        $head = (string) file_get_contents($real, false, null, 0, 8);

        // Avoid UploadedFile::guessExtension()/getMimeType() — both need finfo_open,
        // which is often missing on shared hosts (same class of failure as Livewire tmpfile()).
        if ($ext === 'pdf' && ! str_starts_with($head, '%PDF-')) {
            abort(422, 'File does not look like a valid PDF.');
        }

        if (in_array($ext, ['doc', 'xls', 'ppt'], true)) {
            // Legacy OLE Compound File signature.
            if (! str_starts_with($head, "\xd0\xcf\x11\xe0")) {
                abort(422, 'File does not look like a valid Office document.');
            }
        }

        if (in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            // OOXML is a ZIP package.
            if (! str_starts_with($head, 'PK')) {
                abort(422, 'File does not look like a valid Office Open XML document.');
            }
        }
    }

    private function mimeFromExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'md' => 'text/markdown',
            default => 'application/octet-stream',
        };
    }

    /**
     * Persist a version row; if extracted_text still trips MySQL charset checks,
     * retry once with null so the upload itself never fails on search indexing.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createVersion(array $attributes): DocumentVersion
    {
        try {
            return DocumentVersion::query()->create($attributes);
        } catch (\Illuminate\Database\QueryException $e) {
            if (! array_key_exists('extracted_text', $attributes) || $attributes['extracted_text'] === null) {
                throw $e;
            }

            // MySQL often reports charset errors as SQLSTATE 22007 / 1366.
            $message = $e->getMessage();
            $isCharsetError = str_contains($message, 'extracted_text')
                || str_contains($message, 'Incorrect string value')
                || str_contains($message, 'SQLSTATE[22007]')
                || str_contains($message, '1366');

            if (! $isCharsetError) {
                throw $e;
            }

            $attributes['extracted_text'] = null;

            return DocumentVersion::query()->create($attributes);
        }
    }

    private function extractText(string $contents, string $extension): ?string
    {
        $ext = strtolower($extension);

        if (in_array($ext, ['txt', 'csv', 'md'], true)) {
            return $this->sanitizeExtractedText(mb_substr($contents, 0, 500000));
        }

        if ($ext === 'pdf') {
            // Only match printable ASCII PDF string literals. Broader patterns pull
            // compressed/binary streams from Google Docs PDFs and MySQL utf8mb4 rejects them.
            $text = '';
            if (preg_match_all('/\(([\x20-\x7E]{3,200})\)/', $contents, $matches)) {
                foreach ($matches[1] as $chunk) {
                    $chunk = stripcslashes($chunk);
                    $chunk = trim($chunk);
                    if ($chunk !== '' && preg_match('/[A-Za-z0-9]{3,}/', $chunk)) {
                        $text .= $chunk.' ';
                    }
                }
            }

            return $this->sanitizeExtractedText($text);
        }

        // DOCX/XLSX are zip+xml — pull XML text nodes if ZipArchive available.
        if (in_array($ext, ['docx', 'xlsx'], true) && class_exists(\ZipArchive::class)) {
            $tmp = tempnam(sys_get_temp_dir(), 'doc');
            file_put_contents($tmp, $contents);
            $zip = new \ZipArchive;
            if ($zip->open($tmp) === true) {
                $xml = '';
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (is_string($name) && str_ends_with($name, '.xml')) {
                        $xml .= ' '.$zip->getFromIndex($i);
                    }
                }
                $zip->close();
                @unlink($tmp);
                $text = trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return $this->sanitizeExtractedText($text);
            }
            @unlink($tmp);
        }

        return null;
    }

    /**
     * Ensure extracted body text is safe for MySQL utf8mb4 columns.
     */
    private function sanitizeExtractedText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        // Drop invalid UTF-8 bytes first (iconv is stricter than mb_* here).
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if (! is_string($clean) || $clean === '') {
            // Last resort: keep ASCII printable only.
            $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? '';
        }

        // Strip control chars (keep tab/newline) then normalize whitespace.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean) ?? '';
        $clean = preg_replace('/\s+/', ' ', $clean) ?? '';
        $clean = trim($clean);

        if ($clean === '' || ! mb_check_encoding($clean, 'UTF-8')) {
            return null;
        }

        return mb_substr($clean, 0, 500000);
    }
}
