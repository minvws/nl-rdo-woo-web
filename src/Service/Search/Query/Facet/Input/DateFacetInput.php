<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use App\Service\Utils\CastTypes;
use Symfony\Component\HttpFoundation\ParameterBag;

final readonly class DateFacetInput extends FacetInput implements ParameterBagFactoryInterface, DateFacetInputInterface
{
    private bool $isActive;

    public static function fromParameterBag(FacetKey $facetKey, ParameterBag $bag): self
    {
        $values = $bag->all($facetKey->getParamName());

        // any value except whitespace is considered as true
        $withoutDate = isset($values['without_date']) && strlen(trim($values['without_date'])) > 0;

        if (! $withoutDate) {
            $from = CastTypes::asImmutableDate($values['from'] ?? null, DateFacetInputInterface::DATE_FORMAT);
            $to = CastTypes::asImmutableDate($values['to'] ?? null, DateFacetInputInterface::DATE_FORMAT);
        }

        return new self(
            withoutDate: $withoutDate,
            from: $from ?? null,
            to: $to ?? null,
        );
    }

    private function __construct(
        public bool $withoutDate,
        public ?\DateTimeImmutable $from,
        public ?\DateTimeImmutable $to,
    ) {
        $this->isActive = $withoutDate || $from !== null || $to !== null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isNotActive(): bool
    {
        return ! $this->isActive();
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
}
