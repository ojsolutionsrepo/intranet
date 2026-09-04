<?php

namespace App\Modules\Documents\Policies;

use App\Models\User;
use App\Modules\Documents\Models\Document;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view') && $document->isVisibleTo($user);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function manage(User $user, Document $document): bool
    {
        return $user->can('documents.manage') || ($user->can('documents.upload') && $document->owner_id === $user->id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.manage');
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->can('documents.manage');
    }

    public function upload(User $user): bool
    {
        return $user->can('documents.upload') || $user->can('documents.manage');
    }
}
