<?php

declare(strict_types=1);

namespace App\Service\Inventory\Reader;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\SourceType;
use App\Exception\InventoryReaderException;
use App\Service\FileReader\ColumnMapping;
use App\Service\FileReader\FileReaderInterface;
use App\Service\FileReader\ReaderFactoryInterface;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\InventoryDataHelper;
use App\Service\Inventory\MetadataField;

class InventoryReader implements InventoryReaderInterface
{
    /**
     * @var ColumnMapping[]
     */
    private readonly array $mappings;
    private FileReaderInterface $reader;

    public const MAX_LINK_SIZE = 2048;
    public const MAX_FILE_SIZE = 1024;

    public function __construct(
        private readonly ReaderFactoryInterface $readerFactory,
        ColumnMapping ...$mappings,
    ) {
        $this->mappings = $mappings;
    }

    /**
     * @throws \Exception
     */
    public function open(string $filepath): void
    {
        $this->reader = $this->readerFactory->createReader($filepath, ...$this->mappings);
    }

    /**
     * @return \Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(WooDecision $dossier): \Generator
    {
        foreach ($this->reader as $rowIdx => $row) {
            unset($row);
            $rowIdx = intval($rowIdx);
            $documentMetadata = null;
            $exception = null;
            try {
                $documentMetadata = $this->mapRow($rowIdx);
            } catch (\Exception $exception) {
                // Exception occurred, but we still continue with the next row to discover and report any other errors
                // To not break the generator yield instead of throwing the exception
                $exception = InventoryReaderException::forRowProcessingException($rowIdx, $exception);
            }

            yield new InventoryReadItem($documentMetadata, $rowIdx, $exception);
        }
    }

    /**
     * Map a single row of the spreadsheet to DocumentMetadata VO.
     *
     * @throws \Exception
     */
    protected function mapRow(int $rowIdx): DocumentMetadata
    {
        $documentId = $this->reader->getString($rowIdx, MetadataField::ID->value);
        if (empty($documentId)) {
            throw InventoryReaderException::forMissingDocumentIdInRow($rowIdx);
        }
        if (preg_match('/[^a-z0-9.]/i', $documentId)) {
            throw InventoryReaderException::forInvalidDocumentId($rowIdx);
        }

        $matter = $this->reader->getString($rowIdx, MetadataField::MATTER->value);
        if (mb_strlen($matter) < 2) {
            throw InventoryReaderException::forMissingMatterInRow($rowIdx);
        }

        $links = $this->getLinks($rowIdx);
        $remark = $this->reader->getOptionalString($rowIdx, MetadataField::REMARK->value);

        // In old documents, it's possible that the link is in the remark column
        if (count($links) === 0 && is_string($remark) && str_starts_with($remark, 'http')) {
            $links = [$remark];
            $remark = null;
        }

        $filename = $this->reader->getString($rowIdx, MetadataField::DOCUMENT->value);
        if (strlen($filename) > self::MAX_FILE_SIZE) {
            throw InventoryReaderException::forFileTooLong($filename, $rowIdx);
        }

        return new DocumentMetadata(
            date: $this->reader->getOptionalDateTime($rowIdx, MetadataField::DATE->value),
            filename: $filename,
            familyId: $this->reader->getOptionalInt($rowIdx, MetadataField::FAMILY->value),
            sourceType: SourceType::create($this->reader->getOptionalString($rowIdx, MetadataField::SOURCETYPE->value)),
            grounds: InventoryDataHelper::getGrounds($this->reader->getString($rowIdx, MetadataField::GROUND->value)),
            id: $documentId,
            judgement: InventoryDataHelper::judgement($this->reader->getString($rowIdx, MetadataField::JUDGEMENT->value)),
            period: null,
            threadId: $this->reader->getOptionalInt($rowIdx, MetadataField::THREADID->value),
            caseNumbers: InventoryDataHelper::separateValues($this->reader->getOptionalString($rowIdx, MetadataField::CASENR->value)),
            suspended: InventoryDataHelper::isTrue($this->reader->getOptionalString($rowIdx, MetadataField::SUSPENDED->value)),
            links: $links,
            remark: $remark,
            matter: $matter,
            refersTo: InventoryDataHelper::separateValues($this->reader->getOptionalString($rowIdx, MetadataField::REFERS_TO->value)),
        );
    }

    public function getCount(): int
    {
        return $this->reader->getCount();
    }

    /**
     * @return string[]
     */
    private function getLinks(int $rowIdx): array
    {
        $links = InventoryDataHelper::separateValues($this->reader->getOptionalString($rowIdx, MetadataField::LINK->value), '|');

        foreach ($links as $link) {
            if ($link && strlen($link) > self::MAX_LINK_SIZE) {
                throw InventoryReaderException::forLinkTooLong($link, $rowIdx);
            }
        }

        return $links;
    }
}
