<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiResponsesComponentProvider;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class ResponsesComponentOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    /**
     * @param iterable<array-key,OpenApiResponsesComponentProvider> $responsesProviders
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[AutowireIterator('publication_api.open_api.responses_component_provider')]
        private iterable $responsesProviders,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        $responses = $components->getResponses() ?? new ArrayObject();

        $newResponses = $this->mergeResponses($responses);

        return $newResponses->count() === 0
            ? $openApi
            : $openApi->withComponents($components->withResponses($newResponses));
    }

    /**
     * @param ArrayObject<string,Response> $responses
     *
     * @return ArrayObject<string,Response>
     */
    private function mergeResponses(ArrayObject $responses): ArrayObject
    {
        foreach ($this->responsesProviders as $provider) {
            $providedResponses = $provider->getResponses();

            foreach ($providedResponses as $key => $response) {
                if (isset($responses[$key])) {
                    continue;
                }

                $responses[$key] = $response;
            }
        }

        return $responses;
    }
}
