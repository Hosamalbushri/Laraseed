<?php

namespace Webkul\LostAndFound\Services\Application;

use Illuminate\Auth\Access\AuthorizationException;
use Webkul\Student\Models\Student;
use Webkul\User\Models\User;

class LostAndFoundAuthorization
{
    /**
     * Authorize an employee user for a specific Lost & Found ACL permission.
     *
     * @throws AuthorizationException
     */
    public static function authorizeUser(User $actor, string $permission): void
    {
        if ($actor->role && $actor->role->permission_type === 'all') {
            return;
        }

        if (! $actor->hasPermission($permission)) {
            throw new AuthorizationException("User {$actor->id} is unauthorized for permission '{$permission}'.");
        }
    }

    /**
     * Authorize that a student actor owns the specified resource.
     *
     * @throws AuthorizationException
     */
    public static function authorizeStudentOwnership(Student $actor, int $ownerId, string $resourceName = 'Resource'): void
    {
        if ((int) $actor->id !== (int) $ownerId) {
            throw new AuthorizationException("Student {$actor->id} is not the owner of this {$resourceName}.");
        }
    }
}
