<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticObjectField;
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
        $id = ElasticDocumentId::forDossier($dossier);

        return new ElasticDocument(
            $id,
            ElasticDocumentType::fromEntity($dossier),
            null,
            [
                ElasticField::ID->value => $id,
                ElasticField::DOSSIER_NR->value => $dossier->getDossierNr(),
                ElasticField::PREFIXED_DOSSIER_NR->value => PrefixedDossierNr::forDossier($dossier),
                ElasticField::TITLE->value => $dossier->getTitle(),
                ElasticField::STATUS->value => $dossier->getStatus(),
                ElasticField::SUMMARY->value => $dossier->getSummary(),
                ElasticField::DOCUMENT_PREFIX->value => $dossier->getDocumentPrefix(),
                ElasticObjectField::DEPARTMENTS->value => $this->mapDepartments($dossier),
                ElasticField::DATE_FROM->value => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                ElasticField::DATE_TO->value => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                ElasticField::DATE_RANGE->value => [
                    'gte' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                    'lte' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                ],
                ElasticField::DATE_PERIOD->value => DateRangeConverter::convertToString($dossier->getDateFrom(), $dossier->getDateTo()),
                ElasticField::PUBLICATION_DATE->value => $dossier->getPublicationDate()?->format(\DateTimeInterface::ATOM),
                ElasticObjectField::SUBJECT->value => $this->mapSubject($dossier->getSubject()),
                ElasticField::ORGANISATION_IDS->value => [$dossier->getOrganisation()->getId()],
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
                ElasticField::NAME->value => DepartmentFieldMapper::fromDepartment($department)->getIndexValue(),
                ElasticField::ID->value => $department->getId(),
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
            ElasticField::ID->value => $subject->getId(),
            ElasticField::NAME->value => $subject->getName(),
        ];
    }
}
