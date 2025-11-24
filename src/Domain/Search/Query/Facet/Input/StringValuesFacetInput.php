<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

final readonly class StringValuesFacetInput extends FacetInput implements StringValuesFacetInputInterface
{
    public static function fromParameterBag(FacetDefinitionInterface $facet, ParameterBag $bag): self
    {
        $rawData = array_values($bag->all($facet->getRequestParameter()));

        Assert::allString($rawData);

        return new self(
            facet: $facet,
            values: $rawData,
        );
    }

    /**
     * @param list<string> $values
     */
    public function __construct(
        public FacetDefinitionInterface $facet,
        private array $values,
    ) {
    }

    public function isActive(): bool
    {
        return count($this->values) > 0;
    }

    /**
     * @return list<string>
     */
    public function getStringValues(): array
    {
        return $this->values;
    }

    public function getRequestParameters(): array
    {
        return $this->values;
    }

    public function contains(string $value): bool
    {
        return in_array($value, $this->values, true);
    }

    public function without(int|string $key, string $value): self
    {
        return new self(
            $this->facet,
            array_values(
                array_filter(
                    $this->values,
                    static fn (string $item) => $item !== $value,
                )
            ),
        );
    }

    public function getFacetKey(): FacetKey
    {
        return $this->facet->getKey();
    }
}
