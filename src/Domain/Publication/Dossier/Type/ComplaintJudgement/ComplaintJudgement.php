<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithMainDocument<ComplaintJudgementMainDocument>
 */
#[ORM\Entity(repositoryClass: ComplaintJudgementRepository::class)]
class ComplaintJudgement extends AbstractDossier implements EntityWithMainDocument
{
    /** @use HasMainDocument<ComplaintJudgementMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ComplaintJudgementMainDocument::class, cascade: ['remove'])]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?ComplaintJudgementMainDocument $document;

    public function __construct()
    {
        parent::__construct();

        $this->document = null;
    }

    #[\Override]
    public function setDateFrom(?\DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateFrom;

        return $this;
    }

    public function getType(): DossierType
    {
        return DossierType::COMPLAINT_JUDGEMENT;
    }

    public function getMainDocumentEntityClass(): string
    {
        return ComplaintJudgementMainDocument::class;
    }
}
