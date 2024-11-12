<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ProductionReport extends PublicationItem
{
    #[ORM\OneToOne(inversedBy: 'productionReport', targetEntity: WooDecision::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private WooDecision $dossier;

    public function setDossier(WooDecision $dossier): self
    {
        $this->dossier = $dossier;

        $dossier->setProductionReport($this);

        return $this;
    }

    public function getDossier(): WooDecision
    {
        return $this->dossier;
    }
}
