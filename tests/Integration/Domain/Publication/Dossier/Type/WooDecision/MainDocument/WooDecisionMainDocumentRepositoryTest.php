<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\MainDocument;

use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class WooDecisionMainDocumentRepositoryTest extends SharedWebTestCase
{
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
        $dossier = WooDecisionFactory::createOne();

        $document = WooDecisionMainDocumentFactory::new()->withoutPersisting()->createOne();

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
