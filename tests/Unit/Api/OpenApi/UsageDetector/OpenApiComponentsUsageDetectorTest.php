<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi\UsageDetector;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Api\OpenApi\UsageDetector\OpenApiComponentsUsageDetector;
use App\Api\OpenApi\UsageDetector\UsedComponents;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpenApiComponentsUsageDetectorTest extends UnitTestCase
{
    private OpenApi $openApi;
    private NormalizerInterface&MockInterface $normalizer;

    private OpenApiComponentsUsageDetector $detector;

    protected function setUp(): void
    {
        $this->openApi = new OpenApi(
            info: new Info(
                title: 'Test API',
                version: '1.0.0',
            ),
            servers: [],
            paths: new Paths(),
        );

        $this->normalizer = \Mockery::mock(NormalizerInterface::class);

        $this->detector = new OpenApiComponentsUsageDetector($this->normalizer);
    }

    /**
     * @param string|array<string,mixed> $normalizerOutput
     * @param array<array-key,mixed>     $expected
     */
    #[DataProvider('detectWithEmptyUsedComponentsData')]
    public function testDetectReturningEmptyUsedComponents(string|array $normalizerOutput, array $expected): void
    {
        $this->normalizer
            ->expects('normalize')
            ->with($this->openApi, 'array')
            ->andReturn($normalizerOutput);

        $result = $this->detector->detect($this->openApi);

        $this->assertSame($expected, iterator_to_array($result));
    }

    /**
     * @return array<string,array{normalizerOutput:string|array<string,mixed>,expected:array<array-key,mixed>}>
     */
    public static function detectWithEmptyUsedComponentsData(): array
    {
        return [
            'string returned' => [
                'normalizerOutput' => 'normalizerOutput',
                'expected' => self::emptyUsedComponents(),
            ],
            'empty-array returned' => [
                'normalizerOutput' => [],
                'expected' => self::emptyUsedComponents(),
            ],
            'non-empty-array returned with empty components' => [
                'normalizerOutput' => [
                    'components' => [],
                ],
                'expected' => self::emptyUsedComponents(),
            ],
            'non-empty-array returned without components' => [
                'normalizerOutput' => [
                    'paths' => [],
                ],
                'expected' => self::emptyUsedComponents(),
            ],
            'non-empty components but without paths and webhooks' => [
                'normalizerOutput' => [
                    'components' => [
                        'TestSchema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ],
                'expected' => self::emptyUsedComponents(),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $normalizerOutput
     */
    #[DataProvider('detectData')]
    public function testDetect(array $normalizerOutput, UsedComponents $expected): void
    {
        $this->normalizer
            ->expects('normalize')
            ->with($this->openApi, 'array')
            ->andReturn($normalizerOutput);

        $result = $this->detector->detect($this->openApi);

        $this->assertEquals(iterator_to_array($expected), iterator_to_array($result));
    }

    /**
     * @return array<string,array{normalizerOutput:array<string,mixed>,expected:UsedComponents}>
     */
    public static function detectData(): array
    {
        return [
            'petstore example' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => [
                        'title' => 'Swagger PetStore',
                        'licence' => [
                            'name' => 'MIT',
                        ],
                    ],
                    'servers' => [
                        ['url' => 'http://petstore.swagger.io/v1'],
                    ],
                    'security' => [
                        ['BasicAuth' => []],
                    ],
                    'paths' => [
                        'cats' => [
                            'get' => [
                                'summary' => 'List all cats',
                                'operationId' => 'listCats',
                                'tags' => ['cats'],
                                'security' => [],
                                'responses' => [
                                    '200' => [
                                        'description' => 'A paged array of cats',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Cats',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'default' => [
                                        'description' => 'unexpected error',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Error',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '/pets' => [
                            'get' => [
                                'summary' => 'List all pets',
                                'operationId' => 'listPets',
                                'tags' => ['pets'],
                                'parameters' => [
                                    [
                                        'name' => 'limit',
                                        'in' => 'query',
                                        'description' => 'How many items to return at one time (max 100)',
                                        'required' => false,
                                        'schema' => [
                                            'type' => 'integer',
                                            'maximum' => 100,
                                            'format' => 'int32',
                                        ],
                                    ],
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'A paged array of pets',
                                        'headers' => [
                                            'x-next' => [
                                                'description' => 'A link to the next page of responses',
                                                'schema' => [
                                                    'type' => 'string',
                                                ],
                                            ],
                                        ],
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Pets',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'default' => [
                                        'description' => 'unexpected error',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Error',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'post' => [
                                'summary' => 'Create a pet',
                                'operationId' => 'createPets',
                                'tags' => ['pets'],
                                'security' => [
                                    ['OAuth2' => ['admin']],
                                ],
                                'requestBody' => [
                                    'content' => [
                                        'application/json' => [
                                            'schema' => [
                                                '$ref' => '#/components/schemas/Pet',
                                            ],
                                        ],
                                    ],
                                    'required' => true,
                                ],
                                'responses' => [
                                    '201' => [
                                        'description' => 'Null response',
                                    ],
                                    'default' => [
                                        'description' => 'unexpected error',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Error',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '/pets/{petId}' => [
                            'get' => [
                                'summary' => 'Info for a specific pet',
                                'operationId' => 'showPetById',
                                'tags' => ['pets'],
                                'parameters' => [
                                    [
                                        'name' => 'petId',
                                        'in' => 'path',
                                        'required' => true,
                                        'description' => 'The id of the pet to retrieve',
                                        'schema' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'Expected response to a valid request',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Pet',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'default' => [
                                        'description' => 'unexpected error',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Error',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'Pet' => [
                                'type' => 'object',
                                'required' => ['id', 'name'],
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                        'format' => 'int64',
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'tag' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'Pets' => [
                                'type' => 'array',
                                'maxItems' => 100,
                                'items' => [
                                    '$ref' => '#/components/schemas/Pet',
                                ],
                            ],
                            'Cat' => [
                                'allOf' => [
                                    [
                                        '$ref' => '#/components/schemas/Pet',
                                    ],
                                    [
                                        '$ref' => '#/components/schemas/HuntingSkill',
                                    ],
                                ],
                            ],
                            'Cats' => [
                                'type' => 'array',
                                'maxItems' => 100,
                                'items' => [
                                    '$ref' => '#/components/schemas/Cat',
                                ],
                            ],
                            'HuntingSkill' => [
                                'type' => 'object',
                                'properties' => [
                                    'huntingSkill' => [
                                        'type' => 'string',
                                        'description' => 'The measured skill for hunting',
                                        'enum' => ['clueless', 'lazy', 'adventurous', 'aggressive', 'super'],
                                    ],
                                ],
                            ],
                            'Foobar' => [
                                'type' => 'object',
                                'properties' => [
                                    'foo' => [
                                        'type' => 'string',
                                    ],
                                    'bar' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'Error' => [
                                'type' => 'object',
                                'required' => [
                                    'code',
                                    'message',
                                ],
                                'properties' => [
                                    'code' => [
                                        'type' => 'integer',
                                        'format' => 'int32',
                                    ],
                                    'message' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                        'securitySchemes' => [
                            'BasicAuth' => [
                                'type' => 'http',
                                'scheme' => 'basic',
                            ],
                            'OAuth2' => [
                                'type' => 'oauth2',
                                'flows' => [
                                    'authorizationCode' => [
                                        'authorizationUrl' => 'https://example.com/api/oauth/authorize',
                                        'tokenUrl' => 'https://example.com/api/oauth/token',
                                        'scopes' => [
                                            'read' => 'read your pets',
                                            'write' => 'modify pets in your account',
                                            'admin' => 'access to admin operations',
                                        ],
                                    ],
                                ],
                            ],
                            'FoobarAuth' => [],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'Pet')
                    ->mark('schemas', 'Pets')
                    ->mark('schemas', 'Error')
                    ->mark('schemas', 'Cat')
                    ->mark('schemas', 'Cats')
                    ->mark('schemas', 'HuntingSkill')
                    ->mark('securitySchemes', 'OAuth2')
                    ->mark('securitySchemes', 'BasicAuth'),
            ],
            'discriminator mapping and composition' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                    ],
                    'paths' => [
                        '/animals' => [
                            'get' => [
                                'summary' => 'Get animals',
                                'operationId' => 'getAnimals',
                                'responses' => [
                                    '200' => [
                                        'description' => 'A list of animals',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/BaseSchema'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'BaseSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'string'],
                                ],
                                'discriminator' => [
                                    'propertyName' => 'type',
                                    'mapping' => [
                                        'pigeon' => '#/components/schemas/PigeonSchema',
                                        'Duck' => '#/components/schemas/DuckSchema',
                                    ],
                                ],
                            ],
                            'CanSwim' => [
                                'type' => 'object',
                                'properties' => [
                                    'canSwim' => ['type' => 'boolean'],
                                ],
                            ],
                            'PigeonSchema' => [
                                'allOf' => [
                                    ['$ref' => '#/components/schemas/BaseSchema'],
                                    ['$ref' => '#/components/schemas/CanSwim'],
                                ],
                                'properties' => ['pigeonProp' => ['type' => 'string']],
                            ],
                            'DuckSchema' => [
                                'allOf' => [
                                    ['$ref' => '#/components/schemas/BaseSchema'],
                                    [
                                        'type' => 'object',
                                        'properties' => [
                                            'duckProp' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'BaseSchema')
                    ->mark('schemas', 'CanSwim')
                    ->mark('schemas', 'PigeonSchema')
                    ->mark('schemas', 'DuckSchema'),
            ],
            'single schema keywords (contains, if/then/else)' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                    ],
                    'paths' => [
                        '/wrapped' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'ok',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/ParentArray',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '/conditional' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'ok',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/ConditionalWrapper',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'ParentArray' => [
                                'type' => 'array',
                                'contains' => [
                                    '$ref' => '#/components/schemas/ChildItem',
                                ],
                            ],
                            'ChildItem' => [
                                'type' => 'object',
                                'properties' => [
                                    'value' => ['type' => 'string'],
                                ],
                            ],
                            'AltChildItem' => [
                                'type' => 'object',
                                'properties' => [
                                    'alt' => ['type' => 'string'],
                                ],
                            ],
                            'ConditionalWrapper' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['type' => 'string'],
                                ],
                                'if' => [
                                    'properties' => [
                                        'type' => ['const' => 'primary'],
                                    ],
                                ],
                                'then' => [
                                    '$ref' => '#/components/schemas/ChildItem',
                                ],
                                'else' => [
                                    '$ref' => '#/components/schemas/AltChildItem',
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'ParentArray')
                    ->mark('schemas', 'ChildItem')
                    ->mark('schemas', 'ConditionalWrapper')
                    ->mark('schemas', 'AltChildItem'),
            ],
            'prefixItems and additionalProperties' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                    ],
                    'paths' => [
                        '/tuples' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'tuple wrapper',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/TupleWrapper',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '/maps' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'map wrapper',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/MapWrapper',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'TupleWrapper' => [
                                'type' => 'array',
                                'prefixItems' => [
                                    ['$ref' => '#/components/schemas/FirstItem'],
                                    ['$ref' => '#/components/schemas/SecondItem'],
                                ],
                                'items' => false,
                            ],
                            'FirstItem' => [
                                'type' => 'object',
                                'properties' => [
                                    'a' => ['type' => 'string'],
                                ],
                            ],
                            'SecondItem' => [
                                'type' => 'object',
                                'properties' => [
                                    'b' => ['type' => 'integer'],
                                ],
                            ],
                            'MapWrapper' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    '$ref' => '#/components/schemas/ValueSchema',
                                ],
                            ],
                            'ValueSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'value' => ['type' => 'number'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'TupleWrapper')
                    ->mark('schemas', 'FirstItem')
                    ->mark('schemas', 'SecondItem')
                    ->mark('schemas', 'MapWrapper')
                    ->mark('schemas', 'ValueSchema'),
            ],
            'map-of-schemas keywords (patternProperties, dependentSchemas, $defs)' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                    ],
                    'paths' => [
                        '/map' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'Map container',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/MapContainer',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'MapContainer' => [
                                'type' => 'object',
                                'patternProperties' => [
                                    '^x-' => [
                                        '$ref' => '#/components/schemas/PatternValue',
                                    ],
                                ],
                                'dependentSchemas' => [
                                    'extra' => [
                                        '$ref' => '#/components/schemas/MapContainer/$defs/internalDef',
                                    ],
                                ],
                                '$defs' => [
                                    'internalDef' => [
                                        '$ref' => '#/components/schemas/DefsValue',
                                    ],
                                ],
                            ],
                            'PatternValue' => [
                                'type' => 'object',
                                'properties' => [
                                    'p' => ['type' => 'string'],
                                ],
                            ],
                            'DependentValue' => [
                                'type' => 'object',
                                'properties' => [
                                    'd' => ['type' => 'integer'],
                                ],
                            ],
                            'DefsValue' => [
                                'type' => 'object',
                                'properties' => [
                                    'v' => ['type' => 'boolean'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'MapContainer')
                    ->mark('schemas', 'PatternValue')
                    ->mark('schemas', 'DefsValue'),
            ],
            'deep $ref into $defs sub-schema' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                    'paths' => [
                        '/deep' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'Deep ref',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Wrapper/$defs/Inner',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'Wrapper' => [
                                'type' => 'object',
                                '$defs' => [
                                    'Inner' => [
                                        '$ref' => '#/components/schemas/Core',
                                    ],
                                ],
                            ],
                            'Core' => [
                                'type' => 'object',
                                'properties' => [
                                    'x' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'Wrapper')
                    ->mark('schemas', 'Core'),
            ],
            'direct boolean schema reference' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                    'paths' => [
                        '/direct-bool' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'ok',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/AlwaysTrue',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'AlwaysTrue' => true,
                            'UnusedFalse' => false,
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'AlwaysTrue'),
            ],
            'deep json pointer with escaped slash (~1)' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                    'paths' => [
                        '/escaped' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'Escaped slash pointer',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Wrapper/$defs/TupleItems/1',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'Wrapper' => [
                                'type' => 'object',
                                '$defs' => [
                                    'TupleItems' => [
                                        ['$ref' => '#/components/schemas/ItemA'],
                                        ['$ref' => '#/components/schemas/ItemC/$defs/With~1Slash'],
                                    ],
                                ],
                            ],
                            'ItemA' => [
                                'type' => 'object',
                                'properties' => [
                                    'a' => ['type' => 'string'],
                                ],
                            ],
                            'ItemB' => [
                                'type' => 'object',
                                'properties' => [
                                    'b' => ['type' => 'integer'],
                                ],
                            ],
                            'ItemC' => [
                                'type' => 'object',
                                '$defs' => [
                                    'With/Slash' => [
                                        '$ref' => '#/components/schemas/ItemB',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'Wrapper')
                    ->mark('schemas', 'ItemC')
                    ->mark('schemas', 'ItemB')
                    ->mark('schemas', 'ItemA'),
            ],
            'composition anyOf / oneOf / not' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => ['title' => 'Test', 'version' => '1'],
                    'paths' => [
                        '/compose' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'ok',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/WrapperAnyOf',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'WrapperAnyOf' => [
                                'anyOf' => [
                                    ['$ref' => '#/components/schemas/VariantA'],
                                    ['$ref' => '#/components/schemas/VariantB'],
                                ],
                                'not' => [
                                    '$ref' => '#/components/schemas/Excluded',
                                ],
                                'oneOf' => [
                                    ['$ref' => '#/components/schemas/VariantC'],
                                ],
                            ],
                            'VariantA' => ['type' => 'object'],
                            'VariantB' => ['type' => 'object'],
                            'VariantC' => ['type' => 'object'],
                            'Excluded' => ['type' => 'object'],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'WrapperAnyOf')
                    ->mark('schemas', 'VariantA')
                    ->mark('schemas', 'VariantB')
                    ->mark('schemas', 'VariantC')
                    ->mark('schemas', 'Excluded'),
            ],
            'circular self reference' => [
                'normalizerOutput' => [
                    'openapi' => '3.1.0',
                    'info' => ['title' => 'Test', 'version' => '1'],
                    'paths' => [
                        '/circular' => [
                            'get' => [
                                'responses' => [
                                    '200' => [
                                        'description' => 'ok',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    '$ref' => '#/components/schemas/Node',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'components' => [
                        'schemas' => [
                            'Node' => [
                                'type' => 'object',
                                'properties' => [
                                    'next' => [
                                        '$ref' => '#/components/schemas/Node',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => UsedComponents::new()
                    ->mark('schemas', 'Node'),
            ],
        ];
    }

    /**
     * @return array<value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS>,array<string,true>>
     */
    public static function emptyUsedComponents(): array
    {
        return iterator_to_array(UsedComponents::new());
    }

    public function testItGracefullyHandlesBrokenComponentReference(): void
    {
        $openApiData = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'A test response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/NonExistentSchema',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    // Note that 'NonExistentSchema' is not defined here.
                    'ExistingSchema' => ['type' => 'string'],
                ],
            ],
        ];

        $this->normalizer
            ->expects('normalize')
            ->with($this->openApi, 'array')
            ->andReturn($openApiData);

        $usedComponents = $this->detector->detect($this->openApi);

        self::assertArrayNotHasKey('NonExistentSchema', $usedComponents['schemas']);
    }

    public function testItHandlesBrokenDeepComponentReference(): void
    {
        $openApiData = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'A test response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/DeepRefTarget/properties/nonExistentProp',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'DeepRefTarget' => [
                        'type' => 'object',
                        'properties' => [
                            'existingProp' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $this->normalizer
            ->expects('normalize')
            ->with($this->openApi, 'array')
            ->andReturn($openApiData);

        $usedComponents = $this->detector->detect($this->openApi);

        self::assertArrayHasKey('DeepRefTarget', $usedComponents['schemas']);
        self::assertArrayNotHasKey('nonExistentProp', $usedComponents['schemas']);
    }
}
