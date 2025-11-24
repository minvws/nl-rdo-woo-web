<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Publication\V1\Subject;

use Shared\Api\Publication\V1\Subject\SubjectDto;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\Api\Publication\V1\ApiPublicationV1TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SubjectPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGet(): void
    {
        $subject = SubjectFactory::createOne()->_real();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
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
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $subject = SubjectFactory::createOne()->_real();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $subject = SubjectFactory::createOne()->_real();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSubjectParameter(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/invalid', $organisation->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);
    }

    public function testGetWithInvalidOrganisationParameter(): void
    {
        $subject = SubjectFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/invalid/subject/%s', $subject->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);
    }

    public function testGetWithOtherOrganisation(): void
    {
        $subject = SubjectFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);
    }

    public function testGetCollection(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        $subjectCount = $this->getFaker()->numberBetween(1, 3);
        SubjectFactory::createMany($subjectCount, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectDto::class);
        self::assertCount($subjectCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        SubjectFactory::createMany(5, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectDto::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $cursorSubject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        SubjectFactory::new(['organisation' => $organisation])->create()->_real();

        $requestParameters = \sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            \base64_encode((string) \json_encode(['id' => (string) $cursorSubject->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject?%s', $organisation->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectDto::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        $subject = SubjectFactory::new()->create()->_real();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject?%s', $subject->getOrganisation()->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SubjectDto::class);
        self::assertCount(1, $response->toArray());
    }

    public function testCreateSubject(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $name = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'name' => $name,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(1, $response->toArray());
    }

    public function testCreateSubjectWithInvalidName(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $name = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'name' => [$name],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                \sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId()),
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

        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => $name,
        ])->_real();

        $data = [
            'name' => $newName,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_PUT,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
            );
        self::assertSame($newName, $response->toArray()['name']);
    }

    public function testUpdateSubjectWithInvalidName(): void
    {
        $name = $this->getFaker()->unique()->word();

        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => $name,
        ])->_real();

        $data = [
            'name' => ['invalid'],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_PUT,
                \sprintf('/api/publication/v1/organisation/%s/subject/%s', $organisation->getId(), $subject->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(SubjectDto::class);
    }
}
