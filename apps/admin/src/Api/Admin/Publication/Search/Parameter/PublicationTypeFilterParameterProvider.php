<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Publication\Search\Parameter;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use PublicationApi\Api\Publication\ContextGetter;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Webmozart\Assert\Assert;

use function trim;

class PublicationTypeFilterParameterProvider implements ParameterProviderInterface
{
    use ContextGetter;

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed>|array{request?: Request, resource_class?: string, operation: Operation} $context
     */
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        unset($parameters);

        $this
            ->getRequest($context)
            ->attributes
            ->set('publicationTypeQuery', $this->getTypeQuery($parameter));

        return $this->getOperation($context);
    }

    private function getTypeQuery(Parameter $parameter): DossierType
    {
        $typeQuery = $parameter->getValue();

        Assert::string($typeQuery);

        return DossierType::from(trim($typeQuery));
    }
}
