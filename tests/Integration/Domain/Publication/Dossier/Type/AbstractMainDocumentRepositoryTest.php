<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Uid\Uuid;

final class AbstractMainDocumentRepositoryTest extends SharedWebTestCase
{
    /**
     * @return AbstractMainDocumentRepository<AnnualReportMainDocument>
     */
    private function getRepository(): AbstractMainDocumentRepository
    {
        $managerRegistry = self::getContainer()->get(ManagerRegistry::class);

        return new
        /** @extends AbstractMainDocumentRepository<AnnualReportMainDocument> */
        class($managerRegistry) extends AbstractMainDocumentRepository {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, AnnualReportMainDocument::class);
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testSave(): void
    {
        $dossier = AnnualReportFactory::createOne()->_real();

        $document = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $repository = $this->getRepository();
        $repository->save($document, true);

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($document, $result);
    }

    public function testRemove(): void
    {
        $dossier = AnnualReportFactory::createOne()->_real();
        AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNotNull($result);

        $repository->remove($result, true);

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );
        self::assertNull($result);
    }

    public function testFindOneByDossierId(): void
    {
        $dossier = AnnualReportFactory::createOne()->_real();

        $document = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        self::assertEquals(
            $document->getId(),
            $this->getRepository()->findOneByDossierId($dossier->getId())?->getId(),
        );

        self::assertNull(
            $this->getRepository()->findOneByDossierId(Uuid::v6())
        );
    }

    public function testFindForDossierByPrefixAndNrFindsMatch(): void
    {
        $dossier = AnnualReportFactory::createOne()->_real();

        $document = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
        );

        self::assertNotNull($result);
        self::assertEquals($document->getId(), $result->getId());
    }

    public function testFindForDossierByPrefixAndNrMismatch(): void
    {
        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
        );

        self::assertNull($result);
    }

    public function testCreate(): void
    {
        $dossier = AnnualReportFactory::createOne()->_real();

        $document = AnnualReportMainDocumentFactory::new()->withoutPersisting()->createOne()->_real();

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
