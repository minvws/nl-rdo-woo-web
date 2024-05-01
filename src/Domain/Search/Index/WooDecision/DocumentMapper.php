<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\WooDecision;

use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Entity\Document;
use App\Entity\Inquiry;

readonly class DocumentMapper
{
    public function __construct(
        private WooDecisionMapper $wooDecisionMapper,
    ) {
    }

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function map(Document $document, ?array $metadata = null, ?array $pages = null): ElasticDocument
    {
        $dossiers = [];
        $dossierNrs = [];
        foreach ($document->getDossiers() as $dossier) {
            $dossiers[] = $this->wooDecisionMapper->map($dossier)->getFieldValues();
            $dossierNrs[] = $dossier->getDossierNr();
        }

        $inquiryIds = $document->getInquiries()->map(
            fn (Inquiry $inquiry) => $inquiry->getId()
        )->toArray();

        $file = $document->getFileInfo();

        $fields = [
            'type' => 'document',
            'document_nr' => $document->getDocumentNr(),
            'dossier_nr' => $dossierNrs,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'source_type' => $file->getSourceType(),
            'date' => $document->getDocumentDate()?->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'family_id' => $document->getFamilyId() ?? 0,
            'document_id' => $document->getDocumentId() ?? '',
            'thread_id' => $document->getThreadId() ?? 0,
            'judgement' => $document->getJudgement(),
            'grounds' => $document->getGrounds(),
            'subjects' => $document->getSubjects(),
            'date_period' => $document->getPeriod(),
            'document_pages' => $document->getPageCount(),
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
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
            $fields,
        );
    }
}
