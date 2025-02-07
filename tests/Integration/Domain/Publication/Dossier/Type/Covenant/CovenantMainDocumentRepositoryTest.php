<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocumentRepository;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantMainDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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
