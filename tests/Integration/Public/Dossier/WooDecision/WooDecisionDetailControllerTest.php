<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Public\Dossier\WooDecision;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;

use function sprintf;
use function strtolower;

final class WooDecisionDetailControllerTest extends SharedWebTestCase
{
    public function testDossierNrChangeHistoryIsDisplayedOnPublicPage(): void
    {
        $client = static::createClient();

        $oldDossierNr = strtolower(self::getFaker()->uuid());
        $newDossierNr = strtolower(self::getFaker()->uuid());

        $dossier = WooDecisionFactory::createOne([
            'departments' => [DepartmentFactory::new([
                'feedbackContent' => self::getFaker()->sentence(),
                'responsibilityContent' => self::getFaker()->sentence(),
            ])],
            'dossierNr' => $newDossierNr,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => self::getFaker()->plainDateBetween('-2 week', '-1 week'),
        ]);

        $mainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $dossier]);
        $dossier->setMainDocument($mainDocument);

        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->flush();

        HistoryFactory::createOne([
            'identifier' => $dossier->getId(),
            'context' => ['oldNr' => $oldDossierNr, 'newNr' => $newDossierNr],
        ]);

        $client->request('GET', sprintf('/dossier/%s/%s', $dossier->getDocumentPrefix(), $newDossierNr));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            sprintf('Besluitnummer aangepast van %s naar %s', $oldDossierNr, $newDossierNr),
            (string) $client->getResponse()->getContent(),
        );
    }
}
