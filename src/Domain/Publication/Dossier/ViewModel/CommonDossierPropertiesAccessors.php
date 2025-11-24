<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\Type\DossierType;

trait CommonDossierPropertiesAccessors
{
    protected function getCommonDossier(): CommonDossierProperties
    {
        return $this->commonDossier;
    }

    public function getDossierId(): string
    {
        return $this->getCommonDossier()->dossierId;
    }

    public function getDossierNr(): string
    {
        return $this->getCommonDossier()->dossierNr;
    }

    public function getDocumentPrefix(): string
    {
        return $this->getCommonDossier()->documentPrefix;
    }

    public function isPreview(): bool
    {
        return $this->getCommonDossier()->isPreview;
    }

    public function getTitle(): string
    {
        return $this->getCommonDossier()->title;
    }

    public function getPageTitle(): string
    {
        return $this->getCommonDossier()->pageTitle;
    }

    public function getPublicationDate(): \DateTimeImmutable
    {
        return $this->getCommonDossier()->publicationDate;
    }

    public function getMainDepartment(): Department
    {
        return $this->getCommonDossier()->mainDepartment;
    }

    public function getSummary(): string
    {
        return $this->getCommonDossier()->summary;
    }

    public function getType(): DossierType
    {
        return $this->getCommonDossier()->type;
    }

    public function getSubject(): ?Subject
    {
        return $this->getCommonDossier()->subject;
    }

    /**
     * @phpstan-assert-if-true !null $this->getSubject()
     */
    public function hasSubject(): bool
    {
        return $this->getCommonDossier()->subject !== null;
    }
}
