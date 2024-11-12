<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search\Parameter;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;
use App\Api\ContextGetter;
use App\Domain\Publication\Dossier\Type\DossierType;
use Webmozart\Assert\Assert;

class TypeFilterParameterProvider implements ParameterProviderInterface
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
            ->set('type_query', $this->getTypeQuery($parameter));

        return $this->getOperation($context);
    }

    private function getTypeQuery(Parameter $parameter): DossierType
    {
        $typeQuery = $parameter->getValue();

        Assert::string($typeQuery);

        return DossierType::from(trim($typeQuery));
    }
}
