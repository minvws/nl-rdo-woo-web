<?php

declare(strict_types=1);

namespace PublicationApi\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Webmozart\Assert\Assert;

#[AsDecorator('api_platform.state.error_provider')]
final readonly class ProblemDetailsErrorProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $inner,
        private ProblemDetailsFactory $problemDetailsFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $error = $this->inner->provide($operation, $uriVariables, $context);
        Assert::isInstanceOf($error, Error::class);

        $original = $error->getPrevious();
        $problemDetails = $original !== null ? $this->problemDetailsFactory->build($original) : null;

        if ($problemDetails !== null) {
            $error->setType($problemDetails->type);
            $error->setTitle($problemDetails->title);
        } else {
            $error->setType('about:blank');
        }

        return $error;
    }
}
