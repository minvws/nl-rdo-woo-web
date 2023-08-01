<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\GovernmentOfficial;

class UpdateOfficialMessage
{
    protected GovernmentOfficial $old;
    protected GovernmentOfficial $new;

    public function __construct(GovernmentOfficial $old, GovernmentOfficial $new)
    {
        $this->old = $old;
        $this->new = $new;
    }

    public function getOld(): GovernmentOfficial
    {
        return $this->old;
    }

    public function getNew(): GovernmentOfficial
    {
        return $this->new;
    }
}
