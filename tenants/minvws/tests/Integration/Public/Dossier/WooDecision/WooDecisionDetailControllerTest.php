<?php

declare(strict_types=1);

namespace WooMinVWS\Tests\Integration\Public\Dossier\WooDecision;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\History\HistoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use WooMinVWS\Tests\Integration\MinVwsWebTestCase;

use function sprintf;
use function strtolower;

final class WooDecisionDetailControllerTest extends MinVwsWebTestCase
{
    public function testDossierNumberChangeHistoryIsDisplayedOnPublicPage(): void
    {
        $client = static::createClient();

        $oldDossierNumber = strtolower(self::getFaker()->uuid());
        $newDossierNumber = strtolower(self::getFaker()->uuid());

        $dossier = WooDecisionFactory::createOne([
            'departments' => [DepartmentFactory::new([
                'feedbackContent' => self::getFaker()->sentence(),
                'responsibilityContent' => self::getFaker()->sentence(),
            ])],
            'dossierNumber' => $newDossierNumber,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => self::getFaker()->plainDateBetween('-2 week', '-1 week'),
        ]);

        $mainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $dossier]);
        $dossier->setMainDocument($mainDocument);

        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->flush();

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
}
