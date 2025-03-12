<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use Webmozart\Assert\Assert;

readonly class WooDecisionDocumentMapper implements ElasticSubTypeMapperInterface
{
    public function __construct(
        private WooDecisionMapper $wooDecisionMapper,
    ) {
    }

    public function supports(object $entity): bool
    {
        return $entity instanceof Document;
    }

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function map(object $entity, ?array $metadata = null, ?array $pages = null): ElasticDocument
    {
        /** @var Document $entity */
        Assert::isInstanceOf($entity, Document::class);

        $dossiers = [];
        $prefixedDossierNrs = [];
        foreach ($entity->getDossiers() as $dossier) {
            $dossiers[] = $this->wooDecisionMapper->map($dossier)->getDocumentValues();
            $prefixedDossierNrs[] = PrefixedDossierNr::forDossier($dossier);
        }

        $inquiryIds = $entity->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        $file = $entity->getFileInfo();

        $fields = [
            ElasticField::TYPE->value => ElasticDocumentType::WOO_DECISION_DOCUMENT->value,
            ElasticField::DOCUMENT_NR->value => $entity->getDocumentNr(),
            ElasticField::MIME_TYPE->value => $file->getMimeType(),
            ElasticField::FILE_SIZE->value => $file->getSize(),
            ElasticField::FILE_TYPE->value => $file->getType(),
            ElasticField::SOURCE_TYPE->value => $file->getSourceType(),
            ElasticField::DATE->value => $entity->getDocumentDate()?->format(\DateTimeInterface::ATOM),
            ElasticField::FILENAME->value => $file->getName(),
            ElasticField::FAMILY_ID->value => $entity->getFamilyId() ?? 0,
            ElasticField::DOCUMENT_ID->value => $entity->getDocumentId() ?? '',
            ElasticField::THREAD_ID->value => $entity->getThreadId() ?? 0,
            ElasticField::JUDGEMENT->value => $entity->getJudgement(),
            ElasticField::GROUNDS->value => $entity->getGrounds(),
            ElasticField::DATE_PERIOD->value => $entity->getPeriod(),
            ElasticField::DOCUMENT_PAGES->value => $entity->getPageCount(),
            ElasticNestedField::DOSSIERS->value => $dossiers,
            ElasticField::INQUIRY_IDS->value => $inquiryIds,
            ElasticField::PREFIXED_DOSSIER_NR->value => $prefixedDossierNrs,
        ];

        if ($metadata !== null) {
            $fields[ElasticField::METADATA->value] = $metadata;
        }

        if ($pages !== null) {
            $fields[ElasticNestedField::PAGES->value] = $pages;
        }

        return new ElasticDocument(
            ElasticDocumentId::forObject($entity),
            ElasticDocumentType::WOO_DECISION,
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
            $fields,
        );
    }
}
