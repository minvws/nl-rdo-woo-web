<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

use function array_map;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[UniqueEntity(fields: ['name', 'organisation'], message: 'subject_already_exists')]
class Subject implements HasId
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    #[Assert\Length(min: 1, max: 50)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection<array-key,AbstractDossier> */
    #[ORM\OneToMany(targetEntity: AbstractDossier::class, mappedBy: 'subject')]
    private Collection $dossiers;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $landingPageTitle = null;

    #[ORM\Column(length: 10000, nullable: true)]
    private ?string $landingPageDescription = null;

    #[ORM\Column(type: 'string', length: 255, enumType: SubjectLandingPageStatus::class, nullable: true)]
    private ?SubjectLandingPageStatus $landingPageStatus = null;

    #[ORM\Column(type: 'uuid', unique: true, nullable: true)]
    private ?Uuid $landingPagePreviewToken = null;

    /** @var list<array<string, mixed>>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $landingPageContentTree = null;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->dossiers = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): self
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<AbstractDossier>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    /**
     * @param Collection<AbstractDossier> $dossiers
     */
    public function setDossiers(Collection $dossiers): void
    {
        $this->dossiers = $dossiers;
    }

    /**
     * @param list<SubjectContentNode> $contentTree
     */
    public function setLandingPage(
        string $title,
        string $description,
        SubjectLandingPageStatus $status,
        array $contentTree,
    ): self {
        $this->landingPageTitle = $title;
        $this->landingPageDescription = $description;
        $this->landingPageStatus = $status;
        $this->landingPageContentTree = array_map(
            static fn (SubjectContentNode $node): array => $node->toArray(),
            $contentTree,
        );

        if ($status === SubjectLandingPageStatus::CONCEPT && $this->landingPagePreviewToken === null) {
            $this->landingPagePreviewToken = Uuid::v4();
        }

        return $this;
    }

    public function getLandingPageTitle(): ?string
    {
        return $this->landingPageTitle;
    }

    public function getLandingPageDescription(): ?string
    {
        return $this->landingPageDescription;
    }

    public function getLandingPageStatus(): ?SubjectLandingPageStatus
    {
        return $this->landingPageStatus;
    }

    public function getLandingPagePreviewToken(): ?Uuid
    {
        return $this->landingPagePreviewToken;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getLandingPageContentTree(): ?array
    {
        return $this->landingPageContentTree;
    }
}
