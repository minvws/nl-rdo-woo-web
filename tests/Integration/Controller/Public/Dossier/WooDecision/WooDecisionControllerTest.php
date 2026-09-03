<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;

use function sprintf;
use function trim;

final class WooDecisionControllerTest extends SharedWebTestCase
{
    public function testDossierNumberChangeHistoryIsDisplayedOnPublicPage(): void
    {
        $client = static::createClient();

        $oldDossierNumber = self::getFaker()->uuid();
        $newDossierNumber = self::getFaker()->uuid();

        $department = DepartmentFactory::new();
        $mainDocument = WooDecisionMainDocumentFactory::createOne();
        $dossier = WooDecisionFactory::createOne([
            'departments' => [$department],
            'dossierNumber' => $newDossierNumber,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => self::getFaker()->plainDateBetween('-2 week', '-1 week'),
            'mainDocument' => $mainDocument,
        ]);

        HistoryFactory::createOne([
            'identifier' => $dossier->getId(),
            'context' => [
                'oldNr' => $oldDossierNumber,
                'newNr' => $newDossierNumber,
            ],
        ]);

        $client->request('GET', sprintf('/dossier/%s/%s', $dossier->getDocumentPrefix(), $newDossierNumber));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            sprintf('Besluitnummer aangepast van %s naar %s', $oldDossierNumber, $newDossierNumber),
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testDocumentsAreSortedByDocumentNumberByDefault(): void
    {
        $client = static::createClient();
        $dossier = WooDecisionFactory::new()->published()->create([
            'decision' => DecisionType::PUBLIC,
        ]);

        DocumentFactory::createOne([
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

        DocumentFactory::createOne([
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

        $client->request(
            'GET',
            sprintf('/dossier/%s/%s', $dossier->getDocumentPrefix(), $dossier->getDossierNumber()),
        );

        self::assertResponseIsSuccessful();

        $rows = $client->getCrawler()
            ->filter('[data-e2e-name="documents-section"] table.woo-table')
            ->first()
            ->filter('tbody tr');

        self::assertSame('PREF-MAT-100', trim($rows->eq(0)->filter('td')->eq(0)->text()));
    }

    public function testExplicitDocumentSortOverridesDefault(): void
    {
        $client = static::createClient();
        $dossier = WooDecisionFactory::new()->published()->create([
            'decision' => DecisionType::PUBLIC,
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'judgement' => Judgement::PUBLIC,
            'documentId' => DocumentId::create('doc-low'),
            'documentNumber' => 'PREF-MAT-100',
            'documentDate' => PlainDate::create('2024-02-01'),
            'fileInfo' => FileInfoFactory::new([
                'name' => 'zeta.pdf',
                'uploaded' => true,
            ]),
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'judgement' => Judgement::PUBLIC,
            'documentId' => DocumentId::create('doc-high'),
            'documentNumber' => 'PREF-MAT-200',
            'documentDate' => PlainDate::create('2024-01-01'),
            'fileInfo' => FileInfoFactory::new([
                'name' => 'alpha.pdf',
                'uploaded' => true,
            ]),
        ]);

        $client->request(
            'GET',
            sprintf(
                '/dossier/%s/%s?sort=doc.fileInfo.name&direction=asc',
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
            ),
        );

        self::assertResponseIsSuccessful();

        $rows = $client->getCrawler()
            ->filter('[data-e2e-name="documents-section"] table.woo-table')
            ->first()
            ->filter('tbody tr');

        self::assertSame('PREF-MAT-200', trim($rows->eq(0)->filter('td')->eq(0)->text()));
    }
}
