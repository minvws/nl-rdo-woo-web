<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Entity\Document;
use App\Entity\Inquiry;
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
        $dossierNrs = [];
        foreach ($entity->getDossiers() as $dossier) {
            $dossiers[] = $this->wooDecisionMapper->map($dossier)->getDocumentValues();
            $dossierNrs[] = $dossier->getDossierNr();
        }

        $inquiryIds = $entity->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        $file = $entity->getFileInfo();

        $fields = [
            'type' => 'document',
            'document_nr' => $entity->getDocumentNr(),
            'dossier_nr' => $dossierNrs,
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
            'subjects' => $entity->getSubjects(),
            'date_period' => $entity->getPeriod(),
            'document_pages' => $entity->getPageCount(),
            'dossiers' => $dossiers,
            'inquiry_ids' => $inquiryIds,
        ];

        if ($metadata !== null) {
            $fields['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $fields['pages'] = $pages;
        }

        return new ElasticDocument(
            $this->getId($entity),
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
            $fields,
        );
    }

    public function getId(object $entity): string
    {
        /** @var Document $entity */
        Assert::isInstanceOf($entity, Document::class);

        return $entity->getDocumentNr();
    }
}
