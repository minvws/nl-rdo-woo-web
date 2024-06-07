<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierValidationGroup;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\HasMainDocument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<CovenantAttachment>
 * @implements EntityWithMainDocument<CovenantDocument>
 */
#[ORM\Entity(repositoryClass: CovenantRepository::class)]
class Covenant extends AbstractDossier implements EntityWithAttachments, EntityWithMainDocument
{
    /** @use HasAttachments<CovenantAttachment> */
    use HasAttachments;

    /** @use HasMainDocument<CovenantDocument> */
    use HasMainDocument;

    #[ORM\Column(length: 255)]
    #[Assert\Url(groups: [DossierValidationGroup::CONTENT->value])]
    protected string $previousVersionLink = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\Count(min: 2, minMessage: 'at_least_two_parties_required', groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\All(
        constraints: [new Assert\NotBlank()],
        groups: [DossierValidationGroup::CONTENT->value],
    )]
    private array $parties = [];

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: CovenantDocument::class)]
    #[Assert\NotBlank(groups: [DossierValidationGroup::CONTENT->value])]
    #[Assert\Valid(groups: [DossierValidationGroup::CONTENT->value])]
    private ?CovenantDocument $document;

    /** @var Collection<array-key,CovenantAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: CovenantAttachment::class, orphanRemoval: true)]
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
        return CovenantDocument::class;
    }
}
