<?php

namespace App\Modules\Directory\Livewire;

use App\Models\User;
use App\Modules\Directory\Services\ProfilePhotoService;
use App\Shared\Models\UserProfile;
use App\Shared\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileEdit extends Component
{
    use WithFileUploads;

    public string $bio = '';

    public string $phone = '';

    public string $extension = '';

    public string $location = '';

    public string $expertiseInput = '';

    public $photo = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $profile = $user->profile;

        $this->bio = (string) ($profile?->bio ?? '');
        $this->phone = (string) ($profile?->phone ?? '');
        $this->extension = (string) ($profile?->extension ?? '');
        $this->location = (string) ($profile?->location ?? '');
        $this->expertiseInput = implode(', ', $user->expertiseTags());
    }

    public function save(AuditLogger $audit): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $validated = $this->validate([
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'extension' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:120'],
            'expertiseInput' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $expertise = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($validated['expertiseInput'] ?? '')),
        )));

        $profile = UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $old = $profile->only(['bio', 'phone', 'extension', 'location', 'expertise']);

        $profile->update([
            'bio' => $validated['bio'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'extension' => $validated['extension'] ?: null,
            'location' => $validated['location'] ?: null,
            'expertise' => $expertise,
        ]);

        if ($this->photo) {
            if (! extension_loaded('gd')) {
                throw ValidationException::withMessages([
                    'photo' => 'Image processing (GD) is not available on this server.',
                ]);
            }
            app(ProfilePhotoService::class)->store($user, $this->photo);
            $this->photo = null;
        }

        $audit->log('profile.updated', $user, $old, $profile->fresh()->only(['bio', 'phone', 'extension', 'location', 'expertise']));

        session()->flash('status', 'Your profile has been updated.');
    }

    public function render()
    {
        return view('directory::livewire.profile-edit', [
            'user' => Auth::user()?->load('profile', 'departments', 'roles'),
        ]);
    }
}
