<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\RequestForAdvice;

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
 * @implements EntityWithAttachments<RequestForAdviceAttachment>
 * @implements EntityWithMainDocument<RequestForAdviceMainDocument>
 */
#[ORM\Entity(repositoryClass: RequestForAdviceRepository::class)]
class RequestForAdvice extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<RequestForAdviceAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<RequestForAdviceMainDocument> */
    use HasMainDocument;

    #[ORM\OneToOne(targetEntity: RequestForAdviceMainDocument::class, mappedBy: 'dossier')]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?RequestForAdviceMainDocument $document;

    /** @var Collection<array-key,RequestForAdviceAttachment> */
    #[ORM\OneToMany(targetEntity: RequestForAdviceAttachment::class, mappedBy: 'dossier')]
    private Collection $attachments;

    #[ORM\Column(length: 255)]
    #[Assert\Url(groups: [DossierValidationGroup::CONTENT->value])]
    private string $link = '';

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

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getType(): DossierType
    {
        return DossierType::REQUEST_FOR_ADVICE;
    }

    public function getAttachmentEntityClass(): string
    {
        return RequestForAdviceAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return RequestForAdviceMainDocument::class;
    }
}
