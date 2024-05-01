<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\HasAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Repository\CovenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements EntityWithAttachments<CovenantAttachment>
 */
#[ORM\Entity(repositoryClass: CovenantRepository::class)]
class Covenant extends AbstractDossier implements EntityWithAttachments
{
    /** @use HasAttachments<CovenantAttachment> */
    use HasAttachments;

    #[ORM\Column(length: 255)]
    #[Assert\Url(groups: [StepName::CONTENT->value])]
    protected string $previousVersionLink = '';

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Assert\Count(min: 2, minMessage: 'at_least_two_parties_required', groups: [StepName::CONTENT->value])]
    #[Assert\All(
        constraints: [new Assert\NotBlank()],
        groups: [StepName::CONTENT->value],
    )]
    private array $parties = [];

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: CovenantDocument::class)]
    #[Assert\NotBlank(groups: [StepName::CONTENT->value])]
    #[Assert\Valid(groups: [StepName::CONTENT->value])]
    private ?CovenantDocument $document = null;

    /** @var Collection<array-key,CovenantAttachment> */
    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: CovenantAttachment::class, orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        parent::__construct();

        $this->attachments = new ArrayCollection();
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
     * @return string[]
     */
    public function getParties(): array
    {
        return array_values($this->parties);
    }

    /**
     * @param string[] $parties
     */
    public function setParties(array $parties): void
    {
        $this->parties = $parties;
    }

    public function getDocument(): ?CovenantDocument
    {
        return $this->document;
    }

    public function setDocument(?CovenantDocument $document): void
    {
        $this->document = $document;
    }
}
