<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class CovenantMainDocumentRepositoryTest extends SharedWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    private function getRepository(): CovenantMainDocumentRepository
    {
        /** @var CovenantMainDocumentRepository */
        return self::getContainer()->get(CovenantMainDocumentRepository::class);
    }

    public function testCreate(): void
    {
        $dossier = CovenantFactory::createOne()->_real();

        $document = CovenantMainDocumentFactory::new()->withoutPersisting()->createOne()->_real();

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
