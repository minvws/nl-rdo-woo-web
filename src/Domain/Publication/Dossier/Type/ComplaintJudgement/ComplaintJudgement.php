<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithMainDocument<ComplaintJudgementDocument>
 */
#[ORM\Entity(repositoryClass: ComplaintJudgementRepository::class)]
class ComplaintJudgement extends AbstractDossier implements EntityWithMainDocument
{
    /** @use HasMainDocument<ComplaintJudgementDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ComplaintJudgementDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?ComplaintJudgementDocument $document;

    public function __construct()
    {
        parent::__construct();

        $this->document = null;
    }

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
        return ComplaintJudgementDocument::class;
    }
}
