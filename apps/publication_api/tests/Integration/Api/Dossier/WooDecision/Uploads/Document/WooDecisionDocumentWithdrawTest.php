<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\WooDecision\Uploads\Document;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function Zenstruck\Foundry\Persistence\save;

final class WooDecisionDocumentWithdrawTest extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'woo-decision';
    }

    public function testWithdrawWooDecisionDocumentReturnsAccepted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => Judgement::PUBLIC,
        ]);

        $documentDispatcher = Mockery::mock(DocumentDispatcher::class);
        $documentDispatcher
            ->expects('dispatchWithdrawDocumentCommand')
            ->with(
                Mockery::on(static function (WooDecision $dispatchedWooDecision) use ($wooDecision): bool {
                    return $dispatchedWooDecision->getId()->equals($wooDecision->getId());
                }),
                Mockery::on(static function (Document $dispatchedDocument) use ($document): bool {
                    return $dispatchedDocument->getId()->equals($document->getId());
                }),
                DocumentWithdrawReason::DATA_IN_DOCUMENT,
                'Contains unredacted personal data on page 3.',
            );
        $client = self::createPublicationApiClient();
        self::getContainer()->set(DocumentDispatcher::class, $documentDispatcher);
        $client->request(
            Request::METHOD_PUT,
            sprintf(
                '%s/uploads/document/external/%s/withdraw',
                $this->buildUrl($organisation, $wooDecision),
                $document->getExternalId()?->toString(),
            ),
            [
                'json' => [
                    'reason' => DocumentWithdrawReason::DATA_IN_DOCUMENT->value,
                    'explanation' => 'Contains unredacted personal data on page 3.',
                ],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    /**
     * @param array<string, mixed> $json
     */
    #[DataProvider('withdrawValidationErrorDataProvider')]
    public function testWithdrawWithInvalidInputReturns422(array $json): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => Judgement::PUBLIC,
        ]);

        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            sprintf(
                '%s/uploads/document/external/%s/withdraw',
                $this->buildUrl($organisation, $wooDecision),
                $document->getExternalId()?->toString(),
            ),
            ['json' => $json],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testWithdrawWhenDocumentIsAlreadyWithdrawnReturns422(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => Judgement::PUBLIC,
        ]);
        $document->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'already withdrawn');
        save($document);

        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            sprintf(
                '%s/uploads/document/external/%s/withdraw',
                $this->buildUrl($organisation, $wooDecision),
                $document->getExternalId()?->toString(),
            ),
            [
                'json' => [
                    'reason' => DocumentWithdrawReason::DATA_IN_DOCUMENT->value,
                    'explanation' => 'Contains unredacted personal data on page 3.',
                ],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'Document withdraw is not allowed in current state']]]);
    }

    /**
     * @return array<string, array{json: array<string, mixed>}>
     */
    public static function withdrawValidationErrorDataProvider(): array
    {
        return [
            'without reason' => [
                'json' => [
                    'explanation' => 'Contains unredacted personal data on page 3.',
                ],
            ],
            'without explanation' => [
                'json' => [
                    'reason' => DocumentWithdrawReason::DATA_IN_DOCUMENT->value,
                ],
            ],
            'with invalid reason' => [
                'json' => [
                    'reason' => 'invalid',
                    'explanation' => 'Contains unredacted personal data on page 3.',
                ],
            ],
        ];
    }
}
