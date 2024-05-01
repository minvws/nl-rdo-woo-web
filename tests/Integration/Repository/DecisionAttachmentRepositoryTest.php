<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\DecisionAttachmentRepository;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DecisionAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DecisionAttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testFindForDossierPrefixAndNrFindsMatch(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $attachment = DecisionAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        /** @var DecisionAttachmentRepository $repo */
        $repo = self::getContainer()->get(DecisionAttachmentRepository::class);

        $result = $repo->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122()
        );

        self::assertNotNull($result);
        self::assertEquals($attachment->getId(), $result->getId());
    }

    public function testFindForDossierPrefixAndNrResultsNullOnDossierMismatch(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $attachment = DecisionAttachmentFactory::createOne([
            'dossier' => $dossier,
        ]);

        /** @var DecisionAttachmentRepository $repo */
        $repo = self::getContainer()->get(DecisionAttachmentRepository::class);

        $result = $repo->findForDossierPrefixAndNr(
            $dossier->getDocumentPrefix(),
            'MISMATCH',
            $attachment->getId()->toRfc4122()
        );

        self::assertNull($result);
    }
}
