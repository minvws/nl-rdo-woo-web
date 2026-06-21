<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

use function sprintf;

final class InquiryControllerTest extends SharedWebTestCase
{
    public function testDetailRendersDocumentCountSummaryPerJudgement(): void
    {
        $client = self::createClient();

        $organisation = OrganisationFactory::new()->create();
        $wooDecision = WooDecisionFactory::new()->create([
            'organisation' => $organisation,
            'status' => DossierStatus::PUBLISHED,
        ]);

        $publicDocument1 = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);
        $publicDocument2 = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);

        $suspendedPublic = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
        ]);

        $withdrawnPublic = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);
        $withdrawnPublic->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, '');

        $partialPublic = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PARTIAL_PUBLIC,
        ]);

        $alreadyPublic = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::ALREADY_PUBLIC,
        ]);

        $notPublicDocument1 = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $notPublicDocument2 = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $inquiry = InquiryFactory::createOne([
            'organisation' => $organisation,
            'dossiers' => [$wooDecision],
            'documents' => [
                $publicDocument1,
                $publicDocument2,
                $suspendedPublic,
                $withdrawnPublic,
                $partialPublic,
                $alreadyPublic,
                $notPublicDocument1,
                $notPublicDocument2,
            ],
        ]);

        $client->request('GET', sprintf('/zaak/%s', $inquiry->getToken()));

        self::assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('4 documenten zijn hierna openbaar gemaakt.', $content);
        self::assertStringContainsString('waarvan 1 ingetrokken', $content);
        self::assertStringContainsString('waarvan 1 opgeschort', $content);
        self::assertStringContainsString('1 document is hierna gedeeltelijk openbaar gemaakt.', $content);
        self::assertStringContainsString('1 document was reeds openbaar.', $content);
        self::assertStringContainsString('2 documenten worden niet openbaar gemaakt.', $content);
    }
}
