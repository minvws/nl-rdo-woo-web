<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DecisionDocument extends PublicationItem
{
    #[ORM\OneToOne(inversedBy: 'decisionDocument', targetEntity: Dossier::class)]
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
