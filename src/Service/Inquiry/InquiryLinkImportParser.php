<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Exception\FileReaderException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\FileReader\FileReaderInterface;
use Shared\Service\Inventory\InventoryDataHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class InquiryLinkImportParser
{
    private const string COLUMN_CASE_NR = 'caseNr';
    private const string COLUMN_MATTER = 'matter';
    private const string COLUMN_DOCUMENT_ID = 'documentId';

    public function __construct(
        private readonly ExcelReaderFactory $readerFactory,
    ) {
    }

    /**
     * @return \Generator<string,array<string>>
     */
    public function parse(UploadedFile $uploadedFile, DocumentPrefix $prefix): \Generator
    {
        $reader = $this->getReader($uploadedFile);
        foreach ($reader as $rowIdx => $row) {
            /** @var int|string $rowIdx */
            unset($row);
            $rowIdx = intval($rowIdx);
            $documentId = $reader->getString($rowIdx, self::COLUMN_DOCUMENT_ID);
            $matter = $reader->getString($rowIdx, self::COLUMN_MATTER);
            $caseNrs = InventoryDataHelper::separateValues(
                $reader->getString($rowIdx, self::COLUMN_CASE_NR),
                [',', ';']
            );

            $documentNr = sprintf('%s-%s-%s', $prefix->getPrefix(), $matter, $documentId);

            yield $documentNr => $caseNrs;
        }
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
