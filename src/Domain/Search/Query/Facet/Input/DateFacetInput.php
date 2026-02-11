<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

use DateTimeImmutable;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Utils\CastTypes;
use Symfony\Component\HttpFoundation\ParameterBag;

use function strlen;
use function trim;

final readonly class DateFacetInput extends FacetInput implements DateFacetInputInterface
{
    public const string WITHOUT_DATE = 'without_date';
    public const string FROM = 'from';
    public const string TO = 'to';

    private bool $isActive;

    public static function fromParameterBag(FacetDefinitionInterface $facet, ParameterBag $bag): self
    {
        $values = $bag->all($facet->getRequestParameter());

        // any value except whitespace is considered as true
        $withoutDate = isset($values[self::WITHOUT_DATE]) && strlen(trim($values[self::WITHOUT_DATE])) > 0;

        $from = CastTypes::asImmutableDate($values[self::FROM] ?? null, DateFacetInputInterface::DATE_FORMAT)?->setTime(0, 0);
        $to = CastTypes::asImmutableDate($values[self::TO] ?? null, DateFacetInputInterface::DATE_FORMAT)?->setTime(0, 0);

        return new self(
            facet: $facet,
            withoutDate: $withoutDate,
            from: $from ?? null,
            to: $to ?? null,
        );
    }

    private function __construct(
        public FacetDefinitionInterface $facet,
        public bool $withoutDate,
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
    ) {
        $this->isActive = $withoutDate || $from !== null || $to !== null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isWithoutDate(): bool
    {
        return $this->withoutDate;
    }

    public function hasAnyPeriodFilterDates(): bool
    {
        return $this->from !== null || $this->to !== null;
    }

    public function getPeriodFilterFrom(): ?string
    {
        return $this->from?->format(DateFacetInputInterface::DATE_FORMAT);
    }

    public function getPeriodFilterTo(): ?string
    {
        return $this->to?->format(DateFacetInputInterface::DATE_FORMAT);
    }

    public function getRequestParameters(): array
    {
        $params = [];

        if ($this->isWithoutDate()) {
            $params[self::WITHOUT_DATE] = 1;
        }

        if ($this->from instanceof DateTimeImmutable) {
            $params[self::FROM] = $this->getPeriodFilterFrom();
        }

        if ($this->to instanceof DateTimeImmutable) {
            $params[self::TO] = $this->getPeriodFilterTo();
        }

        return $params;
    }

    public function includeWithoutDate(): self
    {
        return new self(
            $this->facet,
            true,
            $this->from,
            $this->to,
        );
    }

    public function hasFromDate(): bool
    {
        return $this->from !== null;
    }

    public function hasToDate(): bool
    {
        return $this->to !== null;
    }

    public function without(int|string $key, string $value): self
    {
        return new self(
            $this->facet,
            $key === self::WITHOUT_DATE ? false : $this->withoutDate,
            $key === self::FROM ? null : $this->from,
            $key === self::TO ? null : $this->to,
        );
    }

    public function getFacetKey(): FacetKey
    {
        return $this->facet->getKey();
    }
}
