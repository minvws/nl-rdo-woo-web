<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DecisionAttachment extends PublicationItem
{
    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'decisionAttachments')]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    private Dossier $dossier;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $formalDate;

    #[ORM\Column(length: 255, enumType: DecisionAttachmentType::class)]
    private DecisionAttachmentType $type;

    public function __construct(
        Dossier $dossier,
        \DateTimeImmutable $formalDate,
        DecisionAttachmentType $type,
    ) {
        parent::__construct();

        $this->dossier = $dossier;
        $this->formalDate = $formalDate;
        $this->type = $type;
    }

    public function getDossier(): Dossier
    {
        return $this->dossier;
    }

    public function getFormalDate(): \DateTimeImmutable
    {
        return $this->formalDate;
    }

    public function setFormalDate(\DateTimeImmutable $formalDate): void
    {
        $this->formalDate = $formalDate;
    }

    public function getType(): DecisionAttachmentType
    {
        return $this->type;
    }

    public function setType(DecisionAttachmentType $type): void
    {
        $this->type = $type;
    }
}
