<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Subject;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Subject\SubjectLandingPageInputDto;
use PublicationApi\Api\Subject\SubjectResource;
use PublicationApi\Api\Subject\SubjectUpdateDto;
use PublicationApi\EventSubscriber\ApiVersionHeaderSubscriber;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

use function array_column;
use function array_fill;
use function array_replace_recursive;
use function base64_encode;
use function json_encode;
use function sprintf;
use function str_repeat;

final class SubjectPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testUpdateDtoDeserializesNestedLandingPage(): void
    {
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $data = $serializer->deserialize(
            (string) json_encode([
                'name' => 'Subject',
                'landingPage' => $this->landingPagePayload(),
            ]),
            SubjectUpdateDto::class,
            'json',
            [],
        );

        self::assertInstanceOf(SubjectUpdateDto::class, $data);
        self::assertInstanceOf(SubjectLandingPageInputDto::class, $data->landingPage);
        self::assertInstanceOf(LandingPageSlug::class, $data->landingPage->slug);
        self::assertInstanceOf(LandingPageTitle::class, $data->landingPage->title);
        self::assertSame('foo-bar', (string) $data->landingPage->slug);
        self::assertSame('Pagina titel', (string) $data->landingPage->title);
    }

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
            'landingPage' => null,
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);

        $apiVersion = self::getContainer()->getParameter('api_platform.version');

        self::assertIsString($apiVersion);
        self::assertResponseHeaderSame(ApiVersionHeaderSubscriber::HEADER_NAME, $apiVersion);
    }

    public function testGetSubjectWithConceptLandingPage(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::createOne(['organisation' => $organisation]);
        $url = sprintf(
            '/api/publication/v1/organisation/%s/subject/%s',
            $organisation->getId(),
            $subject->getId(),
        );
        $payload = $this->landingPagePayload();

        $putResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => $subject->getName(), 'landingPage' => $payload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $putData = $putResponse->toArray();
        /** @var array<string, mixed> $landingPage */
        $landingPage = $putData['landingPage'];
        self::assertIsString($landingPage['previewUrl']);

        $response = self::createPublicationApiClient()->request(Request::METHOD_GET, $url);

        self::assertResponseIsSuccessful();
        self::assertEquals([
            'slug' => 'foo-bar',
            'status' => 'concept',
            'title' => 'Pagina titel',
            'description' => 'Introductietekst als plain text.',
            'contentTree' => $payload['contentTree'],
            'previewUrl' => $landingPage['previewUrl'],
        ], $response->toArray()['landingPage']);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
    }

    public function testLandingPageLifecycleAndOmittedUpdate(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::createOne(['organisation' => $organisation]);
        $url = sprintf(
            '/api/publication/v1/organisation/%s/subject/%s',
            $organisation->getId(),
            $subject->getId(),
        );
        $payload = $this->landingPagePayload();

        $firstResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => 'Subject with landing page', 'landingPage' => $payload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        /** @var array{landingPage: array{previewUrl: string}} $firstData */
        $firstData = $firstResponse->toArray();
        $firstLandingPage = $firstData['landingPage'];
        self::assertIsString($firstLandingPage['previewUrl']);

        $secondResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => 'Subject with landing page', 'landingPage' => $payload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        /** @var array{landingPage: array{previewUrl: string}} $secondData */
        $secondData = $secondResponse->toArray();
        self::assertSame($firstLandingPage['previewUrl'], $secondData['landingPage']['previewUrl']);

        $publishedPayload = $payload;
        $publishedPayload['status'] = 'published';
        $publishedResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => 'Subject with landing page', 'landingPage' => $publishedPayload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        self::assertSame([
            'status' => 'published',
            'slug' => 'foo-bar',
            'title' => $payload['title'],
            'description' => $payload['description'],
            'contentTree' => $payload['contentTree'],
            'previewUrl' => null,
        ], $publishedResponse->toArray()['landingPage']);

        $conceptResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => 'Subject with landing page', 'landingPage' => $payload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        /** @var array{landingPage: array{previewUrl: string}} $conceptData */
        $conceptData = $conceptResponse->toArray();
        self::assertSame($firstLandingPage['previewUrl'], $conceptData['landingPage']['previewUrl']);

        $omittedLandingPageResponse = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            $url,
            ['json' => ['name' => 'Renamed subject']],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        self::assertSame('Renamed subject', $omittedLandingPageResponse->toArray()['name']);
        self::assertEquals($conceptResponse->toArray()['landingPage'], $omittedLandingPageResponse->toArray()['landingPage']);
    }

    /**
     * @param array<string, mixed> $landingPage
     */
    #[DataProvider('invalidLandingPageProvider')]
    public function testInvalidLandingPageReturnsValidationError(
        array $landingPage,
        string $expectedPropertyPath,
        string $expectedMessage,
    ): void {
        $subject = SubjectFactory::createOne();

        $response = self::createPublicationApiClient()->request(
            Request::METHOD_PUT,
            sprintf('/api/publication/v1/organisation/%s/subject/%s', $subject->getOrganisation()->getId(), $subject->getId()),
            [
                'json' => [
                    'name' => $subject->getName(),
                    'landingPage' => $landingPage,
                ],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        /** @var array{violations: list<array{propertyPath: string, message: string}>} $data */
        $data = $response->toArray(false);
        self::assertSame($expectedPropertyPath, $data['violations'][0]['propertyPath']);
        self::assertSame($expectedMessage, $data['violations'][0]['message']);
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
            'detail' => 'Organisation with id invalid was not found',
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
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount($subjectCount, $items);
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
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(5, $items);
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
            'pagination[cursor]=%s',
            base64_encode((string) json_encode(['id' => (string) $cursorSubject->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject?%s', $organisation->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(2, $items);
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        $subject = SubjectFactory::new()->create();

        $requestParameters = 'pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/subject?%s', $subject->getOrganisation()->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
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
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(0, $items);

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
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
    }

    public function testCreateSubjectWithLandingPage(): void
    {
        $organisation = OrganisationFactory::createOne();
        $payload = $this->landingPagePayload();
        $url = sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId());

        $response = self::createPublicationApiClient()->request(
            Request::METHOD_POST,
            $url,
            ['json' => ['name' => 'Subject with landing page', 'landingPage' => $payload]],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(SubjectResource::class);
        /** @var array{id: string, landingPage: array{slug: string, status: string, title: string, description: string, contentTree: list<array<string, mixed>>, previewUrl: string}} $created */
        $created = $response->toArray();
        self::assertSame('foo-bar', $created['landingPage']['slug']);
        self::assertSame($payload['status'], $created['landingPage']['status']);
        self::assertSame($payload['title'], $created['landingPage']['title']);
        self::assertSame($payload['description'], $created['landingPage']['description']);
        self::assertEquals($payload['contentTree'], $created['landingPage']['contentTree']);
        self::assertIsString($created['landingPage']['previewUrl']);

        $detailResponse = self::createPublicationApiClient()->request(
            Request::METHOD_GET,
            sprintf(
                '/api/publication/v1/organisation/%s/subject/%s',
                $organisation->getId(),
                $created['id'],
            ),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertEquals($created['landingPage'], $detailResponse->toArray()['landingPage']);
    }

    public function testCreateSubjectsWithOneCharacterLandingPageTitleAndDifferentSlugs(): void
    {
        $organisation = OrganisationFactory::createOne();
        $firstPayload = $this->landingPagePayload(['slug' => 'One-Char-1', 'title' => 'T']);
        $secondPayload = $this->landingPagePayload(['slug' => 'One-Char-2', 'title' => 'T']);
        $url = sprintf('/api/publication/v1/organisation/%s/subject', $organisation->getId());

        $firstResponse = self::createPublicationApiClient()->request(
            Request::METHOD_POST,
            $url,
            ['json' => ['name' => 'Subject one', 'landingPage' => $firstPayload]],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array{landingPage: array{slug: string, title: string}} $firstData */
        $firstData = $firstResponse->toArray();
        self::assertSame('one-char-1', $firstData['landingPage']['slug']);
        self::assertSame('T', $firstData['landingPage']['title']);

        $secondResponse = self::createPublicationApiClient()->request(
            Request::METHOD_POST,
            $url,
            ['json' => ['name' => 'Subject two', 'landingPage' => $secondPayload]],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        /** @var array{landingPage: array{slug: string, title: string}} $secondData */
        $secondData = $secondResponse->toArray();
        self::assertSame('one-char-2', $secondData['landingPage']['slug']);
        self::assertSame('T', $secondData['landingPage']['title']);
    }

    public function testCreateSubjectWithDuplicateLandingPageSlugAcrossOrganisationsReturnsValidationError(): void
    {
        $firstOrganisation = OrganisationFactory::createOne();
        $secondOrganisation = OrganisationFactory::createOne();
        $payload = $this->landingPagePayload(['slug' => 'Shared-Slug']);

        self::createPublicationApiClient()->request(
            Request::METHOD_POST,
            sprintf('/api/publication/v1/organisation/%s/subject', $firstOrganisation->getId()),
            ['json' => ['name' => 'First subject', 'landingPage' => $payload]],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = self::createPublicationApiClient()->request(
            Request::METHOD_POST,
            sprintf('/api/publication/v1/organisation/%s/subject', $secondOrganisation->getId()),
            ['json' => ['name' => 'Second subject', 'landingPage' => $payload]],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        /** @var array{violations: list<array{message: string}>} $data */
        $data = $response->toArray(false);
        self::assertContains('This landing page URL already exists', array_column($data['violations'], 'message'));
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
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(0, $items);

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
        self::assertJsonEquals([
            'status' => 422,
            'violations' => [
                [
                    'propertyPath' => 'name',
                    'message' => 'This value should be of type string.',
                    'code' => 'ba785a8c-82cb-4283-967c-3cf342181b40',
                ],
            ],
            'detail' => 'name: This value should be of type string.',
            'type' => '/validation_errors/ba785a8c-82cb-4283-967c-3cf342181b40',
            'title' => 'An error occurred',
        ]);
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

    /**
     * @param array{
     *     slug?: string,
     *     status?: string,
     *     title?: string,
     *     description?: string,
     *     contentTree?: list<array{
     *         title: string,
     *         body: string,
     *         children: list<array{
     *             title: string,
     *             body: string,
     *             children: list<array{
     *                 title: string,
     *                 body: string,
     *                 children: array{}
     *             }>
     *         }>
     *     }>
     * } $overrides
     *
     * @return array{
     *     slug: string,
     *     status: string,
     *     title: string,
     *     description: string,
     *     contentTree: list<array{
     *         title: string,
     *         body: string,
     *         children: list<array{
     *             title: string,
     *             body: string,
     *             children: list<array{
     *                 title: string,
     *                 body: string,
     *                 children: array{}
     *             }>
     *         }>
     *     }>
     * }
     */
    private function landingPagePayload(array $overrides = []): array
    {
        /** @var array{
         *     slug: string,
         *     status: string,
         *     title: string,
         *     description: string,
         *     contentTree: list<array{
         *         title: string,
         *         body: string,
         *         children: list<array{
         *             title: string,
         *             body: string,
         *             children: list<array{
         *                 title: string,
         *                 body: string,
         *                 children: array{}
         *             }>
         *         }>
         *     }>
         * } $payload */
        $payload = array_replace_recursive([
            'slug' => 'Foo-Bar',
            'status' => 'concept',
            'title' => 'Pagina titel',
            'description' => 'Introductietekst als plain text.',
            'contentTree' => [
                [
                    'title' => 'Tijdlijn 2024',
                    'body' => 'Toelichting.',
                    'children' => [
                        [
                            'title' => 'Januari',
                            'body' => 'Gebeurtenis.',
                            'children' => [
                                [
                                    'title' => 'Week 1',
                                    'body' => 'Detail.',
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $overrides);

        return $payload;
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidLandingPageProvider(): iterable
    {
        yield 'non-string title' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 123, 'body' => 'Body', 'children' => []]],
            ],
            'landingPage.contentTree[0].title',
            'This value should be of type string.',
        ];
        yield 'non-string body' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 'Title', 'body' => 123, 'children' => []]],
            ],
            'landingPage.contentTree[0].body',
            'This value should be of type string.',
        ];
        yield 'disallowed markdown node in root body' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 'Title', 'body' => '# Heading', 'children' => []]],
            ],
            'landingPage.contentTree[0].body',
            'The Markdown contains an element that is not allowed (Heading).',
        ];
        yield 'disallowed markdown node in child body' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [[
                    'title' => 'Parent',
                    'body' => 'Body',
                    'children' => [['title' => 'Child', 'body' => '# Heading', 'children' => []]],
                ]],
            ],
            'landingPage.contentTree[0].children[0].body',
            'The Markdown contains an element that is not allowed (Heading).',
        ];
        yield 'non-array children' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 'Title', 'body' => 'Body', 'children' => 'invalid']],
            ],
            'landingPage.contentTree[0].children',
            'This value should be of type array.',
        ];
        yield 'missing body' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 'Title', 'children' => []]],
            ],
            'landingPage.contentTree[0].body',
            'This value should be of type string.',
        ];
        yield 'fourth nesting level' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [[
                    'title' => 'Level 1',
                    'body' => 'Body',
                    'children' => [[
                        'title' => 'Level 2',
                        'body' => 'Body',
                        'children' => [[
                            'title' => 'Level 3',
                            'body' => 'Body',
                            'children' => [[
                                'title' => 'Level 4',
                                'body' => 'Body',
                                'children' => [],
                            ]],
                        ]],
                    ]],
                ]],
            ],
            'landingPage.contentTree[0].children[0].children[0].children',
            'subject.content_tree.max_depth_exceeded',
        ];
        yield 'body exceeding maximum length' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [['title' => 'Title', 'body' => str_repeat('x', 10001), 'children' => []]],
            ],
            'landingPage.contentTree[0].body',
            'subject.content_tree.body_too_long',
        ];
        yield 'tree exceeding maximum node count' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => array_fill(0, 101, ['title' => 'Title', 'body' => 'Body', 'children' => []]),
            ],
            'landingPage.contentTree[100]',
            'subject.content_tree.too_many_nodes',
        ];
        yield 'slug with whitespace' => [
            [
                'slug' => 'foo bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [],
            ],
            'landingPage.slug',
            'Invalid landing page slug format',
        ];
        yield 'slug with underscore' => [
            [
                'slug' => 'foo_bar',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [],
            ],
            'landingPage.slug',
            'Invalid landing page slug format',
        ];
        yield 'slug shorter than minimum length' => [
            [
                'slug' => 'a',
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [],
            ],
            'landingPage.slug',
            'Invalid landing page slug length',
        ];
        yield 'slug longer than maximum length' => [
            [
                'slug' => str_repeat('a', 51),
                'status' => 'concept',
                'title' => 'Pagina titel',
                'description' => 'Beschrijving',
                'contentTree' => [],
            ],
            'landingPage.slug',
            'Invalid landing page slug length',
        ];
        yield 'title longer than maximum length' => [
            [
                'slug' => 'foo-bar',
                'status' => 'concept',
                'title' => str_repeat('a', 201),
                'description' => 'Beschrijving',
                'contentTree' => [],
            ],
            'landingPage.title',
            'Invalid landing page title length',
        ];
    }
}
