<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search\Parameter;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use App\Api\ContextGetter;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

class SearchParameterProvider implements ParameterProviderInterface
{
    use ContextGetter;

    /**
     * @param array<string, mixed>                                                                         $parameters
     * @param array<string, mixed>|array{request?: Request, resource_class?: string, operation: Operation} $context
     */
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        unset($parameters);

        $this
            ->getRequest($context)
            ->attributes
            ->set('search_query', $this->getSearchQuery($parameter));

        return $this->getOperation($context);
    }

    private function getSearchQuery(Parameter $parameter): string
    {
        $searchQuery = $parameter->getValue();

        Assert::string($searchQuery);

        return trim($searchQuery);
    }
}
