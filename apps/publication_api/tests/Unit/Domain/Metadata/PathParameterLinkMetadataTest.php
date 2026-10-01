<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Department\DepartmentResource;
use PublicationApi\Api\Dossier\Advice\AdviceResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Attachment\WooDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\WooDecisionUploadDocumentResource;
use PublicationApi\Api\Organisation\OrganisationResource;
use PublicationApi\Api\Subject\SubjectResource;
use PublicationApi\Domain\OpenApi\Metadata\ExternalIdOpenApiSchema;
use PublicationApi\Domain\OpenApi\Metadata\UuidOpenApiSchema;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DepartmentIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DocumentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\SubjectIdLink;
use Shared\Tests\Unit\UnitTestCase;

use function str_contains;

final class PathParameterLinkMetadataTest extends UnitTestCase
{
    /**
     * @param class-string $resourceClass
     * @param class-string<Link> $expectedLinkClass
     * @param array{type: string, format: string, minLength?: int, maxLength?: int, pattern?: string} $expectedSchema
     */
    #[DataProvider('pathParameterDataProvider')]
    public function testResourceDeclaresMetadataOnThePathParameterLink(
        string $resourceClass,
        string $parameterName,
        string $expectedLinkClass,
        string $expectedDescription,
        array $expectedSchema,
    ): void {
        $resourceMetadataCollection = new AttributesResourceMetadataCollectionFactory()->create($resourceClass);
        $resource = $resourceMetadataCollection[0] ?? null;
        self::assertInstanceOf(ApiResource::class, $resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $matchingOperation = null;
        foreach ($operations as $operation) {
            if (
                $operation instanceof HttpOperation
                && str_contains((string) $operation->getUriTemplate(), '{' . $parameterName . '}')
            ) {
                $matchingOperation = $operation;
                break;
            }
        }

        self::assertInstanceOf(HttpOperation::class, $matchingOperation);
        $uriVariables = $matchingOperation->getUriVariables();
        self::assertIsArray($uriVariables);

        $link = $uriVariables[$parameterName] ?? null;
        self::assertInstanceOf($expectedLinkClass, $link);
        self::assertSame($expectedDescription, $link->getDescription());
        self::assertSame($expectedSchema, $link->getSchema());
    }

    /**
     * @return iterable<string, array{
     *     class-string,
     *     string,
     *     class-string<Link>,
     *     string,
     *     array{type: string, format: string, minLength?: int, maxLength?: int, pattern?: string}
     * }>
     */
    public static function pathParameterDataProvider(): iterable
    {
        yield 'organisation UUID' => [
            OrganisationResource::class,
            'organisationId',
            OrganisationIdLink::class,
            'The unique identifier of the organisation.',
            UuidOpenApiSchema::SCHEMA,
        ];
        yield 'department UUID' => [
            DepartmentResource::class,
            'departmentId',
            DepartmentIdLink::class,
            'The unique identifier of the department.',
            UuidOpenApiSchema::SCHEMA,
        ];
        yield 'subject UUID' => [
            SubjectResource::class,
            'subjectId',
            SubjectIdLink::class,
            'The unique identifier of the subject.',
            UuidOpenApiSchema::SCHEMA,
        ];
        yield 'dossier external ID' => [
            AdviceResource::class,
            'dossierExternalId',
            DossierExternalIdLink::class,
            'The external identifier of the dossier, as supplied by the source system.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];
        yield 'document external ID' => [
            WooDecisionUploadDocumentResource::class,
            'documentExternalId',
            DocumentExternalIdLink::class,
            'The external identifier of the document, unique within the organisation.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];
        yield 'attachment external ID' => [
            WooDecisionUploadAttachmentResource::class,
            'attachmentExternalId',
            AttachmentExternalIdLink::class,
            'The external identifier of the attachment, unique within the dossier.',
            ExternalIdOpenApiSchema::SCHEMA,
        ];
    }
}
