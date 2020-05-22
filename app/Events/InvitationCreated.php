<?php

namespace App\Events;

use App\Models\Invitation;

class InvitationCreated
{
    /**
     * @var
     */
    public Invitation $invitation;

    /**
     * InvitationCreated constructor.
     * @param $invitation
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }
}
