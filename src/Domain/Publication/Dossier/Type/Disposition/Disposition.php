<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
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
 * @implements EntityWithAttachments<DispositionAttachment>
 * @implements EntityWithMainDocument<DispositionDocument>
 */
#[ORM\Entity(repositoryClass: DispositionRepository::class)]
class Disposition extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<DispositionAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<DispositionDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: DispositionDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?DispositionDocument $document;

    /** @var Collection<array-key,DispositionAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DispositionAttachment::class, orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
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
        return DossierType::DISPOSITION;
    }

    public function getAttachmentEntityClass(): string
    {
        return DispositionAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return DispositionDocument::class;
    }
}
