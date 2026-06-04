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
    public function testCreate(): void
    {
        $dossier = CovenantFactory::createOne();

        $document = CovenantMainDocumentFactory::new()->withoutPersisting()->createOne();

        $createMainDocumentCommand = new CreateMainDocumentCommand(
            dossierId: $dossier->getId(),
            formalDate: $document->getFormalDate(),
            internalReference: $document->getInternalReference(),
            type: $document->getType(),
            language: $document->getLanguage(),
            grounds: [],
            uploadFileReference: 'uploadFileReference',
        );

        $result = self::fromContainer(CovenantMainDocumentRepository::class)
            ->create($dossier, $createMainDocumentCommand);

        self::assertEquals($dossier, $result->getDossier());
        self::assertEquals($createMainDocumentCommand->formalDate, $result->getFormalDate());
        self::assertEquals($createMainDocumentCommand->type, $result->getType());
        self::assertEquals($createMainDocumentCommand->language, $result->getLanguage());
    }
}
