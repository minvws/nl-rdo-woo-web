<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication;

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
 * @implements EntityWithAttachments<OtherPublicationAttachment>
 * @implements EntityWithMainDocument<OtherPublicationMainDocument>
 */
#[ORM\Entity(repositoryClass: OtherPublicationRepository::class)]
class OtherPublication extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<OtherPublicationAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<OtherPublicationMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: OtherPublicationMainDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?OtherPublicationMainDocument $document;

    /** @var Collection<array-key,OtherPublicationAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: OtherPublicationAttachment::class)]
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
        return DossierType::OTHER_PUBLICATION;
    }

    public function getAttachmentEntityClass(): string
    {
        return OtherPublicationAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return OtherPublicationMainDocument::class;
    }
}
