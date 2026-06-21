<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Controller\Public\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;

use function sprintf;

final class WooDecisionControllerTest extends SharedWebTestCase
{
    public function testDossierNrChangeHistoryIsDisplayedOnPublicPage(): void
    {
        $client = static::createClient();

        $oldDossierNr = self::getFaker()->uuid();
        $newDossierNr = self::getFaker()->uuid();

        $department = DepartmentFactory::new();
        $mainDocument = WooDecisionMainDocumentFactory::createOne();
        $dossier = WooDecisionFactory::createOne([
            'departments' => [$department],
            'dossierNr' => $newDossierNr,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => self::getFaker()->plainDateBetween('-2 week', '-1 week'),
            'mainDocument' => $mainDocument,
        ]);

        HistoryFactory::createOne([
            'identifier' => $dossier->getId(),
            'context' => [
                'oldNr' => $oldDossierNr,
                'newNr' => $newDossierNr,
            ],
        ]);

        $client->request('GET', sprintf('/dossier/%s/%s', $dossier->getDocumentPrefix(), $newDossierNr));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            sprintf('Besluitnummer aangepast van %s naar %s', $oldDossierNr, $newDossierNr),
            (string) $client->getResponse()->getContent(),
        );
    }
}
