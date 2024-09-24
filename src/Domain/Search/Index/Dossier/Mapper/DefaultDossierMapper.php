<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticField;
use App\Domain\Search\Result\FacetValue\AbbreviatedValue;
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
                ElasticField::SUBJECT->value => $this->mapSubject($dossier->getSubject()),
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
                'name' => AbbreviatedValue::fromDepartment($department)->getIndexValue(),
                'id' => $department->getId(),
            ]
        )->toArray();
    }

    /**
     * @return array<array-key, string|Uuid>|null
     */
    private function mapSubject(?Subject $subject): ?array
    {
        if ($subject === null) {
            return null;
        }

        return [
            ElasticField::SUBJECT_ID->value => $subject->getId(),
            ElasticField::SUBJECT_NAME->value => $subject->getName(),
        ];
    }
}
