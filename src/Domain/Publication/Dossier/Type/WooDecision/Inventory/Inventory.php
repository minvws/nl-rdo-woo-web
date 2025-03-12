<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Shared\AbstractPublicationItem;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Inventory extends AbstractPublicationItem
{
    #[ORM\OneToOne(inversedBy: 'inventory', targetEntity: WooDecision::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private WooDecision $dossier;

    public function setDossier(WooDecision $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getDossier(): WooDecision
    {
        return $this->dossier;
    }
}
