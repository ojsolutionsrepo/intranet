<?php

namespace App\Modules\Admin\Livewire;

use App\Models\User;
use App\Modules\Admin\Services\UserAdminService;
use Livewire\Component;
use Livewire\WithPagination;

class UserIndex extends Component
{
    use WithPagination;

    public string $q = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function deactivate(int $userId, UserAdminService $admin): void
    {
        $user = User::query()->findOrFail($userId);
        abort_if($user->id === auth()->id(), 403, 'You cannot deactivate yourself.');
        $admin->deactivate($user);
        session()->flash('status', "{$user->name} has been deactivated.");
    }

    public function reactivate(int $userId, UserAdminService $admin): void
    {
        $user = User::query()->findOrFail($userId);
        $admin->reactivate($user);
        session()->flash('status', "{$user->name} has been reactivated.");
    }

    public function render()
    {
        $users = User::query()
            ->with(['roles', 'departments', 'profile'])
            ->when($this->q !== '', function ($query): void {
                $q = $this->q;
                $query->where(function ($inner) use ($q): void {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('admin-module::livewire.user-index', compact('users'));
    }
}
