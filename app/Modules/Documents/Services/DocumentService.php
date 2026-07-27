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
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{document: Document, duplicate_warning: DocumentVersion|null}
     */
    public function upload(User $uploader, array $data, UploadedFile $file): array
    {
        $this->assertSafeUpload($file);

        $checksum = hash_file('sha256', $file->getRealPath());
        $duplicate = DocumentVersion::query()->where('checksum_sha256', $checksum)->first();

        $contents = (string) file_get_contents($file->getRealPath());
        $ext = strtolower($file->getClientOriginalExtension());
        $path = sprintf('%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);

        $stored = $this->storage->put($path, $contents, [
            'mime' => $file->getMimeType(),
        ]);

        $document = DB::transaction(function () use ($uploader, $data, $file, $checksum, $stored, $contents) {
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

            $version = DocumentVersion::query()->create([
                'document_id' => $document->id,
                'version_number' => 1,
                'storage_ref' => $stored['ref'],
                'disk' => $stored['disk'],
                'original_filename' => $file->getClientOriginalName(),
                'mime' => $stored['mime'] ?? $file->getMimeType(),
                'size' => $stored['size'],
                'checksum_sha256' => $checksum,
                'extracted_text' => $this->extractText($contents, $file->getClientOriginalExtension()),
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
        $stored = $this->storage->newVersion($path, $contents, ['mime' => $file->getMimeType()]);

        $next = (int) $document->versions()->max('version_number') + 1;

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $next,
            'storage_ref' => $stored['ref'],
            'disk' => $stored['disk'],
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $stored['mime'] ?? $file->getMimeType(),
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

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $next,
            'storage_ref' => $stored['ref'],
            'disk' => $stored['disk'],
            'original_filename' => $old->original_filename,
            'mime' => $old->mime,
            'size' => $stored['size'],
            'checksum_sha256' => $old->checksum_sha256,
            'extracted_text' => $old->extracted_text,
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
        $guessed = strtolower((string) ($file->guessExtension() ?: ''));

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            abort(422, 'File extension not allowed.');
        }

        // Spoofed extension: declared type disagrees with sniffed type.
        if ($guessed !== '' && $guessed !== $ext) {
            $aliases = [
                'doc' => ['docx', 'zip'],
                'docx' => ['doc', 'zip'],
                'xls' => ['xlsx', 'zip'],
                'xlsx' => ['xls', 'zip'],
                'ppt' => ['pptx', 'zip'],
                'pptx' => ['ppt', 'zip'],
                'csv' => ['txt', 'text'],
                'txt' => ['csv', 'text'],
                'md' => ['txt', 'text'],
                'pdf' => ['pdf'],
            ];
            $ok = in_array($guessed, $aliases[$ext] ?? [], true);
            if (! $ok) {
                abort(422, 'Spoofed or mismatched file extension rejected.');
            }
        }

        // Extra guard: .pdf must look like a PDF header.
        if ($ext === 'pdf') {
            $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 5);
            if (! str_starts_with($head, '%PDF-')) {
                abort(422, 'Spoofed or mismatched file extension rejected.');
            }
        }
    }

    private function extractText(string $contents, string $extension): ?string
    {
        $ext = strtolower($extension);

        if (in_array($ext, ['txt', 'csv', 'md'], true)) {
            return mb_substr($contents, 0, 500000);
        }

        if ($ext === 'pdf') {
            // Lightweight extractor: pull readable ASCII/UTF-8 streams from PDF.
            $text = '';
            if (preg_match_all('/\((\\\\.|[^\\\\)]){1,200}\)/s', $contents, $matches)) {
                foreach ($matches[0] as $match) {
                    $chunk = trim($match, '()');
                    $chunk = stripcslashes($chunk);
                    if (preg_match('/[A-Za-z0-9]{3,}/', $chunk)) {
                        $text .= $chunk.' ';
                    }
                }
            }

            return $text !== '' ? mb_substr($text, 0, 500000) : null;
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
                $text = trim(html_entity_decode(strip_tags($xml)));

                return $text !== '' ? mb_substr($text, 0, 500000) : null;
            }
            @unlink($tmp);
        }

        return null;
    }
}
