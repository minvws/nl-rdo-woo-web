<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Entity\Department;
use App\Service\DateRangeConverter;
use Symfony\Component\Uid\Uuid;

readonly class DefaultDossierMapper implements ElasticDossierMapperInterface
{
    public function supports(AbstractDossier $dossier): bool
    {
        return true;
    }

    public function map(AbstractDossier $dossier): ElasticDocument
    {
        return new ElasticDocument(
            $dossier->getDossierNr(),
            ElasticDocumentType::fromEntity($dossier),
            null,
            [
                'dossier_nr' => $dossier->getDossierNr(),
                'title' => $dossier->getTitle(),
                'status' => $dossier->getStatus(),
                'summary' => $dossier->getSummary(),
                'document_prefix' => $dossier->getDocumentPrefix(),
                'departments' => $this->mapDepartments($dossier),
                'date_from' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                'date_to' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                'date_range' => [
                    'gte' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                    'lte' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                ],
                'date_period' => DateRangeConverter::convertToString($dossier->getDateFrom(), $dossier->getDateTo()),
                'publication_date' => $dossier->getPublicationDate()?->format(\DateTimeInterface::ATOM),
            ],
        );
    }

    /**
     * @return array<array{name: string, id: Uuid}>
     */
    private function mapDepartments(AbstractDossier $dossier): array
    {
        return $dossier->getDepartments()->map(
            fn (Department $department) => [
                'name' => $department->getName(),
                'id' => $department->getId(),
            ]
        )->toArray();
    }
}
