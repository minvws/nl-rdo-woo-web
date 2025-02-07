<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Uploader\WooDecision;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUploadFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ProcessUploadsTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private DocumentFileSetRepository $documentFileSetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->documentFileSetRepository = self::getContainer()->get(DocumentFileSetRepository::class);
    }

    public function testProcessUploads(): void
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

        $documentFileSet = DocumentFileSetFactory::createOne(['dossier' => $wooDecision])->_real();

        DocumentFileUploadFactory::createOne(['documentFileSet' => $documentFileSet]);

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        self::assertSame(
            DocumentFileSetStatus::PROCESSING_UPLOADS,
            $this->documentFileSetRepository->find($documentFileSet->getId())?->getStatus(),
        );
    }

    public function testProcessUploadOnNonExistingWooDecision(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', Uuid::v6()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('getInvalidUuidsData')]
    public function testProcessUploadUsingMalformedUuid(string $invalidUuid): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', $invalidUuid),
            [
                'headers' => ['Accept' => 'application/json'],
            ],
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

    public function testProcessUploadOnWooDecisionWithoutUploads(): void
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

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testProcessUploadWithoutAuthorisation(): void
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
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testProcessUploadWhileNotOpenForUploads(): void
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

        DocumentFileUploadFactory::createOne([
            'documentFileSet' => DocumentFileSetFactory::createOne([
                'dossier' => $wooDecision,
                'status' => DocumentFileSetStatus::PROCESSING_UPLOADS,
            ]),
        ]);

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/uploader/woo-decision/%s/process', $wooDecision->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
