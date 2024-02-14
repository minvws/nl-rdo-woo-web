<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

final readonly class StringValuesFacetInput extends FacetInput implements ParameterBagFactoryInterface, StringValuesFacetInputInterface
{
    public static function fromParameterBag(FacetKey $facetKey, ParameterBag $bag): self
    {
        $rawData = array_values($bag->all($facetKey->getParamName()));

        Assert::allString($rawData);

        return new self(
            values: $rawData,
        );
    }

    /**
     * @param list<string> $values
     */
    private function __construct(
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
}
