<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Publication\Search\Parameter;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use Shared\Api\ContextGetter;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

class DossierIdParameterProvider implements ParameterProviderInterface
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
            ->set('dossierIdQuery', $this->getDossierQuery($parameter));

        return $this->getOperation($context);
    }

    private function getDossierQuery(Parameter $parameter): Uuid
    {
        $dossierQuery = $parameter->getValue();

        Assert::string($dossierQuery);

        return Uuid::fromString(trim($dossierQuery));
    }
}
