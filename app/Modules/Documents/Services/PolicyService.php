<?php

namespace App\Modules\Documents\Services;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAcknowledgement;
use App\Shared\Services\AuditLogger;
use Illuminate\Support\Collection;

final class PolicyService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, Document>
     */
    public function hubFor(User $user): Collection
    {
        return Document::query()
            ->with(['currentVersion', 'category', 'owner', 'acknowledgements'])
            ->policies()
            ->notTrashed()
            ->orderBy('title')
            ->get()
            ->filter(fn (Document $doc) => $doc->isVisibleTo($user))
            ->values();
    }

    public function acknowledge(Document $document, User $user): DocumentAcknowledgement
    {
        abort_unless($document->is_policy && $document->current_version_id, 422);
        abort_unless($document->isVisibleTo($user), 403);

        $ack = DocumentAcknowledgement::query()->updateOrCreate(
            [
                'document_version_id' => $document->current_version_id,
                'user_id' => $user->id,
            ],
            [
                'document_id' => $document->id,
                'acknowledged_at' => now(),
            ],
        );

        $this->audit->log('policy.acknowledged', $document, null, [
            'version_id' => $document->current_version_id,
        ]);

        return $ack;
    }

    public function hasAcknowledgedCurrent(Document $document, User $user): bool
    {
        if (! $document->current_version_id) {
            return false;
        }

        return DocumentAcknowledgement::query()
            ->where('document_version_id', $document->current_version_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * @return list<array{user: string, email: string, status: string, acknowledged_at: string|null}>
     */
    public function complianceMatrix(Document $document): array
    {
        $audienceUserIds = $this->resolveAudienceUsers($document);

        $acks = DocumentAcknowledgement::query()
            ->where('document_version_id', $document->current_version_id)
            ->get()
            ->keyBy('user_id');

        $rows = [];
        foreach ($audienceUserIds as $userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                continue;
            }
            $ack = $acks->get($userId);
            $rows[] = [
                'user' => $user->name,
                'email' => $user->email,
                'status' => $ack ? 'acknowledged' : 'outstanding',
                'acknowledged_at' => $ack?->acknowledged_at?->toDateTimeString(),
            ];
        }

        return $rows;
    }

    /**
     * @return list<int>
     */
    private function resolveAudienceUsers(Document $document): array
    {
        $audience = $document->visibility === 'inherit'
            ? ($document->category?->audience ?? [])
            : ($document->audience ?? []);

        $query = User::query()->where('is_active', true);

        $deptIds = $audience['departments'] ?? [];
        $teamIds = $audience['teams'] ?? [];
        $roles = $audience['roles'] ?? [];
        $userIds = $audience['users'] ?? [];

        if ($deptIds === [] && $teamIds === [] && $roles === [] && $userIds === []) {
            return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $query->where(function ($q) use ($deptIds, $teamIds, $roles, $userIds): void {
            if ($userIds !== []) {
                $q->orWhereIn('id', $userIds);
            }
            if ($deptIds !== []) {
                $q->orWhereHas('departments', fn ($d) => $d->whereIn('departments.id', $deptIds));
            }
            if ($teamIds !== []) {
                $q->orWhereHas('teams', fn ($t) => $t->whereIn('teams.id', $teamIds));
            }
            if ($roles !== []) {
                $q->orWhereHas('roles', fn ($r) => $r->whereIn('name', $roles));
            }
        })->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
