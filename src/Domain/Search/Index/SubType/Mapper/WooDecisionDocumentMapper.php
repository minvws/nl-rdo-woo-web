<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\ElasticDocumentType;
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
            'type' => 'document',
            'document_nr' => $entity->getDocumentNr(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'source_type' => $file->getSourceType(),
            'date' => $entity->getDocumentDate()?->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'family_id' => $entity->getFamilyId() ?? 0,
            'document_id' => $entity->getDocumentId() ?? '',
            'thread_id' => $entity->getThreadId() ?? 0,
            'judgement' => $entity->getJudgement(),
            'grounds' => $entity->getGrounds(),
            'date_period' => $entity->getPeriod(),
            'document_pages' => $entity->getPageCount(),
            'dossiers' => $dossiers,
            'inquiry_ids' => $inquiryIds,
            'prefixed_dossier_nr' => $prefixedDossierNrs,
        ];

        if ($metadata !== null) {
            $fields['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $fields['pages'] = $pages;
        }

        return new ElasticDocument(
            ElasticDocumentId::forObject($entity),
            ElasticDocumentType::WOO_DECISION,
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
            $fields,
        );
    }
}
