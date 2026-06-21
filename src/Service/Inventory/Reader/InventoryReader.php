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
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\InventoryDataHelper;
use Shared\Service\Inventory\MetadataField;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\PlainDate;
use Webmozart\Assert\Assert;

use function count;
use function filter_var;
use function intval;
use function is_string;
use function mb_strlen;
use function str_starts_with;
use function strlen;
use function trim;

use const FILTER_VALIDATE_URL;

class InventoryReader implements InventoryReaderInterface
{
    private const int MAX_LINK_LENGTH = 2048;
    private const int MAX_FILENAME_LENGTH = 500;
    private const int MAX_REMARK_LENGTH = 1000;

    /**
     * @var array<array-key, ColumnMapping>
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

            Assert::scalar($rowIdx);
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
            $links = InventoryDataHelper::separateValues($remark, '|');
            $this->validateLinks($links, $rowIdx);
            $remark = null;
        }

        $documentDate = $this->reader->getOptionalDateTime($rowIdx, MetadataField::DATE->value);
        if ($documentDate !== null) {
            $documentDate = PlainDate::createFromFormat('Y-m-d', $documentDate->format('Y-m-d'));
        }

        return new DocumentMetadata(
            date: $documentDate,
            filename: $this->getFilename($rowIdx),
            familyId: $this->getFamilyId($rowIdx),
            sourceType: SourceType::create($this->reader->getOptionalString($rowIdx, MetadataField::SOURCETYPE->value)),
            grounds: InventoryDataHelper::getGrounds($this->reader->getString($rowIdx, MetadataField::GROUND->value)),
            id: $this->getDocumentId($rowIdx),
            judgement: InventoryDataHelper::judgement($this->reader->getString($rowIdx, MetadataField::JUDGEMENT->value)),
            period: null,
            threadId: $this->getThreadId($rowIdx),
            inquiryNumbers: $this->getInquiryNumbers($rowIdx),
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
     * @return array<array-key, string>
     */
    private function getLinks(int $rowIdx): array
    {
        $links = InventoryDataHelper::separateValues($this->reader->getOptionalString($rowIdx, MetadataField::LINK->value), '|');

        $this->validateLinks($links, $rowIdx);

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

    private function getDocumentId(int $rowIdx): DocumentId
    {
        $rawDocumentId = $this->reader->getString($rowIdx, MetadataField::ID->value);

        try {
            return DocumentId::create($rawDocumentId);
        } catch (InvalidArgumentException $e) {
            match ($e->getCode()) {
                DocumentId::ERROR_EMPTY
                    => throw InventoryReaderException::forMissingDocumentIdInRow($rowIdx),
                DocumentId::ERROR_INVALID_LENGTH
                    => throw InventoryReaderException::forInvalidDocumentIdLength($rowIdx, DocumentId::MIN_LENGTH, DocumentId::MAX_LENGTH),
                default => throw InventoryReaderException::forInvalidDocumentId($rowIdx),
            };
        }
    }

    private function getFilename(int $rowIdx): string
    {
        $filename = $this->reader->getString($rowIdx, MetadataField::DOCUMENT->value);
        if (strlen($filename) > self::MAX_FILENAME_LENGTH) {
            throw InventoryReaderException::forFileTooLong($filename, $rowIdx);
        }

        return $filename;
    }

    private function getInquiryNumbers(int $rowIdx): InquiryNumbers
    {
        $inquiryNumbersInput = $this->reader->getOptionalString($rowIdx, MetadataField::INQUIRY_NUMBER->value);
        try {
            $inquiryNumbers = InquiryNumbers::fromCommaSeparatedString($inquiryNumbersInput);
        } catch (InvalidArgumentException) {
            throw InventoryReaderException::forInvalidInquiryNumbers($inquiryNumbersInput ?? '', $rowIdx);
        }

        return $inquiryNumbers;
    }

    private function getMatter(int $rowIdx): ?DocumentMatter
    {
        $matter = $this->reader->getOptionalString($rowIdx, MetadataField::MATTER->value);
        if ($matter === null || trim($matter) === '') {
            return null;
        }

        try {
            return DocumentMatter::create($matter);
        } catch (InvalidArgumentException) {
            throw InventoryReaderException::forInvalidMatterInRow($rowIdx);
        }
    }

    /**
     * @param array<array-key, string> $links
     */
    private function validateLinks(array $links, int $rowIdx): void
    {
        foreach ($links as $link) {
            if (mb_strlen($link) > self::MAX_LINK_LENGTH) {
                throw InventoryReaderException::forLinkTooLong($link, $rowIdx);
            }

            if (! filter_var($link, FILTER_VALIDATE_URL)) {
                throw InventoryReaderException::forInvalidLink($link, $rowIdx);
            }
        }
    }
}
