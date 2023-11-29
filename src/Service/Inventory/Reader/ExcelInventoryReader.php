<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Entity\Dossier;
use App\Exception\InventoryReaderException;
use App\Service\Excel\ColumnMapping;
use App\Service\Excel\ExcelReader;
use App\Service\Excel\ExcelReaderFactory;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\InventoryDataHelper;
use App\Service\Inventory\MetadataField;
use App\SourceType;

class ExcelInventoryReader implements InventoryReaderInterface
{
    /**
     * @var ColumnMapping[]
     */
    private array $mappings;
    private ExcelReaderFactory $excelReaderFactory;
    private ExcelReader $reader;

    public const MAX_LINK_SIZE = 2048;
    public const MAX_FILE_SIZE = 1024;

    public function __construct(
        ExcelReaderFactory $excelReaderFactory,
        ColumnMapping ...$mappings
    ) {
        $this->excelReaderFactory = $excelReaderFactory;
        $this->mappings = $mappings;
    }

    /**
     * @throws \Exception
     */
    public function open(string $filepath): void
    {
        $this->reader = $this->excelReaderFactory->getReader($filepath, ...$this->mappings);
    }

    /**
     * @return \Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(Dossier $dossier): \Generator
    {
        foreach ($this->reader->getIterator() as $row) {
            $documentMetadata = null;
            $exception = null;
            try {
                $documentMetadata = $this->mapRow($row->getRowIndex(), $dossier);
            } catch (\Exception $exception) {
                // Exception occurred, but we still continue with the next row to discover and report any other errors
                // To not break the generator yield instead of throwing the exception
                $exception = InventoryReaderException::forRowProcessingException($row->getRowIndex(), $exception);
            }

            yield new InventoryReadItem($documentMetadata, $row->getRowIndex(), $exception);
        }
    }

    /**
     * Map a single row of the spreadsheet to DocumentMetadata VO.
     *
     * @throws \Exception
     */
    protected function mapRow(int $rowIdx, Dossier $dossier): DocumentMetadata
    {
        $documentId = $this->reader->getInt($rowIdx, MetadataField::ID->value);
        if (empty($documentId)) {
            throw InventoryReaderException::forMissingDocumentIdInRow($rowIdx);
        }

        // Set default subjects from the dossier when no subjects have been found in the document
        $subjects = InventoryDataHelper::separateValues($this->reader->getString($rowIdx, MetadataField::SUBJECT->value));
        if (count($subjects) === 0) {
            $subjects = $dossier->getDefaultSubjects() ?? [];
        }

        $link = $this->reader->getOptionalString($rowIdx, MetadataField::LINK->value);
        $remark = $this->reader->getOptionalString($rowIdx, MetadataField::REMARK->value);

        // In old documents, it's possible that the link is in the remark column
        if (empty($link) && str_starts_with($remark ?? '', 'http')) {
            $link = $remark;
            $remark = null;
        }

        if ($link && strlen($link) > self::MAX_LINK_SIZE) {
            throw InventoryReaderException::forLinkTooLong($link, $rowIdx);
        }

        $filename = $this->reader->getString($rowIdx, MetadataField::DOCUMENT->value);
        if (strlen($filename) > self::MAX_FILE_SIZE) {
            throw InventoryReaderException::forFileTooLong($filename, $rowIdx);
        }

        return new DocumentMetadata(
            date: $this->reader->getDateTime($rowIdx, MetadataField::DATE->value),
            filename: $filename,
            familyId: $this->reader->getInt($rowIdx, MetadataField::FAMILY->value),
            sourceType: SourceType::getType($this->reader->getString($rowIdx, MetadataField::SOURCETYPE->value)),
            grounds: InventoryDataHelper::separateValues($this->reader->getString($rowIdx, MetadataField::GROUND->value)),
            id: $documentId,
            judgement: InventoryDataHelper::judgement($this->reader->getString($rowIdx, MetadataField::JUDGEMENT->value)),
            period: $this->reader->getString($rowIdx, MetadataField::PERIOD->value),
            subjects: $subjects,
            threadId: $this->reader->getInt($rowIdx, MetadataField::THREADID->value),
            caseNumbers: InventoryDataHelper::separateValues($this->reader->getOptionalString($rowIdx, MetadataField::CASENR->value)),
            suspended: InventoryDataHelper::isTrue($this->reader->getOptionalString($rowIdx, MetadataField::SUSPENDED->value)),
            link: $link,
            remark: $remark,
            matter: $this->reader->getOptionalString($rowIdx, MetadataField::MATTER->value),
        );
    }

    public function getCount(): int
    {
        return $this->reader->getCount();
    }
}
