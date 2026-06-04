<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\ResponsesComponentOpenApiFactoryDecorator;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiResponsesComponentProvider;
use Shared\Tests\Unit\UnitTestCase;

class ResponsesComponentOpenApiFactoryDecoratorTest extends UnitTestCase
{
    public function testContextIsPassedToDecoratedFactory(): void
    {
        $context = ['foo' => 'bar'];
        $captured = new ArrayObject();

        $openApi = $this->createOpenApi();
        $decorated = new readonly class($openApi, $captured) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi, private ArrayObject $captured)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                $captured = $this->captured;
                $captured['context'] = $context;

                return $this->openApi;
            }
        };

        $decorator = new ResponsesComponentOpenApiFactoryDecorator($decorated, []);
        $decorator($context);

        $this->assertSame($context, $captured['context']);
    }

    public function testNoProvidersAndNoExistingResponsesReturnsOriginalOpenApi(): void
    {
        $openApi = $this->createOpenApi();
        $decorator = $this->createDecorator($openApi, providers: []);

        $result = $decorator([]);

        $this->assertSame($openApi, $result);
    }

    public function testProviderResponsesAreAddedToComponents(): void
    {
        $notFound = new Response(description: 'Not Found');
        $unauthorized = new Response(description: 'Unauthorized');

        $provider = $this->createProvider(['NotFound' => $notFound, 'Unauthorized' => $unauthorized]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$provider]);

        $result = $decorator([]);

        $responses = $result->getComponents()->getResponses();
        $this->assertInstanceOf(ArrayObject::class, $responses);
        $this->assertSame($notFound, $responses['NotFound']);
        $this->assertSame($unauthorized, $responses['Unauthorized']);
    }

    public function testExistingResponseIsNotOverwritten(): void
    {
        $existingResponse = new Response(description: 'Existing Not Found');
        $providerResponse = new Response(description: 'Provider Not Found');

        $existingResponses = new ArrayObject(['NotFound' => $existingResponse]);
        $openApi = $this->createOpenApi(responses: $existingResponses);

        $provider = $this->createProvider(['NotFound' => $providerResponse]);

        $decorator = $this->createDecorator($openApi, providers: [$provider]);

        $result = $decorator([]);

        $responses = $result->getComponents()->getResponses();
        $this->assertInstanceOf(ArrayObject::class, $responses);
        $this->assertSame($existingResponse, $responses['NotFound']);
    }

    public function testResponsesFromMultipleProvidersAreMerged(): void
    {
        $notFound = new Response(description: 'Not Found');
        $unauthorized = new Response(description: 'Unauthorized');

        $firstProvider = $this->createProvider(['NotFound' => $notFound]);
        $secondProvider = $this->createProvider(['Unauthorized' => $unauthorized]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$firstProvider, $secondProvider]);

        $result = $decorator([]);

        $responses = $result->getComponents()->getResponses();
        $this->assertInstanceOf(ArrayObject::class, $responses);
        $this->assertSame($notFound, $responses['NotFound']);
        $this->assertSame($unauthorized, $responses['Unauthorized']);
    }

    public function testFirstProviderWinsOnConflictingKey(): void
    {
        $firstResponse = new Response(description: 'First');
        $secondResponse = new Response(description: 'Second');

        $firstProvider = $this->createProvider(['NotFound' => $firstResponse]);
        $secondProvider = $this->createProvider(['NotFound' => $secondResponse]);

        $decorator = $this->createDecorator($this->createOpenApi(), providers: [$firstProvider, $secondProvider]);

        $result = $decorator([]);

        $responses = $result->getComponents()->getResponses();
        $this->assertInstanceOf(ArrayObject::class, $responses);
        $this->assertSame($firstResponse, $responses['NotFound']);
    }

    public function testNullComponentResponsesAreHandledGracefully(): void
    {
        $response = new Response(description: 'Not Found');
        $provider = $this->createProvider(['NotFound' => $response]);

        $openApi = $this->createOpenApi(responses: null);
        $decorator = $this->createDecorator($openApi, providers: [$provider]);

        $result = $decorator([]);

        $responses = $result->getComponents()->getResponses();
        $this->assertInstanceOf(ArrayObject::class, $responses);
        $this->assertSame($response, $responses['NotFound']);
    }

    /**
     * @param array<array-key,OpenApiResponsesComponentProvider> $providers
     */
    private function createDecorator(OpenApi $openApi, array $providers): ResponsesComponentOpenApiFactoryDecorator
    {
        $decorated = new readonly class($openApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        return new ResponsesComponentOpenApiFactoryDecorator($decorated, $providers);
    }

    /**
     * @param array<string,Response> $responses
     */
    private function createProvider(array $responses): OpenApiResponsesComponentProvider
    {
        return new readonly class($responses) implements OpenApiResponsesComponentProvider {
            /** @param array<string,Response> $responses */
            public function __construct(private array $responses)
            {
            }

            public function getResponses(): array
            {
                return $this->responses;
            }
        };
    }

    /**
     * @param ArrayObject<string,Response>|null $responses
     */
    private function createOpenApi(?ArrayObject $responses = null): OpenApi
    {
        return new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: new Paths(),
            components: new Components(responses: $responses),
        );
    }
}
