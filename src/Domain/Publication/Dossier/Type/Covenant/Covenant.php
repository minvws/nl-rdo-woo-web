<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
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
 * @implements EntityWithAttachments<CovenantAttachment>
 * @implements EntityWithMainDocument<CovenantMainDocument>
 */
#[ORM\Entity(repositoryClass: CovenantRepository::class)]
class Covenant extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<CovenantAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<CovenantMainDocument> */
    use HasMainDocument;

    #[ORM\Column(length: 2048)]
    #[Assert\Url(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Length(min: 0, max: 2048, groups: [DossierValidationGroup::CONTENT->value])]
    protected string $previousVersionLink = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\Count(
        min: 2,
        max: 10,
        minMessage: 'min_max_parties',
        groups: [DossierValidationGroup::CONTENT->value],
    )]
    #[Assert\All(
        constraints: [
            new Assert\NotBlank(),
            new Assert\Length(min: 2, max: 100),
        ],
        groups: [DossierValidationGroup::CONTENT->value],
    )]
    private array $parties = [];

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: CovenantMainDocument::class, cascade: ['remove'])]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?CovenantMainDocument $document;

    /** @var Collection<array-key,CovenantAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: CovenantAttachment::class)]
    #[Assert\Count(max: AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
        $this->document = null;
    }

    public function getType(): DossierType
    {
        return DossierType::COVENANT;
    }

    public function getPreviousVersionLink(): string
    {
        return $this->previousVersionLink;
    }

    public function setPreviousVersionLink(string $previousVersionLink): void
    {
        $this->previousVersionLink = $previousVersionLink;
    }

    /**
     * @return list<string>
     */
    public function getParties(): array
    {
        return $this->parties;
    }

    /**
     * @param array<array-key,string> $parties
     */
    public function setParties(array $parties): void
    {
        $this->parties = array_values($parties);
    }

    public function getAttachmentEntityClass(): string
    {
        return CovenantAttachment::class;
    }

    public function getMainDocumentEntityClass(): string
    {
        return CovenantMainDocument::class;
    }
}
