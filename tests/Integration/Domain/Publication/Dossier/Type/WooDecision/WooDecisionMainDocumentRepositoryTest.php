<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentRepository;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WooDecisionMainDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    private function getRepository(): WooDecisionMainDocumentRepository
    {
        /** @var WooDecisionMainDocumentRepository */
        return self::getContainer()->get(WooDecisionMainDocumentRepository::class);
    }

    public function testCreate(): void
    {
        $dossier = WooDecisionFactory::createOne()->_real();

        $document = WooDecisionMainDocumentFactory::new()->withoutPersisting()->createOne()->_real();

        $createMainDocumentCommand = new CreateMainDocumentCommand(
            dossierId: $dossier->getId(),
            formalDate: $document->getFormalDate(),
            internalReference: $document->getInternalReference(),
            type: $document->getType(),
            language: $document->getLanguage(),
            grounds: [],
            uploadFileReference: 'uploadFileReference',
        );

        $result = $this->getRepository()->create($dossier, $createMainDocumentCommand);

        self::assertEquals($dossier, $result->getDossier());
        self::assertEquals($createMainDocumentCommand->formalDate, $result->getFormalDate());
        self::assertEquals($createMainDocumentCommand->type, $result->getType());
        self::assertEquals($createMainDocumentCommand->language, $result->getLanguage());
    }
}
