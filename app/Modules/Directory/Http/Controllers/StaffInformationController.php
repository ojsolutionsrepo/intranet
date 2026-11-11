<?php

namespace App\Modules\Directory\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use App\Modules\Directory\Http\Requests\StoreStaffInformationRequest;
use App\Modules\Directory\Models\StaffInformation;
use App\Modules\Directory\Services\StaffInformationService;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffInformationController extends Controller
{
    public function edit(ModuleRegistry $registry, StaffInformationService $service): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $this->renderForm($service, $actor, $actor, false);
    }

    public function update(
        ModuleRegistry $registry,
        StoreStaffInformationRequest $request,
        StaffInformationService $service,
    ): RedirectResponse {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $this->persist($request, $service, $actor, $actor, route('directory.staff-information.edit'));
    }

    public function adminEdit(ModuleRegistry $registry, User $user, StaffInformationService $service): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        abort_unless(StaffInformation::actorCanManage($actor), 403);

        return $this->renderForm($service, $user, $actor, true);
    }

    public function adminUpdate(
        ModuleRegistry $registry,
        User $user,
        StoreStaffInformationRequest $request,
        StaffInformationService $service,
    ): RedirectResponse {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        abort_unless(StaffInformation::actorCanManage($actor), 403);

        return $this->persist(
            $request,
            $service,
            $user,
            $actor,
            route('admin.users.staff-information.edit', $user),
        );
    }

    public function setEditing(
        ModuleRegistry $registry,
        User $user,
        Request $request,
        StaffInformationService $service,
    ): RedirectResponse {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        abort_unless(StaffInformation::actorCanManage($actor), 403);

        $validated = $request->validate([
            'staff_can_edit' => ['required', 'boolean'],
        ]);

        $service->setStaffCanEdit($user, $actor, (bool) $validated['staff_can_edit']);

        return redirect()
            ->route('admin.users.staff-information.edit', $user)
            ->with('status', $validated['staff_can_edit']
                ? 'Staff can now edit their information.'
                : 'Staff editing locked.');
    }

    public function downloadCv(ModuleRegistry $registry, StaffInformationService $service, ?User $user = null): StreamedResponse
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        $subject = $user ?? $actor;
        if ($subject->id !== $actor->id) {
            abort_unless(StaffInformation::actorCanManage($actor), 403);
        }

        $record = $service->forUser($subject);
        abort_unless(filled($record->cv_path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        abort_unless($disk->exists($record->cv_path), 404);

        return $disk->download(
            $record->cv_path,
            $record->cv_original_name ?: 'cv.pdf',
        );
    }

    public function downloadDbs(ModuleRegistry $registry, StaffInformationService $service, ?User $user = null): StreamedResponse
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        $subject = $user ?? $actor;
        if ($subject->id !== $actor->id) {
            abort_unless(StaffInformation::actorCanManage($actor), 403);
        }

        $record = $service->forUser($subject);
        abort_unless(filled($record->dbs_path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($disk->exists($record->dbs_path), 404);

        return $disk->download(
            $record->dbs_path,
            $record->dbs_original_name ?: 'dbs-certificate.pdf',
        );
    }

    private function renderForm(StaffInformationService $service, User $subject, User $actor, bool $adminMode): View
    {
        $record = $service->forUser($subject);
        $canEdit = $record->actorCanEdit($actor);

        return view('directory::staff-information', [
            'subject' => $subject,
            'actor' => $actor,
            'record' => $record,
            'canEdit' => $canEdit,
            'adminMode' => $adminMode || StaffInformation::actorCanManage($actor),
            'genders' => StaffInformation::GENDERS,
            'contractTypes' => StaffInformation::CONTRACT_TYPES,
            'formAction' => $adminMode
                ? route('admin.users.staff-information.update', $subject)
                : route('directory.staff-information.update'),
            'cvDownloadUrl' => $adminMode
                ? route('admin.users.staff-information.cv', $subject)
                : route('directory.staff-information.cv'),
            'dbsDownloadUrl' => $adminMode
                ? route('admin.users.staff-information.dbs', $subject)
                : route('directory.staff-information.dbs'),
        ]);
    }

    private function persist(
        StoreStaffInformationRequest $request,
        StaffInformationService $service,
        User $subject,
        User $actor,
        string $redirectRoute,
    ): RedirectResponse {
        try {
            $service->save(
                $subject,
                $actor,
                $request->validated(),
                $request->file('photo'),
                $request->file('cv'),
                $request->file('dbs'),
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['photo' => $e->getMessage()]);
        }

        return redirect($redirectRoute)->with('status', 'Staff information saved.');
    }
}
