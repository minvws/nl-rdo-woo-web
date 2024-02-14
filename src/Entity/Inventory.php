<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Inventory extends PublicationItem
{
    #[ORM\OneToOne(inversedBy: 'inventory', targetEntity: Dossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Dossier $dossier;

    public function setDossier(Dossier $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getDossier(): Dossier
    {
        return $this->dossier;
    }
}
