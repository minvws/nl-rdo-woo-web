<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Entity\Organisation;
use App\Exception\FileReaderException;
use App\Exception\InquiryLinkImportException;
use App\Exception\InventoryReaderException;
use App\Exception\TranslatableException;
use App\Service\FileReader\ColumnMapping;
use App\Service\FileReader\ExcelReaderFactory;
use App\Service\FileReader\FileReaderInterface;
use App\Service\Inventory\InventoryDataHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    public function processSpreadsheet(
        Organisation $activeOrganisation,
        UploadedFile $uploadedFile,
        DocumentPrefix $prefix,
    ): InquiryLinkImportResult {
        $inquiryChangeset = new InquiryChangeset($activeOrganisation);
        $result = new InquiryLinkImportResult($inquiryChangeset);

        try {
            if ($activeOrganisation !== $prefix->getOrganisation()) {
                throw new \RuntimeException('Cannot link to prefixes outside of the active organisation');
            }

            $reader = $this->getReader($uploadedFile);
            foreach ($reader as $rowIdx => $row) {
                unset($row);
                $rowIdx = intval($rowIdx);
                $documentId = $reader->getString($rowIdx, self::COLUMN_DOCUMENT_ID);
                $matter = $reader->getString($rowIdx, self::COLUMN_MATTER);
                $caseNrs = InventoryDataHelper::separateValues(
                    $reader->getString($rowIdx, self::COLUMN_CASE_NR),
                    [',', ';']
                );

                $documentNr = sprintf('%s-%s-%s', $prefix->getPrefix(), $matter, $documentId);
                try {
                    $document = $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
                    if (! $document) {
                        throw InquiryLinkImportException::forMissingDocument($documentNr);
                    }

                    $inquiryChangeset->updateCaseNrsForDocument($document, $caseNrs);
                } catch (TranslatableException $exception) {
                    $result->addRowException($rowIdx, $exception);
                }
            }

            $this->inquiryService->applyChangesetAsync($inquiryChangeset);
        } catch (\Exception $exception) {
            if (! $exception instanceof TranslatableException) {
                $exception = InventoryReaderException::forOpenSpreadsheetException($exception);
            }

            $result->addGenericException($exception);
        }

        return $result;
    }

    private function getReader(UploadedFile $uploadedFile): FileReaderInterface
    {
        try {
            return $this->readerFactory->createReader(
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
            throw FileReaderException::forOpenSpreadsheetException($exception);
        }
    }
}
