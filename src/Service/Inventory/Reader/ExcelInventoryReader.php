<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Entity\Dossier;
use App\Exception\InventoryReaderException;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\InventoryDataHelper;
use App\Service\Inventory\MetadataField;
use App\SourceType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 *  @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
 *  @SuppressWarnings(PHPMD.NPathComplexity)
 */
class ExcelInventoryReader implements InventoryReaderInterface
{
    /**
     * @var string[]
     */
    private array $headerMapping;

    private Worksheet $sheet;

    /**
     * @var ColumnMapping[]
     */
    private array $mappings = [];

    public function __construct(ColumnMapping ...$mappings)
    {
        foreach ($mappings as $mapping) {
            $this->mappings[$mapping->getField()->value] = $mapping;
        }
    }

    /**
     * @throws \Exception
     */
    public function open(string $filepath): void
    {
        $spreadsheet = IOFactory::load($filepath);

        // Assume only first worksheet
        $this->sheet = $spreadsheet->getSheet(0);
        $this->headerMapping = $this->resolveHeaderMapping($this->sheet);
    }

    /**
     * @return \Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(Dossier $dossier): \Generator
    {
        foreach ($this->sheet->getRowIterator(2) as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $documentMetadata = null;
            $exception = null;
            try {
                $documentMetadata = $this->processRow($this->sheet, $this->headerMapping, $row->getRowIndex(), $dossier);
            } catch (\Exception $exception) {
                // Exception occurred, but we still continue with the next row to discover and report any other errors
                // To not break the generator yield instead of throwing the exception
                $exception = InventoryReaderException::forRowProcessingException($row->getRowIndex(), $exception);
            }

            yield new InventoryReadItem($documentMetadata, $row->getRowIndex(), $exception);
        }
    }

    /**
     * Resolve the header mapping into an array of mapped headers (name => column)
     * Will throw an exception for missing mandatory headers.
     *
     * @return array<string,string>
     *
     * @throws InventoryReaderException|\PhpOffice\PhpSpreadsheet\Exception
     */
    protected function resolveHeaderMapping(Worksheet $sheet): array
    {
        $headerMapping = [];
        $missingHeaders = $this->mappings;

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $columnName = strval($cell->getValue());
                $columnName = trim(strtolower($columnName));
                $columnName = ltrim($columnName, '0123456789');
                if (empty($columnName)) {
                    continue;
                }

                foreach ($missingHeaders as $key => $mapping) {
                    if ($mapping->matches($columnName)) {
                        $headerMapping[$key] = $cell->getColumn();
                        unset($missingHeaders[$key]);
                    }
                }
            }
        }

        $missingHeaders = array_filter(
            $missingHeaders,
            static fn (ColumnMapping $mapping): bool => $mapping->isRequired()
        );

        if (count($missingHeaders) > 0) {
            throw InventoryReaderException::forMissingHeaders(array_keys($missingHeaders));
        }

        return $headerMapping;
    }

    /**
     * Process a single row of the spreadsheet, maps data to DocumentMetadata VO.
     *
     * @param string[] $headers
     *
     * @throws \Exception
     */
    protected function processRow(Worksheet $sheet, array $headers, int $rowIdx, Dossier $dossier): DocumentMetadata
    {
        $documentId = intval($sheet->getCell($headers['id'] . $rowIdx)->getValue());
        if (empty($documentId)) {
            throw InventoryReaderException::forMissingDocumentIdInRow($rowIdx);
        }

        $documentDate = new \DateTimeImmutable(strval($sheet->getCell($headers[MetadataField::DATE->value] . $rowIdx)->getValue()));
        $fileName = strval($sheet->getCell($headers[MetadataField::DOCUMENT->value] . $rowIdx)->getValue());
        $familyId = intval($sheet->getCell($headers[MetadataField::FAMILY->value] . $rowIdx)->getValue());
        $threadId = intval($sheet->getCell($headers[MetadataField::THREADID->value] . $rowIdx)->getValue());
        $judgement = InventoryDataHelper::judgement($sheet->getCell($headers[MetadataField::JUDGEMENT->value] . $rowIdx)->getValue());
        $grounds = InventoryDataHelper::separateValues($sheet->getCell($headers[MetadataField::GROUND->value] . $rowIdx)->getValue());
        $subjects = InventoryDataHelper::separateValues($sheet->getCell($headers[MetadataField::SUBJECT->value] . $rowIdx)->getValue());
        $period = strval($sheet->getCell($headers[MetadataField::PERIOD->value] . $rowIdx)->getValue());
        $sourceType = SourceType::getType(strval($sheet->getCell($headers[MetadataField::SOURCETYPE->value] . $rowIdx)->getValue()));

        // Set default subjects from the dossier when no subjects have been found in the document
        if (count($subjects) == 0) {
            $subjects = $dossier->getDefaultSubjects();
        }

        $matter = null;
        if (isset($headers[MetadataField::MATTER->value])) {
            $matter = strval($sheet->getCell($headers[MetadataField::MATTER->value] . $rowIdx)->getValue());
        }

        $link = null;
        if (isset($headers[MetadataField::LINK->value])) {
            $link = strval($sheet->getCell($headers[MetadataField::LINK->value] . $rowIdx)->getValue());
        }
        $remark = null;
        if (isset($headers[MetadataField::REMARK->value])) {
            $remark = strval($sheet->getCell($headers[MetadataField::REMARK->value] . $rowIdx)->getValue());
        }

        // In old documents, it's possible that the link is in the remark column
        if (empty($link) && str_starts_with($remark ?? '', 'http')) {
            $link = $remark;
            $remark = null;
        }

        $caseNrs = [];
        if (isset($headers[MetadataField::CASENR->value])) {
            $caseNrs = InventoryDataHelper::separateValues($sheet->getCell($headers[MetadataField::CASENR->value] . $rowIdx)->getValue());
        }

        $suspended = false;
        if (isset($headers[MetadataField::SUSPENDED->value])) {
            $suspended = InventoryDataHelper::isTrue($sheet->getCell($headers[MetadataField::SUSPENDED->value] . $rowIdx)->getValue());
        }

        return new DocumentMetadata(
            $documentDate,
            $fileName,
            $familyId,
            $sourceType,
            $grounds,
            $documentId,
            $judgement,
            $period,
            $subjects ?? [],
            $threadId,
            $caseNrs,
            $suspended,
            $link,
            $remark,
            $matter,
        );
    }

    public function isEmptyRow(Row $row): bool
    {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $value = $cell->getValue();
            if ($value !== null && trim(strval($value)) !== '') {
                return false;
            }
        }

        return true;
    }
}
