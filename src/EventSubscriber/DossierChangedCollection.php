<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ArrayCollection<array-key, Uuid>
 */
class DossierChangedCollection extends ArrayCollection
{
    public function addDossierId(Uuid $dossierId): void
    {
        if (! $this->contains($dossierId)) {
            $this->add($dossierId);
        }
    }
}
