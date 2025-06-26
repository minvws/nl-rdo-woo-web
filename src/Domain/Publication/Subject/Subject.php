<?php

declare(strict_types=1);

namespace App\Domain\Publication\Subject;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\Organisation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[UniqueEntity(fields: ['name', 'organisation'], message: 'subject_already_exists')]
class Subject
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 1, max: 50)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection<array-key,AbstractDossier> */
    #[ORM\OneToMany(targetEntity: AbstractDossier::class, mappedBy: 'subject')]
    private Collection $dossiers;

    public function __construct()
    {
        $this->id = Uuid::v6();
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
}
