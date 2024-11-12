<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Dossier\Type\AbstractAttachmentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AbstractAttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    /**
     * @return AbstractAttachmentRepository<CovenantAttachment>
     */
    private function getRepository(): AbstractAttachmentRepository
    {
        $managerRegistry = self::getContainer()->get(ManagerRegistry::class);

        return new
        /** @extends AbstractAttachmentRepository<CovenantAttachment> */
        class($managerRegistry) extends AbstractAttachmentRepository {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, CovenantAttachment::class);
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
        $covenant = CovenantFactory::createOne()->_real();

        $covenantAttachment = CovenantAttachmentFactory::new()
            ->withoutPersisting()
            ->createOne([
                'dossier' => $covenant,
            ])
            ->_real();

        $this->getRepository()->save($covenantAttachment, true);

        $result = $this->getRepository()->findOneForDossier($covenant->getId(), $covenantAttachment->getId());
        self::assertEquals($covenantAttachment, $result);
    }

    public function testFindForDossierByPrefixAndNrFindsMatch(): void
    {
        $covenant = CovenantFactory::createOne()->_real();

        $covenantAttachment = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
        ])->_real();

        $repository = $this->getRepository();

        $result = $repository->findForDossierByPrefixAndNr(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
            $covenantAttachment->getId()->toRfc4122(),
        );

        self::assertNotNull($result);
        self::assertEquals($covenantAttachment->getId(), $result->getId());
    }

    public function testFindForDossierByPrefixAndNrMismatch(): void
    {
        $repository = $this->getRepository();

        $result = $repository->findForDossierByPrefixAndNr(
            'a non-existing document prefix',
            'a non-existing dossier number',
            $this->getFaker()->uuid(),
        );

        self::assertNull($result);
    }

    public function testRemove(): void
    {
        $dossier = CovenantFactory::createOne()->_real();
        $attachment = CovenantAttachmentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122(),
        );
        self::assertNotNull($result);

        $repository->remove($result, true);

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $attachment->getId()->toRfc4122(),
        );
        self::assertNull($result);
    }

    public function testFindOneOrNullForDossier(): void
    {
        $dossier = CovenantFactory::createOne()->_real();
        $attachment = CovenantAttachmentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $result = $this->getRepository()->findOneOrNullForDossier(
            $dossier->getId(),
            $attachment->getId(),
        );

        self::assertNotNull($result);
        self::assertEquals($attachment->getId(), $result->getId());

        self::assertNull(
            $this->getRepository()->findOneOrNullForDossier(
                $dossier->getId(),
                Uuid::v6(),
            )
        );
    }

    public function testFindForDossierByPrefixAndNrResultsNullOnDossierMismatch(): void
    {
        $dossier = CovenantFactory::createOne()->_real();
        $attachment = CovenantAttachmentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        $result = $this->getRepository()->findForDossierByPrefixAndNr(
            $dossier->getDocumentPrefix(),
            'MISMATCH',
            $attachment->getId()->toRfc4122()
        );

        self::assertNull($result);
    }

    public function testCreate(): void
    {
        $covenant = CovenantFactory::createOne()->_real();

        $covenantAttachment = CovenantAttachmentFactory::new()->withoutPersisting()->createOne()->_real();

        $createAttachmentCommand = new CreateAttachmentCommand(
            dossierId: $covenant->getId(),
            formalDate: $covenantAttachment->getFormalDate(),
            internalReference: $covenantAttachment->getInternalReference(),
            type: $covenantAttachment->getType(),
            language: $covenantAttachment->getLanguage(),
            grounds: $covenantAttachment->getGrounds(),
            uploadFileReference: 'uploadFileReference',
        );

        $result = $this->getRepository()->create($covenant, $createAttachmentCommand);

        self::assertEquals($covenant, $result->getDossier());
        self::assertEquals($createAttachmentCommand->formalDate, $result->getFormalDate());
        self::assertEquals($createAttachmentCommand->type, $result->getType());
        self::assertEquals($createAttachmentCommand->language, $result->getLanguage());
    }
}
