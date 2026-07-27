<?php

namespace App\Shared\Services;

use App\Models\User;

final class AudienceResolver
{
    /**
     * @param  array{departments?: list<int|string>, teams?: list<int|string>, roles?: list<string>, users?: list<int|string>}|null  $audience
     */
    public function allows(?array $audience, User $user): bool
    {
        if ($audience === null || $audience === []) {
            return true;
        }

        $departments = array_map('intval', $audience['departments'] ?? []);
        $teams = array_map('intval', $audience['teams'] ?? []);
        $roles = array_map('strval', $audience['roles'] ?? []);
        $users = array_map('intval', $audience['users'] ?? []);

        $hasAnyConstraint = $departments !== [] || $teams !== [] || $roles !== [] || $users !== [];
        if (! $hasAnyConstraint) {
            return true;
        }

        if ($users !== [] && in_array((int) $user->id, $users, true)) {
            return true;
        }

        if ($roles !== []) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        if ($departments !== []) {
            $userDeptIds = $user->departments()->pluck('departments.id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($departments, $userDeptIds) !== []) {
                return true;
            }
        }

        if ($teams !== []) {
            $userTeamIds = $user->teams()->pluck('teams.id')->map(fn ($id) => (int) $id)->all();
            if (array_intersect($teams, $userTeamIds) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize UI input into the shared audience JSON shape.
     *
     * @param  array<string, mixed>  $input
     * @return array{departments: list<int>, teams: list<int>, roles: list<string>, users: list<int>}
     */
    public function normalize(array $input): array
    {
        return [
            'departments' => array_values(array_unique(array_map('intval', array_filter($input['departments'] ?? [])))),
            'teams' => array_values(array_unique(array_map('intval', array_filter($input['teams'] ?? [])))),
            'roles' => array_values(array_unique(array_map('strval', array_filter($input['roles'] ?? [])))),
            'users' => array_values(array_unique(array_map('intval', array_filter($input['users'] ?? [])))),
        ];
    }

    /**
     * Empty audience means company-wide.
     *
     * @param  array{departments?: list<int>, teams?: list<int>, roles?: list<string>, users?: list<int>}  $audience
     */
    public function isCompanyWide(array $audience): bool
    {
        return ($audience['departments'] ?? []) === []
            && ($audience['teams'] ?? []) === []
            && ($audience['roles'] ?? []) === []
            && ($audience['users'] ?? []) === [];
    }
}
