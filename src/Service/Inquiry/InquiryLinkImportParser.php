<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Exception;
use Generator;
use Shared\Exception\FileReaderException;
use Shared\Exception\InquiryLinkImportException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\ExcelReaderFactory;
use Shared\Service\FileReader\FileReaderInterface;
use Shared\Service\Inventory\InventoryDataHelper;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function intval;
use function sprintf;

class InquiryLinkImportParser
{
    private const string COLUMN_INQUIRY_NUMBER = 'inquiryNumber';
    private const string COLUMN_MATTER = 'matter';
    private const string COLUMN_PUBLICATION_CONTEXT = 'publicationContext';
    private const string COLUMN_DOCUMENT_ID = 'documentId';

    public function __construct(
        private readonly ExcelReaderFactory $readerFactory,
    ) {
    }

    /**
     * @return Generator<string, array<array-key, string>>
     */
    public function parse(UploadedFile $uploadedFile, string $prefix): Generator
    {
        $reader = $this->getReader($uploadedFile);

        $hasMatter = $reader->hasColumn(self::COLUMN_MATTER);
        $hasPublicationContext = $reader->hasColumn(self::COLUMN_PUBLICATION_CONTEXT);

        if ($hasMatter && $hasPublicationContext) {
            throw InquiryLinkImportException::forMatterAndPublicationContextCombination();
        }

        if (! $hasMatter && ! $hasPublicationContext) {
            throw FileReaderException::forMissingHeaders(['Publicatiecontext']);
        }

        foreach ($reader as $rowIdx => $row) {
            /** @var int|string $rowIdx */
            unset($row);
            $rowIdx = intval($rowIdx);
            $documentId = $reader->getString($rowIdx, self::COLUMN_DOCUMENT_ID);
            $inquiryNumbers = InventoryDataHelper::separateValues(
                $reader->getString($rowIdx, self::COLUMN_INQUIRY_NUMBER),
                [',', ';'],
            );

            $matter = $reader->getOptionalString($rowIdx, self::COLUMN_MATTER);
            $publicationContext = $reader->getOptionalString($rowIdx, self::COLUMN_PUBLICATION_CONTEXT);

            if ($hasMatter) {
                $publicationContext = sprintf('%s-%s', $prefix, $matter);
            }

            $documentNumber = DocumentNumber::fromString(sprintf('%s-%s', $publicationContext, $documentId));

            yield $documentNumber->toString() => $inquiryNumbers;
        }
    }

    public function hasMatterColumn(UploadedFile $uploadedFile): bool
    {
        return $this->getReader($uploadedFile)->hasColumn(self::COLUMN_MATTER);
    }

    private function getReader(UploadedFile $uploadedFile): FileReaderInterface
    {
        try {
            return $this->readerFactory->createReader(
                $uploadedFile->getRealPath(),
                new ColumnMapping(
                    name: self::COLUMN_MATTER,
                    required: false,
                    columnNames: ['matter', 'matter id', 'matterid'],
                ),
                new ColumnMapping(
                    name: self::COLUMN_PUBLICATION_CONTEXT,
                    required: false,
                    columnNames: ['publicatiecontext', 'publicatie context', 'publicationcontext', 'publication context', 'publication_context'],
                ),
                new ColumnMapping(
                    name: self::COLUMN_DOCUMENT_ID,
                    required: true,
                    columnNames: ['id', 'documentid', 'document', 'document id', 'documentnr', 'document nr', 'documentnr.', 'document nr.'],
                ),
                new ColumnMapping(
                    name: self::COLUMN_INQUIRY_NUMBER,
                    required: true,
                    columnNames: ['zaaknr', 'casenr', 'zaak', 'case', 'zaaknummer', 'zaaknummers', 'zaaknummer(s)', 'inquiry_number', 'inquiry_nr'],
                ),
            );
        } catch (Exception $exception) {
            throw FileReaderException::forOpenSpreadsheetException($exception);
        }
    }
}
