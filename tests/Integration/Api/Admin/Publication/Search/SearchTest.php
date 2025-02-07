<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Publication\Search;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Api\Admin\Publication\Search\SearchResultDto;
use App\Api\Admin\Publication\Search\SearchResultType;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SearchTest extends ApiTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testSearch(): void
    {
        $organisation = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create([
                'organisation' => $organisation,
            ]);

        // Only the second dossier from this sequence should be found
        $dossiers = AnnualReportFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very fancy document'],
                ['title' => 'The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested'],
            ])
            ->create(['organisation' => $organisation]);

        // This dossier should not be found because it is from another organisation
        WooDecisionFactory::createOne([
            'title' => 'A very fancy document from another organisation',
            'organisation' => $organisationTwo,
        ]);

        // Only the first document from this sequence should be found
        $documents = DocumentFactory::new()
            ->sequence([
                [
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'fancy document.pdf',
                    ]),
                ],
                [
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'another document.pdf',
                    ]),
                ],
            ])
            ->create();

        // The above documents are attached to this dossier that belongs to the same organisation as the user, but the
        // dossier itself should not be found.
        WooDecisionFactory::createOne([
            'title' => 'This dossier should not be found',
            'organisation' => $organisation,
            'documents' => $documents,
        ]);

        // There is no match on the title of this dossier, but the mainDocument and Attachment should be found
        $annualReport = AnnualReportFactory::createOne([
            'title' => 'This dossier should not be found',
            'organisation' => $organisation,
        ]);

        $annualReportMainDocument = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $annualReport,
            'fileInfo' => FileInfoFactory::new()->createOne([
                'name' => 'maindocument fancy document.pdf',
            ]),
        ])->_real();

        $annualReportAttachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'fileInfo' => FileInfoFactory::new()->createOne([
                'name' => 'attachment FANCY document.pdf',
            ]),
        ])->_real();

        $searchQuery = '  fancy document  ';

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/publication/search?q=%s', rawurlencode($searchQuery)),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SearchResultDto::class);

        $responseAsArray = $response->toArray(false);
        usort($responseAsArray, $this->sortById(...));

        self::assertCount(4, $responseAsArray);

        $expected = [
            [
                'id' => $documents[0]->getId()->__toString(),
                'number' => $documents[0]->getDocumentNr(),
                'type' => SearchResultType::DOCUMENT->value,
                'title' => $documents[0]->getFileInfo()->getName(),
            ],
            [
                'id' => $dossiers[1]->getId()->__toString(),
                'number' => $dossiers[1]->getDossierNr(),
                'type' => SearchResultType::DOSSIER->value,
                'title' => $dossiers[1]->getTitle(),
            ],
            [
                'id' => $annualReportMainDocument->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $annualReportMainDocument->getFileInfo()->getName(),
            ],
            [
                'id' => $annualReportAttachment->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::ATTACHMENT->value,
                'title' => $annualReportAttachment->getFileInfo()->getName(),
            ],
        ];
        usort($expected, $this->sortById(...));

        self::assertArraySubset($expected, $responseAsArray);
    }

    public function testSearchFilteredByPublicationType(): void
    {
        $organisation = OrganisationFactory::createOne();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create(['organisation' => $organisation]);

        $annualReports = AnnualReportFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'], // This should be found
            ])
            ->create(['organisation' => $organisation]);

        $annualReportMainDocuments = AnnualReportMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $annualReports[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument fancy document.pdf',
                    ]),
                ],
                [
                    'dossier' => $annualReports[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar document.pdf', // This should be found
                    ]),
                ],
            ])
            ->create();

        $covenants = CovenantFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'],
            ])
            ->create(['organisation' => $organisation]);

        CovenantMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $covenants[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument fancy document.pdf',
                    ]),
                ],
                [
                    'dossier' => $covenants[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar 2 document.pdf',
                    ]),
                ],
            ])
            ->create();

        $searchQuery = 'foobar  ';

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/publication/search?q=%s&filter[publicationType]=%s',
                    rawurlencode($searchQuery),
                    rawurlencode(DossierType::ANNUAL_REPORT->value),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SearchResultDto::class);

        $responseAsArray = $response->toArray(false);
        usort($responseAsArray, $this->sortById(...));

        self::assertCount(2, $responseAsArray);

        $expected = [
            [
                'id' => $annualReports[1]->getId()->__toString(),
                'number' => $annualReports[1]->getDossierNr(),
                'type' => SearchResultType::DOSSIER->value,
                'title' => $annualReports[1]->getTitle(),
            ],
            [
                'id' => $annualReportMainDocuments[1]->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $annualReportMainDocuments[1]->getFileInfo()->getName(),
            ],
        ];
        usort($expected, $this->sortById(...));

        self::assertArraySubset($expected, $responseAsArray);
    }

    public function testSearchFilteredByResultType(): void
    {
        $organisation = OrganisationFactory::createOne();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create(['organisation' => $organisation]);

        $annualReports = AnnualReportFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'], // This should be found
            ])
            ->create(['organisation' => $organisation]);

        $annualReportMainDocuments = AnnualReportMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $annualReports[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument fancy document.pdf',
                    ]),
                ],
                [
                    'dossier' => $annualReports[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar document.pdf', // This should be found
                    ]),
                ],
            ])
            ->create();

        $covenants = CovenantFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'],
            ])
            ->create(['organisation' => $organisation]);

        $covenantMainDocuments = CovenantMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $covenants[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar 1 document.pdf',
                    ]),
                ],
                [
                    'dossier' => $covenants[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar 2 document.pdf',
                    ]),
                ],
            ])
            ->create();

        $searchQuery = 'foobar  ';

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/publication/search?q=%s&filter[resultType]=%s',
                    rawurlencode($searchQuery),
                    rawurlencode(SearchResultType::MAIN_DOCUMENT->value),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SearchResultDto::class);

        $responseAsArray = $response->toArray(false);
        usort($responseAsArray, $this->sortById(...));

        self::assertCount(3, $responseAsArray);

        $expected = [
            [
                'id' => $annualReportMainDocuments[1]->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $annualReportMainDocuments[1]->getFileInfo()->getName(),
            ],
            [
                'id' => $covenantMainDocuments[0]->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $covenantMainDocuments[0]->getFileInfo()->getName(),
            ],
            [
                'id' => $covenantMainDocuments[1]->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $covenantMainDocuments[1]->getFileInfo()->getName(),
            ],
        ];
        usort($expected, $this->sortById(...));

        self::assertArraySubset($expected, $responseAsArray);
    }

    public function testSearchFilteredByUuid(): void
    {
        $organisation = OrganisationFactory::createOne();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create(['organisation' => $organisation]);

        $annualReports = AnnualReportFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'], // This should be found
                ['title' => 'A very fancy foobar document'],
            ])
            ->create(['organisation' => $organisation]);

        $annualReportMainDocuments = AnnualReportMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $annualReports[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument fancy document.pdf',
                    ]),
                ],
                [
                    'dossier' => $annualReports[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument document.pdf',
                    ]),
                ],
                [
                    'dossier' => $annualReports[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar document.pdf', // This should be found
                    ]),
                ],
            ])
            ->create();

        $covenants = CovenantFactory::new()
            ->sequence([
                ['title' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
                ['title' => 'A very foobar document'],
            ])
            ->create(['organisation' => $organisation]);

        CovenantMainDocumentFactory::new()
            ->sequence([
                [
                    'dossier' => $covenants[0],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument fancy document.pdf',
                    ]),
                ],
                [
                    'dossier' => $covenants[1],
                    'fileInfo' => FileInfoFactory::new()->createOne([
                        'name' => 'maindocument foobar document.pdf',
                    ]),
                ],
            ])
            ->create();

        $searchQuery = 'foobar  ';

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/publication/search?q=%s&dossierId=%s',
                    rawurlencode($searchQuery),
                    $annualReports[1]->getId()->toRfc4122(),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(SearchResultDto::class);

        $responseAsArray = $response->toArray(false);
        usort($responseAsArray, $this->sortById(...));

        self::assertCount(2, $responseAsArray);

        $expected = [
            [
                'id' => $annualReports[1]->getId()->__toString(),
                'number' => $annualReports[1]->getDossierNr(),
                'type' => SearchResultType::DOSSIER->value,
                'title' => $annualReports[1]->getTitle(),
            ],
            [
                'id' => $annualReportMainDocuments[2]->getId()->__toString(),
                'number' => null,
                'type' => SearchResultType::MAIN_DOCUMENT->value,
                'title' => $annualReportMainDocuments[2]->getFileInfo()->getName(),
            ],
        ];
        usort($expected, $this->sortById(...));

        self::assertArraySubset($expected, $responseAsArray);
    }

    public function testSearchQueryParamIsRequired(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                '/balie/api/publication/search',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        $violations = [
            ['propertyPath' => 'q', 'code' => NotBlank::IS_BLANK_ERROR],
        ];

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => $violations]);
    }

    public function testSearchQueryParamIsOfAMinimalLength(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                '/balie/api/publication/search?q=s',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        $violations = [
            ['propertyPath' => 'q', 'code' => Length::TOO_SHORT_ERROR],
        ];

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => $violations]);
    }

    public function testSearchQueryParamCannotExceedMaxLength(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        $searchQuery = str_repeat('a', 256);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/publication/search?q=%s', $searchQuery),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        $violations = [
            ['propertyPath' => 'q', 'code' => Length::TOO_LONG_ERROR],
        ];

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => $violations]);
    }

    /**
     * @param array{id:string} $a
     * @param array{id:string} $b
     */
    private function sortById(array $a, array $b): int
    {
        return $a['id'] <=> $b['id'];
    }
}
