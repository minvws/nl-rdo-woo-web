<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Definition;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\DisplayValue\UntranslatedStringFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Service\Inquiry\InquirySessionService;
use Shared\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use Shared\Service\Search\Query\Filter\FilterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract readonly class AbstractInquiryFacet implements FacetDefinitionInterface
{
    public function __construct(
        public InquiryRepository $repository,
        private InquirySessionService $inquirySession,
    ) {
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[]', $this->getRequestParameter());
    }

    public function getFilter(): ?FilterInterface
    {
        // Intentionally no filter, this is handled in ContentAccessConditions
        return null;
    }

    public function getAggregationStrategy(): ?AggregationStrategyInterface
    {
        // Intentionally no agg. strategy: only exists for filtering
        return null;
    }

    public function getInput(ParameterBag $parameters): FacetInputInterface
    {
        return new StringValuesFacetInput(
            $this,
            $this->getValidatedInquiries($parameters),
        );
    }

    public function displayActiveSelection(int|string $key, string $value): bool
    {
        return true;
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        $inquiry = $this->repository->find($value);

        return UntranslatedStringFacetDisplayValue::fromString($inquiry ? $inquiry->getCasenr() : '');
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return null;
    }

    /**
     * @return list<string>
     */
    private function getValidatedInquiries(ParameterBag $queryParams): array
    {
        if (! $queryParams->has($this->getRequestParameter())) {
            return [];
        }

        $validInquiries = $this->inquirySession->getInquiries();
        if ($validInquiries === []) {
            return [];
        }

        $requestedInquiries = $queryParams->all()[$this->getRequestParameter()];
        $requestedInquiries = is_array($requestedInquiries) ? array_values($requestedInquiries) : [$requestedInquiries];

        /** @var string[] $validatedInquiries */
        $validatedInquiries = array_intersect($requestedInquiries, $validInquiries);
        $validatedInquiries = array_values($validatedInquiries);

        return $validatedInquiries;
    }
}
