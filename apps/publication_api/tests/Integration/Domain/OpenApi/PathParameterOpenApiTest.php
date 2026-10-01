<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use PublicationApi\Domain\OpenApi\Metadata\ExternalIdOpenApiSchema;
use PublicationApi\Domain\OpenApi\Metadata\UuidOpenApiSchema;
use PublicationApi\Tests\Integration\PublicationWebTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function array_diff;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function preg_match_all;
use function sprintf;
use function strtoupper;

final class PathParameterOpenApiTest extends PublicationWebTestCase
{
    private const array HTTP_METHODS = ['get', 'put'];

    /**
     * @var array<string, array{
     *     description: string,
     *     schema: array{type: string, format: string, minLength?: int, maxLength?: int, pattern?: string},
     * }>
     */
    private const array EXPECTED_PATH_PARAMETER_METADATA = [
        'organisationId' => [
            'description' => 'The unique identifier of the organisation.',
            'schema' => UuidOpenApiSchema::SCHEMA,
        ],
        'departmentId' => [
            'description' => 'The unique identifier of the department.',
            'schema' => UuidOpenApiSchema::SCHEMA,
        ],
        'subjectId' => [
            'description' => 'The unique identifier of the subject.',
            'schema' => UuidOpenApiSchema::SCHEMA,
        ],
        'dossierExternalId' => [
            'description' => 'The external identifier of the dossier, as supplied by the source system.',
            'schema' => ExternalIdOpenApiSchema::SCHEMA,
        ],
        'documentExternalId' => [
            'description' => 'The external identifier of the document, unique within the organisation.',
            'schema' => ExternalIdOpenApiSchema::SCHEMA,
        ],
        'attachmentExternalId' => [
            'description' => 'The external identifier of the attachment, unique within the dossier.',
            'schema' => ExternalIdOpenApiSchema::SCHEMA,
        ],
    ];

    public function testPathParametersHaveExpectedMetadata(): void
    {
        $spec = $this->createOpenApiSpec();
        $expected = [];
        $actual = [];
        $usedNames = [];

        foreach ($spec['paths'] as $path => $pathItem) {
            self::assertIsArray($pathItem, sprintf('Path item "%s" must be an object.', $path));
            $operations = array_intersect_key($pathItem, array_flip(self::HTTP_METHODS));

            foreach ($operations as $method => $operation) {
                self::assertIsArray(
                    $operation,
                    sprintf('Operation for %s %s must be an object.', strtoupper($method), $path),
                );

                $parameters = $operation['parameters'] ?? [];
                self::assertIsArray(
                    $parameters,
                    sprintf('Parameters for %s %s must be an array.', strtoupper($method), $path),
                );

                $pathParameterNames = [];
                foreach ($parameters as $parameter) {
                    self::assertIsArray(
                        $parameter,
                        sprintf('Parameter for %s %s must be an object.', strtoupper($method), $path),
                    );
                    self::assertArrayNotHasKey(
                        '$ref',
                        $parameter,
                        sprintf(
                            'Parameter for %s %s must be resolved before checking metadata.',
                            strtoupper($method),
                            $path,
                        ),
                    );

                    if (($parameter['in'] ?? null) !== 'path') {
                        continue;
                    }

                    $name = $parameter['name'] ?? null;
                    self::assertIsString(
                        $name,
                        sprintf('Path parameter for %s %s must have a name.', strtoupper($method), $path),
                    );

                    $key = sprintf('%s %s {%s}', strtoupper($method), $path, $name);
                    $actual[$key] = [
                        'description' => $parameter['description'] ?? null,
                        'schema' => $parameter['schema'] ?? null,
                    ];
                    $expected[$key] = self::EXPECTED_PATH_PARAMETER_METADATA[$name] ?? null;
                    $usedNames[$name] = true;
                    $pathParameterNames[] = $name;
                }

                $matches = [];
                preg_match_all('/\{(\w+)}/', $path, $matches);
                foreach ($matches[1] as $placeholder) {
                    self::assertContains(
                        $placeholder,
                        $pathParameterNames,
                        sprintf(
                            'Path placeholder "%s" is not declared for %s %s.',
                            $placeholder,
                            strtoupper($method),
                            $path,
                        ),
                    );
                }
            }
        }

        self::assertNotEmpty($actual, 'The generated OpenAPI document contains no path parameters.');
        self::assertSame(
            [],
            array_diff(array_keys(self::EXPECTED_PATH_PARAMETER_METADATA), array_keys($usedNames)),
            'Every path parameter in EXPECTED_PATH_PARAMETER_METADATA must be used by at least one route.',
        );
        self::assertEquals(
            $expected,
            $actual,
            'Generated path parameter descriptions and schemas must match EXPECTED_PATH_PARAMETER_METADATA.',
        );
    }

    /**
     * @return array{paths: array<string, array<string, mixed>>}
     */
    private function createOpenApiSpec(): array
    {
        $openApiFactory = self::fromContainer(OpenApiFactoryInterface::class);
        $normalizer = self::fromContainer(NormalizerInterface::class);

        $spec = $normalizer->normalize(
            $openApiFactory(),
            'json',
            ['spec_version' => '3'],
        );
        self::assertIsArray($spec);
        self::assertIsArray($spec['paths'] ?? null, 'The OpenAPI document must contain a paths object.');

        /** @var array{paths: array<string, array<string, mixed>>} $spec */
        return $spec;
    }
}
