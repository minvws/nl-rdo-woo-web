<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Reader;

use Exception;
use Generator;
use InvalidArgumentException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Exception\InventoryReaderException;
use Shared\Service\FileReader\ColumnMapping;
use Shared\Service\FileReader\FileReaderInterface;
use Shared\Service\FileReader\ReaderFactoryInterface;
use Shared\Service\Inquiry\CaseNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\InventoryDataHelper;
use Shared\Service\Inventory\MetadataField;

use function count;
use function intval;
use function is_string;
use function mb_strlen;
use function preg_match;
use function str_starts_with;
use function strlen;

class InventoryReader implements InventoryReaderInterface
{
    private const int MAX_LINK_LENGTH = 2048;
    private const int MAX_FILENAME_LENGTH = 500;
    private const int MAX_REMARK_LENGTH = 1000;
    private const int MIN_DOCUMENT_ID_LENGTH = 1;
    private const int MAX_DOCUMENT_ID_LENGTH = 170;
    private const int MIN_MATTER_LENGTH = 2;
    private const int MAX_MATTER_LENGTH = 50;

    /**
     * @var ColumnMapping[]
     */
    private readonly array $mappings;
    private FileReaderInterface $reader;

    public function __construct(
        private readonly ReaderFactoryInterface $readerFactory,
        ColumnMapping ...$mappings,
    ) {
        $this->mappings = $mappings;
    }

    /**
     * @throws Exception
     */
    public function open(string $filepath): void
    {
        $this->reader = $this->readerFactory->createReader($filepath, ...$this->mappings);
    }

    /**
     * @return Generator<InventoryReadItem>
     */
    public function getDocumentMetadataGenerator(WooDecision $dossier): Generator
    {
        foreach ($this->reader as $rowIdx => $row) {
            unset($row);
            $rowIdx = intval($rowIdx);
            $documentMetadata = null;
            $exception = null;
            try {
                $documentMetadata = $this->mapRow($rowIdx);
            } catch (Exception $exception) {
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
     * @throws Exception
     */
    protected function mapRow(int $rowIdx): DocumentMetadata
    {
        $links = $this->getLinks($rowIdx);
        $remark = $this->getRemark($rowIdx);

        // In old documents, it's possible that the link is in the remark column
        if (count($links) === 0 && is_string($remark) && str_starts_with($remark, 'http')) {
            $links = [$remark];
            $remark = null;
        }

        return new DocumentMetadata(
            date: $this->reader->getOptionalDateTime($rowIdx, MetadataField::DATE->value),
            filename: $this->getFilename($rowIdx),
            familyId: $this->getFamilyId($rowIdx),
            sourceType: SourceType::create($this->reader->getOptionalString($rowIdx, MetadataField::SOURCETYPE->value)),
            grounds: InventoryDataHelper::getGrounds($this->reader->getString($rowIdx, MetadataField::GROUND->value)),
            id: $this->getDocumentId($rowIdx),
            judgement: InventoryDataHelper::judgement($this->reader->getString($rowIdx, MetadataField::JUDGEMENT->value)),
            period: null,
            threadId: $this->getThreadId($rowIdx),
            caseNumbers: $this->getCaseNumbers($rowIdx),
            suspended: InventoryDataHelper::isTrue($this->reader->getOptionalString($rowIdx, MetadataField::SUSPENDED->value)),
            links: $links,
            remark: $remark,
            matter: $this->getMatter($rowIdx),
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
            if ($link && strlen($link) > self::MAX_LINK_LENGTH) {
                throw InventoryReaderException::forLinkTooLong($link, $rowIdx);
            }
        }

        return $links;
    }

    private function getRemark(int $rowIdx): ?string
    {
        $remark = $this->reader->getOptionalString($rowIdx, MetadataField::REMARK->value);
        if ($remark !== null && mb_strlen($remark) > self::MAX_REMARK_LENGTH) {
            throw InventoryReaderException::forRemarkTooLong($rowIdx, self::MAX_REMARK_LENGTH);
        }

        return $remark;
    }

    private function getFamilyId(int $rowIdx): ?int
    {
        $familyId = $this->reader->getOptionalInt($rowIdx, MetadataField::FAMILY->value);
        if ($familyId !== null && $familyId < 1) {
            throw InventoryReaderException::forInvalidFamilyId($rowIdx);
        }

        return $familyId;
    }

    private function getThreadId(int $rowIdx): ?int
    {
        $threadId = $this->reader->getOptionalInt($rowIdx, MetadataField::THREADID->value);
        if ($threadId !== null && $threadId < 1) {
            throw InventoryReaderException::forInvalidThreadId($rowIdx);
        }

        return $threadId;
    }

    private function getDocumentId(int $rowIdx): string
    {
        $documentId = $this->reader->getString($rowIdx, MetadataField::ID->value);
        if ($documentId === '') {
            throw InventoryReaderException::forMissingDocumentIdInRow($rowIdx);
        }

        $length = mb_strlen($documentId);
        if ($length < self::MIN_DOCUMENT_ID_LENGTH || $length > self::MAX_DOCUMENT_ID_LENGTH) {
            throw InventoryReaderException::forInvalidDocumentIdLength($rowIdx, self::MIN_DOCUMENT_ID_LENGTH, self::MAX_DOCUMENT_ID_LENGTH);
        }

        if (preg_match('/[^a-z0-9.]/i', $documentId)) {
            throw InventoryReaderException::forInvalidDocumentId($rowIdx);
        }

        return $documentId;
    }

    private function getFilename(int $rowIdx): string
    {
        $filename = $this->reader->getString($rowIdx, MetadataField::DOCUMENT->value);
        if (strlen($filename) > self::MAX_FILENAME_LENGTH) {
            throw InventoryReaderException::forFileTooLong($filename, $rowIdx);
        }

        return $filename;
    }

    private function getCaseNumbers(int $rowIdx): CaseNumbers
    {
        $caseNumbersInput = $this->reader->getOptionalString($rowIdx, MetadataField::CASENR->value);
        try {
            $caseNumbers = CaseNumbers::fromCommaSeparatedString($caseNumbersInput);
        } catch (InvalidArgumentException) {
            throw InventoryReaderException::forCaseNumbersInvalid($caseNumbersInput ?? '', $rowIdx);
        }

        return $caseNumbers;
    }

    private function getMatter(int $rowIdx): string
    {
        $matter = $this->reader->getString($rowIdx, MetadataField::MATTER->value);

        $length = mb_strlen($matter);
        if ($length < self::MIN_MATTER_LENGTH || $length > self::MAX_MATTER_LENGTH) {
            throw InventoryReaderException::forInvalidMatterInRow($rowIdx, self::MIN_MATTER_LENGTH, self::MAX_MATTER_LENGTH);
        }

        return $matter;
    }
}
