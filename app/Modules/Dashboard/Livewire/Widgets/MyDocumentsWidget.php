<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class MyDocumentsWidget extends Component
{
    public function render()
    {
        try {
            $user = Auth::user();
            abort_unless($user instanceof User, 403);

            $docs = Document::query()
                ->with('currentVersion')
                ->notTrashed()
                ->where('owner_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->filter(fn (Document $d) => $d->isVisibleTo($user))
                ->values();

            return view('dashboard::widgets.my-documents', [
                'documents' => $docs,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.my-documents', [
                'documents' => collect(),
                'error' => 'Documents temporarily unavailable.',
            ]);
        }
    }
}
