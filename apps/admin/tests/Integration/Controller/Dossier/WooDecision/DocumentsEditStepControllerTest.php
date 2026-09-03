<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Controller\Dossier\WooDecision;

use Admin\Tests\Integration\AdminWebTestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\UserFactory;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;

use function sprintf;
use function trim;

final class DocumentsEditStepControllerTest extends AdminWebTestCase
{
    public function testDocumentsDefaultToDocumentNumber(): void
    {
        $client = static::createClient();
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create();
        $dossier = WooDecisionFactory::new()->published()->create([
            'decision' => DecisionType::PUBLIC,
            'organisation' => $user->getOrganisation(),
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

        $client
            ->loginUser($user, 'balie')
            ->request(
                'GET',
                sprintf(
                    '/balie/dossier/woodecision/documents/edit/%s/%s',
                    $dossier->getDocumentPrefix(),
                    $dossier->getDossierNumber(),
                ),
            );

        self::assertResponseIsSuccessful();

        $rows = $client->getCrawler()
            ->filter('table[data-e2e-name="dossier-documents"] tbody tr');

        self::assertSame('doc-low', trim($rows->eq(0)->filter('td')->eq(0)->text()));
    }
}
