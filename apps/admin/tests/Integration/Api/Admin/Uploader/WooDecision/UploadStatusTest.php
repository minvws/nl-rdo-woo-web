<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin\Uploader\WooDecision;

use Admin\Tests\Integration\Api\Admin\AdminApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUploadFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

use function sprintf;

final class UploadStatusTest extends AdminApiTestCase
{
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
            ->create();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ]);

        $wooDecision->addDocument(DocumentFactory::new()->withNotPublicJudgement()->create());
        $wooDecision->addDocument(DocumentFactory::new()->withPartialPublicJudgement()->create());
        $wooDecision->addDocument(DocumentFactory::new()->withPublicJudgement()->create());
        $wooDecision->addDocument(DocumentFactory::new()->withPublicJudgement()->removeFileProperties()->create([
            'documentId' => $missingDocumentId = '1337',
        ]));

        DocumentFileUploadFactory::createOne([
            'documentFileSet' => DocumentFileSetFactory::createOne(['dossier' => $wooDecision]),
        ]);

        $client = self::createAdminApiClient($user);
        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
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
        // Ugly check to make sure an empty changes object is returned instead of an empty array
        self::assertStringContainsString('"changes":{}', $client->getResponse()?->getContent() ?? '');
    }

    public function testFetchingUploadStatusWithChanges(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ]);

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

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
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
            ->create();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/woo-decision/%s/status', Uuid::v6()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('getInvalidUuidsData')]
    public function testFetchingUploadStatusUsingMalformedUuid(string $invalidUuid): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/woo-decision/%s/status', Uuid::v6()),
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
            ->create();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $owner->getOrganisation(),
        ]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/woo-decision/%s/status', $wooDecision->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
