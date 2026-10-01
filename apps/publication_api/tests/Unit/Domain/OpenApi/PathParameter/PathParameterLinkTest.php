<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\PathParameter;

use ApiPlatform\Metadata\Link;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Domain\OpenApi\Metadata\ExternalIdOpenApiSchema;
use PublicationApi\Domain\OpenApi\Metadata\UuidOpenApiSchema;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DepartmentIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DocumentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\SubjectIdLink;
use Shared\Tests\Unit\UnitTestCase;

final class PathParameterLinkTest extends UnitTestCase
{
    /**
     * @param class-string<Link> $linkClass
     * @param array{type: string, format: string} $expectedSchema
     */
    #[DataProvider('linkDataProvider')]
    public function testItExposesTheExpectedMetadata(
        string $linkClass,
        string $expectedDescription,
        array $expectedSchema,
    ): void {
        $link = new $linkClass();

        self::assertInstanceOf(Link::class, $link);
        self::assertSame($expectedDescription, $link->getDescription());
        self::assertSame($expectedSchema, $link->getSchema());
    }

    public function testSubjectIdLinkReturnsTheIdIdentifier(): void
    {
        $link = new SubjectIdLink();

        self::assertSame(['id'], $link->getIdentifiers());
    }

    /**
     * @return iterable<string, array{
     *     class-string<Link>,
     *     string,
     *     array{type: string, format: string}
     * }>
     */
    public static function linkDataProvider(): iterable
    {
        yield 'organisation ID' => [
            OrganisationIdLink::class,
            'The unique identifier of the organisation.',
            UuidOpenApiSchema::SCHEMA,
        ];

        yield 'department ID' => [
            DepartmentIdLink::class,
            'The unique identifier of the department.',
            UuidOpenApiSchema::SCHEMA,
        ];

        yield 'subject ID' => [
            SubjectIdLink::class,
            'The unique identifier of the subject.',
            UuidOpenApiSchema::SCHEMA,
        ];

        yield 'dossier external ID' => [
            DossierExternalIdLink::class,
            'The external identifier of the dossier, as supplied by the source system.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];

        yield 'document external ID' => [
            DocumentExternalIdLink::class,
            'The external identifier of the document, unique within the organisation.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];

        yield 'attachment external ID' => [
            AttachmentExternalIdLink::class,
            'The external identifier of the attachment, unique within the dossier.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];
    }
}
