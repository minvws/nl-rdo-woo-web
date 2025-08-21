<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use App\Domain\Publication\Attachment\Entity\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<AdviceAttachment>
 * @implements EntityWithMainDocument<AdviceMainDocument>
 */
#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<AdviceAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<AdviceMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: AdviceMainDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?AdviceMainDocument $document;

    /** @var Collection<array-key,AdviceAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: AdviceAttachment::class)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
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
        return DossierType::ADVICE;
    }

    public function getAttachmentEntityClass(): string
    {
        return AdviceAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return AdviceMainDocument::class;
    }
}
