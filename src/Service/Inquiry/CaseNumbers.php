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
readonly class CaseNumbers implements IteratorAggregate
{
    /**
     * @var list<string>
     */
    public array $values;

    /**
     * @param array<array-key, string>|list<string> $caseNumbers
     */
    public function __construct(array $caseNumbers)
    {
        foreach ($caseNumbers as $caseNumber) {
            Assert::string($caseNumber);
            Assert::lengthBetween(
                value: $caseNumber,
                min: Inquiry::CASENUMBER_MIN_LENGTH,
                max: Inquiry::CASENUMBER_MAX_LENGTH,
            );
            Assert::regex($caseNumber, Inquiry::CASENUMBER_REGEX);
        }

        $this->values = array_values($caseNumbers);
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
                fn (Inquiry $inquiry) => $inquiry->getCasenr()
            )->toArray(),
        );
    }

    public static function forWooDecision(WooDecision $wooDecision): self
    {
        return new self(
            $wooDecision->getInquiries()->map(
                fn (Inquiry $inquiry) => $inquiry->getCasenr()
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
