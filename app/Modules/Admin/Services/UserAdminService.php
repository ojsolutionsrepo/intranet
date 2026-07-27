<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Shared\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UserAdminService
{
    public function deactivate(User $user): void
    {
        $user->update(['is_active' => false]);
        $this->terminateSessions($user);
        app(AuditLogger::class)->log('user.deactivated', $user, ['is_active' => true], ['is_active' => false]);
    }

    public function reactivate(User $user): void
    {
        $user->update(['is_active' => true]);
        app(AuditLogger::class)->log('user.reactivated', $user, ['is_active' => false], ['is_active' => true]);
    }

    public function terminateSessions(User $user): void
    {
        if (DB::getSchemaBuilder()->hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }
}
