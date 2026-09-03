<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;

use function sprintf;
use function trim;

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

    public function testDossierSortsDocumentsByDocumentNumberByDefault(): void
    {
        $client = self::createClient();
        $organisation = OrganisationFactory::new()->create();
        $dossier = WooDecisionFactory::new()->published()->create([
            'decision' => DecisionType::PUBLIC,
            'organisation' => $organisation,
        ]);

        $documentLow = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'judgement' => Judgement::PUBLIC,
            'documentId' => DocumentId::create('doc-low'),
            'documentNumber' => 'PREF-MAT-100',
            'documentDate' => PlainDate::create('2024-02-01'),
            'fileInfo' => FileInfoFactory::new([
                'name' => 'alpha.pdf',
                'uploaded' => true,
            ]),
        ]);

        $documentHigh = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'judgement' => Judgement::PUBLIC,
            'documentId' => DocumentId::create('doc-high'),
            'documentNumber' => 'PREF-MAT-200',
            'documentDate' => PlainDate::create('2024-01-01'),
            'fileInfo' => FileInfoFactory::new([
                'name' => 'zeta.pdf',
                'uploaded' => true,
            ]),
        ]);

        $inquiry = InquiryFactory::createOne([
            'organisation' => $organisation,
            'dossiers' => [$dossier],
            'documents' => [$documentLow, $documentHigh],
        ]);

        $client->request(
            'GET',
            sprintf(
                '/zaak/%s/dossier/%s/%s',
                $inquiry->getToken(),
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
            ),
        );

        self::assertResponseIsSuccessful();

        $rows = $client->getCrawler()
            ->filter('[data-e2e-name="inquiry-documents"] table.woo-table')
            ->first()
            ->filter('tbody tr');

        self::assertSame('PREF-MAT-100', trim($rows->eq(0)->filter('td')->eq(0)->text()));
        self::assertSame('PREF-MAT-200', trim($rows->eq(1)->filter('td')->eq(0)->text()));
    }
}
