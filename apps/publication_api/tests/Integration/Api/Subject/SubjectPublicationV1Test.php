<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Subject;

use PublicationApi\Api\Subject\SubjectResource;
use PublicationApi\EventSubscriber\ApiVersionHeaderSubscriber;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class SubjectPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetSubject(): void
    {
        $subject = SubjectFactory::createOne();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $subject->getId(),
            'organisation' => [
                'id' => (string) $subject->getOrganisation()->getId(),
                'name' => $subject->getOrganisation()->getName(),
            ],
            'name' => $subject->getName(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);

        $apiVersion = self::getContainer()->getParameter('api_platform.version');

        self::assertIsString($apiVersion);
        self::assertResponseHeaderSame(ApiVersionHeaderSubscriber::HEADER_NAME, $apiVersion);
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $subject = SubjectFactory::createOne();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $subject = SubjectFactory::createOne();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSubjectParameter(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/invalid', $organisation->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => 'Subject with id invalid was not found',
        ]);
    }

    public function testGetWithInvalidOrganisationParameter(): void
    {
        $subject = SubjectFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/invalid/subject/%s', $subject->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => 'Invalid identifier value or configuration.',
        ]);
    }

    public function testGetWithUnknownOrganisation(): void
    {
        $organisationId = self::getFaker()->uuid();
        $subject = SubjectFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisationId, $subject->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Organisation with id %s was not found', $organisationId),
        ]);
    }

    public function testGetWithUnknownSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subjectId = self::getFaker()->uuid();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subjectId),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Subject with id %s was not found', $subjectId),
        ]);
    }

    public function testGetWithOtherOrganisation(): void
    {
        $subject = SubjectFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
    }

    public function testGetCollection(): void
    {
        $organisation = OrganisationFactory::createOne();

        $subjectCount = $this->getFaker()->numberBetween(1, 3);
        SubjectFactory::createMany($subjectCount, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectResource::class);
        self::assertCount($subjectCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        $organisation = OrganisationFactory::createOne();

        SubjectFactory::createMany(5, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectResource::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        $organisation = OrganisationFactory::createOne();

        SubjectFactory::new(['organisation' => $organisation])->create();
        SubjectFactory::new(['organisation' => $organisation])->create();
        $cursorSubject = SubjectFactory::new(['organisation' => $organisation])->create();
        SubjectFactory::new(['organisation' => $organisation])->create();
        SubjectFactory::new(['organisation' => $organisation])->create();

        $requestParameters = sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            base64_encode((string) json_encode(['id' => (string) $cursorSubject->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject?%s', $organisation->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectResource::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        $subject = SubjectFactory::new()->create();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject?%s', $subject->getOrganisation()->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectResource::class);
        self::assertCount(1, $response->toArray());
    }

    public function testCreateSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $name = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'name' => $name,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(1, $response->toArray());
    }

    public function testCreateSubjectWithInvalidName(): void
    {
        $organisation = OrganisationFactory::createOne();
        $name = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'name' => [$name],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateSubject(): void
    {
        $name = $this->getFaker()->unique()->word();
        $newName = $this->getFaker()->unique()->word();

        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => $name,
        ]);

        $data = [
            'name' => $newName,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_PUT,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
            );
        self::assertSame($newName, $response->toArray()['name']);
    }

    public function testUpdateSubjectWithInvalidName(): void
    {
        $name = $this->getFaker()->unique()->word();

        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => $name,
        ]);

        $data = [
            'name' => ['invalid'],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_PUT,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
    }

    // test bug https://github.com/minvws/nl-rdo-woo-web-private/issues/6919
    public function testCreateSubjectWithDuplicateNameInSameOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $name = $this->getFaker()->unique()->word();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
                ['json' => ['name' => $name]],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
                ['json' => ['name' => $name]],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testDeleteUnusedSubjectReturns204(): void
    {
        $subject = SubjectFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_DELETE,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteSubjectInUseReturns405(): void
    {
        $subject = SubjectFactory::createOne();
        WooDecisionFactory::createOne(['subject' => $subject]);

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_DELETE,
                sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
        self::assertJsonEquals([
            'type' => 'errors#resource-in-use',
            'title' => 'Method Not Allowed',
            'status' => Response::HTTP_METHOD_NOT_ALLOWED,
            'detail' => 'Resource is still linked to one or more dossiers and cannot be deleted',
        ]);
    }
}
