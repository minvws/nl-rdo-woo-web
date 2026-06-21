<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use ArrayIterator;
use IteratorAggregate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\InventoryDataHelper;
use Traversable;
use Webmozart\Assert\Assert;

use function array_diff;
use function array_values;
use function count;

/**
 * @implements IteratorAggregate<array-key,string>
 */
readonly class InquiryNumbers implements IteratorAggregate
{
    /**
     * @var list<string>
     */
    public array $values;

    /**
     * @param array<array-key, string>|list<string> $inquiryNumbers
     */
    public function __construct(array $inquiryNumbers)
    {
        foreach ($inquiryNumbers as $inquiryNumber) {
            Assert::string($inquiryNumber);
            Assert::lengthBetween(
                value: $inquiryNumber,
                min: Inquiry::INQUIRY_NUMBER_MIN_LENGTH,
                max: Inquiry::INQUIRY_NUMBER_MAX_LENGTH,
            );
            Assert::regex($inquiryNumber, Inquiry::INQUIRY_NUMBER_REGEX);
        }

        $this->values = array_values($inquiryNumbers);
    }

    public function getMissingValuesComparedToInput(self $compareWith): self
    {
        return new self(array_diff($compareWith->values, $this->values));
    }

    public function getExtraValuesComparedToInput(self $compareWith): self
    {
        return new self(array_diff($this->values, $compareWith->values));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    public function isNotEmpty(): bool
    {
        return count($this->values) > 0;
    }

    public static function forDocument(Document $document): self
    {
        return new self(
            $document->getInquiries()->map(
                static fn (Inquiry $inquiry) => $inquiry->getInquiryNumber(),
            )->toArray(),
        );
    }

    public static function forWooDecision(WooDecision $wooDecision): self
    {
        return new self(
            $wooDecision->getInquiries()->map(
                static fn (Inquiry $inquiry) => $inquiry->getInquiryNumber(),
            )->toArray(),
        );
    }

    public static function fromCommaSeparatedString(?string $input): self
    {
        if ($input === null) {
            return self::empty();
        }

        return new self(
            InventoryDataHelper::separateValues($input, [',', ';']),
        );
    }

    public static function empty(): self
    {
        return new self([]);
    }
}
