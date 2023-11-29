<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Entity\DocumentPrefix;
use App\Exception\ExcelReaderException;
use App\Exception\InquiryLinkImportException;
use App\Exception\InventoryReaderException;
use App\Exception\TranslatableException;
use App\Repository\DocumentRepository;
use App\Service\Excel\ColumnMapping;
use App\Service\Excel\ExcelReader;
use App\Service\Excel\ExcelReaderFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InquiryLinkImporter
{
    private const COLUMN_CASE_NR = 'caseNr';
    private const COLUMN_MATTER = 'matter';
    private const COLUMN_DOCUMENT_ID = 'documentId';

    public function __construct(
        private readonly InquiryService $inquiryService,
        private readonly DocumentRepository $documentRepository,
        private readonly ExcelReaderFactory $readerFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function processSpreadsheet(UploadedFile $uploadedFile, DocumentPrefix $prefix): array
    {
        $errors = [
            'generic' => [],
            'row' => [],
        ];

        try {
            $reader = $this->getReader($uploadedFile);

            foreach ($reader as $row) {
                $documentId = $reader->getString($row->getRowIndex(), self::COLUMN_DOCUMENT_ID);
                $matter = $reader->getString($row->getRowIndex(), self::COLUMN_MATTER);
                $caseNrs = $reader->getString($row->getRowIndex(), self::COLUMN_CASE_NR);
                $caseNrs = explode(',', $caseNrs);

                $documentNr = sprintf('%s-%s-%s', $prefix->getPrefix(), $matter, $documentId);
                try {
                    $this->handleDocumentLink($documentNr, $caseNrs);
                } catch (TranslatableException $exception) {
                    $errors['row'][$row->getRowIndex()] = [[
                        'message' => $exception->getMessage(),
                        'translation' => $exception->getTranslationKey(),
                        'placeholders' => $exception->getPlaceholders(),
                    ]];
                }
            }
        } catch (\Exception $exception) {
            if (! $exception instanceof TranslatableException) {
                $exception = InventoryReaderException::forOpenSpreadsheetException($exception);
            }

            $errors['generic'][] = [
                'message' => $exception->getMessage(),
                'translation' => $exception->getTranslationKey(),
                'placeholders' => $exception->getPlaceholders(),
            ];

            return $errors;
        }

        $this->inquiryService->flush();

        return $errors;
    }

    private function getReader(UploadedFile $uploadedFile): ExcelReader
    {
        try {
            return $this->readerFactory->getReader(
                $uploadedFile->getRealPath(),
                new ColumnMapping(
                    name: self::COLUMN_MATTER,
                    required: true,
                    columnNames: ['matter', 'matter id', 'matterid'],
                ),
                new ColumnMapping(
                    name: self::COLUMN_DOCUMENT_ID,
                    required: true,
                    columnNames: ['id', 'documentid', 'document', 'document id', 'documentnr', 'document nr', 'documentnr.', 'document nr.'],
                ),
                new ColumnMapping(
                    name: self::COLUMN_CASE_NR,
                    required: true,
                    columnNames: ['zaaknr', 'casenr', 'zaak', 'case', 'zaaknummer', 'zaaknummers', 'zaaknummer(s)'],
                ),
            );
        } catch (\Exception $exception) {
            throw ExcelReaderException::forOpenSpreadsheetException($exception);
        }
    }

    /**
     * @param string[] $caseNrs
     */
    private function handleDocumentLink(string $documentNr, array $caseNrs): void
    {
        $document = $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
        if (! $document) {
            throw InquiryLinkImportException::forMissingDocument($documentNr);
        }

        $this->inquiryService->updateDocumentInquiries($document, $caseNrs);
    }
}
