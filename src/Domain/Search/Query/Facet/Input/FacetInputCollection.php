<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Service\Search\Model\FacetKey;
use Webmozart\Assert\Assert;

/**
 * @implements \IteratorAggregate<array-key,FacetInputInterface>
 */
readonly class FacetInputCollection implements \IteratorAggregate
{
    /**
     * @var array<array-key, FacetInputInterface>
     */
    private array $inputs;

    public function __construct(FacetInputInterface ...$inputs)
    {
        $this->inputs = $inputs;
    }

    public function getByFacetDefinition(FacetDefinitionInterface $definition): FacetInputInterface
    {
        return $this->getByFacetKey($definition->getKey());
    }

    public function getByFacetKey(FacetKey $facetKey): FacetInputInterface
    {
        Assert::keyExists($this->inputs, $facetKey->value);

        return $this->inputs[$facetKey->value];
    }

    /**
     * @return \Traversable<array-key, FacetInputInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->inputs);
    }

    public function withFacetInput(FacetKey $key, FacetInputInterface $facetInput): self
    {
        $facetInputs = $this->inputs;
        $facetInputs[$key->value] = $facetInput;

        return new self(...$facetInputs);
    }
}
