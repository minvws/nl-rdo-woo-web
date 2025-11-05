<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Uploader\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUploadFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\Api\Admin\AdminApiTestCase;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class RejectChangesTest extends AdminApiTestCase
{
    use IntegrationTestTrait;

    private DocumentFileSetRepository $documentFileSetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->documentFileSetRepository = self::getContainer()->get(DocumentFileSetRepository::class);
    }

    public function testRejectChanges(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::PUBLISHED,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $documentFileSet = DocumentFileSetFactory::createOne([
            'dossier' => $wooDecision,
            'status' => DocumentFileSetStatus::NEEDS_CONFIRMATION,
        ])->_real();

        DocumentFileUploadFactory::createOne(['documentFileSet' => $documentFileSet]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', $wooDecision->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        self::assertSame(
            DocumentFileSetStatus::REJECTED,
            $this->documentFileSetRepository->find($documentFileSet->getId())?->getStatus(),
        );
    }

    public function testRejectChangesOnNonExistingWooDecision(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', Uuid::v6()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('getInvalidUuidsData')]
    public function testRejectChangesUsingMalformedUuid(string $invalidUuid): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', $invalidUuid),
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

    public function testRejectChangesWithoutAuthorisation(): void
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

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', $wooDecision->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[DataProvider('getInvalidDossierStatus')]
    public function testRejectChangesWhileDossierHasInvalidStatus(DossierStatus $dossierStatus): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => $dossierStatus,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        DocumentFileUploadFactory::createOne([
            'documentFileSet' => DocumentFileSetFactory::createOne([
                'dossier' => $wooDecision,
                'status' => DocumentFileSetStatus::PROCESSING_UPLOADS,
            ]),
        ]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', $wooDecision->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array<string,array{dossierStatus:DossierStatus}>
     */
    public static function getInvalidDossierStatus(): array
    {
        return [
            'dossier status new' => [
                'dossierStatus' => DossierStatus::NEW,
            ],
            'dossier status concept' => [
                'dossierStatus' => DossierStatus::CONCEPT,
            ],
            'dossier status deleted' => [
                'dossierStatus' => DossierStatus::DELETED,
            ],
        ];
    }

    public function testRejectChangesWhileNotNeedingConfirmation(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::PUBLISHED,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        DocumentFileUploadFactory::createOne([
            'documentFileSet' => DocumentFileSetFactory::createOne([
                'dossier' => $wooDecision,
                'status' => DocumentFileSetStatus::PROCESSING_UPLOADS,
            ]),
        ]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/uploader/woo-decision/%s/reject-changes', $wooDecision->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
