<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Entity\DecisionAttachment;
use App\Entity\Dossier;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WooDecision extends Dossier
{
    public function getType(): DossierType
    {
        return DossierType::WOO_DECISION;
    }

    public function getAttachmentEntityClass(): string
    {
        return DecisionAttachment::class;
    }
}
