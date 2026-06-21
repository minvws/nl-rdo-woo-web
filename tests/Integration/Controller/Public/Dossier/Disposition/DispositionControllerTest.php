<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\Disposition;

use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DossierTitle;

use function sprintf;

final class DispositionControllerTest extends SharedWebTestCase
{
    public function testDetailBreadcrumbCapitalisesMultibyteLeadingTitle(): void
    {
        $client = static::createClient();

        $title = 'ënergiebesluit-titel met multibyte begin';
        $expectedBreadcrumb = 'Ënergiebesluit-titel met multibyte begin';

        $department = DepartmentFactory::new();
        $dossier = DispositionFactory::createOne([
            'title' => DossierTitle::create($title),
            'dateFrom' => self::getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        DispositionMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        $client->request('GET', sprintf('/beschikking/%s/%s', $dossier->getDocumentPrefix(), $dossier->getDossierNr()));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($expectedBreadcrumb, (string) $client->getResponse()->getContent());
    }
}
