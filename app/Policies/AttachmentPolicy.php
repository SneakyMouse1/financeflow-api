<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Determine whether the user can delete the attachment.
     * Check is done via the related transaction's user_id.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        return $attachment->transaction && $user->id === $attachment->transaction->user_id;
    }
}
