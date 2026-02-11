<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Domain\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Decorator\AttachmentTypeDescriptionDecorator;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class AttachmentTypeDescriptionDecoratorTest extends PublicationApiTestCase
{
    public function testDecoratorAddsEnumDescriptionsToAttachmentTypeInRealOpenApi(): void
    {
        /** @var ArrayObject<string, Schema>|ArrayObject<string, Reference> $schemas */
        $schemas = new ArrayObject([
            'AttachmentRequestDto' => new ArrayObject([
                'properties' => new ArrayObject([
                    'type' => new ArrayObject([
                        'type' => 'string',
                        'class' => 'AttachmentType',
                        'enum' => [
                            AttachmentType::ADVICE->value,
                            AttachmentType::REQUEST_FOR_ADVICE->value,
                            AttachmentType::POLICY_NOTE->value,
                        ],
                    ]),
                ]),
            ]),
        ]);

        $components = new Components(schemas: $schemas);
        $testOpenApi = new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: new Paths(),
            components: $components,
        );

        // Create a mock factory that returns our test OpenAPI
        $mockFactory = new readonly class($testOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $translator = self::getContainer()->get('translator');
        Assert::isInstanceOf($translator, TranslatorInterface::class);

        $decorator = new AttachmentTypeDescriptionDecorator($mockFactory, $translator);
        $result = $decorator();

        $resultSchemas = $result->getComponents()->getSchemas();
        Assert::notNull($resultSchemas);

        $attachmentSchema = $resultSchemas['AttachmentRequestDto'];
        if ($attachmentSchema instanceof ArrayObject) {
            $attachmentSchema = $attachmentSchema->getArrayCopy();
        }
        Assert::isArray($attachmentSchema);
        Assert::keyExists($attachmentSchema, 'properties');

        $properties = $attachmentSchema['properties'];
        if ($properties instanceof ArrayObject) {
            $properties = $properties->getArrayCopy();
        }
        Assert::isArray($properties);
        Assert::keyExists($properties, 'type');

        $typeProperty = $properties['type'];
        if ($typeProperty instanceof ArrayObject) {
            $typeProperty = $typeProperty->getArrayCopy();
        }
        Assert::isArray($typeProperty);

        self::assertArrayHasKey('x-enum-varnames', $typeProperty, 'x-enum-varnames should be added');
        $enumVarnames = $typeProperty['x-enum-varnames'];
        self::assertIsArray($enumVarnames);
        self::assertCount(3, $enumVarnames);

        foreach ($enumVarnames as $varname) {
            self::assertIsString($varname);
            self::assertNotEmpty($varname);
        }

        self::assertArrayNotHasKey('class', $typeProperty, 'class attribute should be removed after decoration');

        self::assertArrayHasKey('enum', $typeProperty);
        $enumValues = $typeProperty['enum'];
        self::assertIsArray($enumValues);
        self::assertCount(3, $enumValues);
        self::assertContains(AttachmentType::ADVICE->value, $enumValues);
        self::assertContains(AttachmentType::REQUEST_FOR_ADVICE->value, $enumValues);
        self::assertContains(AttachmentType::POLICY_NOTE->value, $enumValues);
    }

    public function testDecoratorDontEnumDescriptionsToNonAttachmentTypeInRealOpenApi(): void
    {
        /** @var ArrayObject<string, Schema>|ArrayObject<string, Reference> $schemas */
        $schemas = new ArrayObject([
            'AttachmentRequestDto' => new ArrayObject([
                'properties' => new ArrayObject([
                    'type' => new ArrayObject([
                        'type' => 'string',
                        // 'class' => 'AttachmentType', // Simulate missing class attribute
                        'enum' => [
                            AttachmentType::ADVICE->value,
                            AttachmentType::REQUEST_FOR_ADVICE->value,
                            AttachmentType::POLICY_NOTE->value,
                        ],
                    ]),
                ]),
            ]),
        ]);

        $components = new Components(schemas: $schemas);
        $testOpenApi = new OpenApi(
            info: new Info(title: 'Test API', version: '1.0.0'),
            servers: [],
            paths: new Paths(),
            components: $components,
        );

        // Create a mock factory that returns our test OpenAPI
        $mockFactory = new readonly class($testOpenApi) implements OpenApiFactoryInterface {
            public function __construct(private readonly OpenApi $openApi)
            {
            }

            public function __invoke(array $context = []): OpenApi
            {
                return $this->openApi;
            }
        };

        $translator = self::getContainer()->get('translator');
        Assert::isInstanceOf($translator, TranslatorInterface::class);

        $decorator = new AttachmentTypeDescriptionDecorator($mockFactory, $translator);
        $result = $decorator();

        $resultSchemas = $result->getComponents()->getSchemas();
        Assert::notNull($resultSchemas);

        $attachmentSchema = $resultSchemas['AttachmentRequestDto'];
        if ($attachmentSchema instanceof ArrayObject) {
            $attachmentSchema = $attachmentSchema->getArrayCopy();
        }
        Assert::isArray($attachmentSchema);
        Assert::keyExists($attachmentSchema, 'properties');

        $properties = $attachmentSchema['properties'];
        if ($properties instanceof ArrayObject) {
            $properties = $properties->getArrayCopy();
        }
        Assert::isArray($properties);
        Assert::keyExists($properties, 'type');

        $typeProperty = $properties['type'];
        if ($typeProperty instanceof ArrayObject) {
            $typeProperty = $typeProperty->getArrayCopy();
        }
        Assert::isArray($typeProperty);

        self::assertArrayNotHasKey('x-enum-varnames', $typeProperty, 'x-enum-varnames should not be added');

        self::assertArrayHasKey('enum', $typeProperty);
        $enumValues = $typeProperty['enum'];
        self::assertIsArray($enumValues);
        self::assertCount(3, $enumValues);
        self::assertContains(AttachmentType::ADVICE->value, $enumValues);
        self::assertContains(AttachmentType::REQUEST_FOR_ADVICE->value, $enumValues);
        self::assertContains(AttachmentType::POLICY_NOTE->value, $enumValues);
    }
}
