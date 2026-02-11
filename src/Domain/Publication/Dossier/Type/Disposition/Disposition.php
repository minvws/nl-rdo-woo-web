<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Entity\HasAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\HasMainDocument;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<DispositionAttachment>
 * @implements EntityWithMainDocument<DispositionMainDocument>
 */
#[ORM\Entity(repositoryClass: DispositionRepository::class)]
class Disposition extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<DispositionAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<DispositionMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DispositionMainDocument::class, cascade: ['remove', 'persist'])]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?DispositionMainDocument $document;

    /** @var Collection<array-key,DispositionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DispositionAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    #[Override]
    public function setDateFrom(?DateTimeImmutable $dateFrom): static
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateFrom;

        return $this;
    }

    public function getType(): DossierType
    {
        return DossierType::DISPOSITION;
    }

    public function getAttachmentEntityClass(): string
    {
        return DispositionAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return DispositionMainDocument::class;
    }
}
