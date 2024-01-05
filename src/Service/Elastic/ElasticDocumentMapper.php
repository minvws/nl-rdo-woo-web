<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DateRangeConverter;

class ElasticDocumentMapper
{
    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     *
     * @return array<string, mixed>
     */
    public function mapDocumentToElasticDocument(Document $document, array $metadata = null, array $pages = null): array
    {
        $dossiers = [];
        $dossierIds = [];
        foreach ($document->getDossiers() as $dossier) {
            $dossiers[] = $this->mapDossierToElasticDocument($dossier);
            $dossierIds[] = $dossier->getDossierNr();
        }

        $inquiryIds = [];
        foreach ($document->getInquiries() as $inquiry) {
            $inquiryIds[] = $inquiry->getId();
        }

        $file = $document->getFileInfo();
        $documentDoc = [
            'type' => 'document',
            'document_nr' => $document->getDocumentNr(),
            'dossier_nr' => $dossierIds,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'source_type' => $file->getSourceType(),
            'date' => $document->getDocumentDate()->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'family_id' => $document->getFamilyId() ?? 0,
            'document_id' => $document->getDocumentId() ?? '',
            'thread_id' => $document->getThreadId() ?? 0,
            'judgement' => $document->getJudgement(),
            'grounds' => $document->getGrounds(),
            'subjects' => $document->getSubjects(),
            'date_period' => $document->getPeriod(),
            'audio_duration' => $document->getDuration(),
            'document_pages' => $document->getPageCount(),
            'dossiers' => $dossiers,
            'inquiry_ids' => $inquiryIds,
        ];

        if ($metadata !== null) {
            $documentDoc['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $documentDoc['pages'] = $pages;
        }

        return $documentDoc;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapDossierToElasticDocument(Dossier $dossier): array
    {
        $departments = $this->getDepartments($dossier);

        $inquiryIds = [];
        foreach ($dossier->getInquiries() as $inquiry) {
            $inquiryIds[] = $inquiry->getId();
        }

        return [
            'type' => 'dossier',
            'dossier_nr' => $dossier->getDossierNr(),
            'title' => $dossier->getTitle(),
            'status' => $dossier->getStatus(),
            'summary' => $dossier->getSummary(),
            'document_prefix' => $dossier->getDocumentPrefix(),
            'departments' => $departments,
            'date_from' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
            'date_to' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
            'date_range' => [
                'gte' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                'lte' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
            ],
            'date_period' => DateRangeConverter::convertToString($dossier->getDateFrom(), $dossier->getDateTo()),
            'publication_reason' => $dossier->getPublicationReason(),
            'publication_date' => $dossier->getPublicationDate()?->format(\DateTimeInterface::ATOM),
            'decision_date' => $dossier->getDecisionDate()?->format(\DateTimeInterface::ATOM),
            'decision' => $dossier->getDecision(),
            'inquiry_ids' => $inquiryIds,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function getDepartments(Dossier $dossier): array
    {
        $departments = [];
        foreach ($dossier->getDepartments() as $department) {
            $departments[] = [
                'name' => $department->getName(),
                'id' => $department->getId(),
            ];
        }

        return $departments;
    }
}
