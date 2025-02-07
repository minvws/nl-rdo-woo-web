<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Uploader\WooDecision;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateType;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUploadFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class UploadStatusTest extends ApiTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testFetchingUploadStatus(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $wooDecision->addDocument(DocumentFactory::new()->withNotPublicJudgement()->create()->_real());
        $wooDecision->addDocument(DocumentFactory::new()->withPartialPublicJudgement()->create()->_real());
        $wooDecision->addDocument(DocumentFactory::new()->withPublicJudgement()->create()->_real());
        $wooDecision->addDocument(DocumentFactory::new()->withPublicJudgement()->removeFileProperties()->create([
            'documentId' => $missingDocumentId = '1337',
        ])->_real());

        DocumentFileUploadFactory::createOne([
            'documentFileSet' => DocumentFileSetFactory::createOne(['dossier' => $wooDecision]),
        ]);

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'dossierId' => $wooDecision->getId()->toRfc4122(),
            'status' => DocumentFileSetStatus::OPEN_FOR_UPLOADS->value,
            'canProcess' => true,
            'uploadedFiles' => [
                [
                    'name' => 'file_name.pdf',
                    'mimeType' => 'application/pdf',
                ],
            ],
            'expectedDocumentsCount' => 3,
            'currentDocumentsCount' => 2,
            'missingDocuments' => [
                $missingDocumentId,
            ],
            'changes' => [],
        ]);
    }

    public function testFetchingUploadStatusWithChanges(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $documentFileSet = DocumentFileSetFactory::createOne([
            'dossier' => $wooDecision,
            'status' => DocumentFileSetStatus::NEEDS_CONFIRMATION,
        ]);

        DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => DocumentFactory::new()->withdrawn()->create(['addDossier' => $wooDecision]),
        ]);

        DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => DocumentFactory::new()->withdrawn()->create(['addDossier' => $wooDecision]),
        ]);

        DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => DocumentFactory::new()->withPublicJudgement()->removeFileProperties()->create(['addDossier' => $wooDecision]),
        ]);

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'dossierId' => $wooDecision->getId()->toRfc4122(),
            'status' => DocumentFileSetStatus::NEEDS_CONFIRMATION->value,
            'uploadedFiles' => [],
            'expectedDocumentsCount' => 1,
            'currentDocumentsCount' => 0,
            'missingDocuments' => [],
            'changes' => [
                DocumentFileUpdateType::ADD->value => 1,
                DocumentFileUpdateType::UPDATE->value => 0,
                DocumentFileUpdateType::REPUBLISH->value => 2,
            ],
        ]);
    }

    public function testFetchingUploadStatusOnNonExistingWooDecision(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', Uuid::v6()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('getInvalidUuidsData')]
    public function testFetchingUploadStatusUsingMalformedUuid(string $invalidUuid): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', Uuid::v6()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return list<array{invalidUuid:string}>
     */
    public static function getInvalidUuidsData(): array
    {
        return [
            [
                'invalidUuid' => 'NOT_A_VALID_UUID',
            ],
            [
                'invalidUuid' => 'id',
            ],
        ];
    }

    public function testFetchingUploadStatusWithoutAuthorisation(): void
    {
        $owner = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $owner->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
