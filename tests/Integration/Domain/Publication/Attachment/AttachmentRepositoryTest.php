<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class AttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private AttachmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(AttachmentRepository::class);
    }

    public function testFindBySearchTerm(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        $dossierA = AnnualReportFactory::createOne([
            'organisation' => $organisationOne,
        ]);

        $attachmentA = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        // This dossier wont be found because it does not belong to the same organisation
        $dossierB = AnnualReportFactory::createOne([
            'organisation' => $organisationTwo,
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $this->repository->findBySearchTerm('foobar', 10, $organisationOne->_real());

        self::assertCount(1, $result);
        self::assertEquals($attachmentA->_real()->getId(), $result[0]->getId());
    }

    public function testFindBySearchTermFilteredByUuid(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = AnnualReportFactory::createOne([
            'organisation' => $organisation,
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        $dossierB = AnnualReportFactory::createOne([
            'organisation' => $organisation,
        ]);

        $attachmentB = AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $this->repository->findBySearchTerm(
            'foobar',
            10,
            $organisation->_real(),
            dossierId: $dossierB->_real()->getId(),
        );

        self::assertCount(1, $result);
        self::assertEquals($attachmentB->_real()->getId(), $result[0]->getId());
    }

    public function testFindBySearchTermFilteredByType(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = AnnualReportFactory::createOne([
            'organisation' => $organisation,
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        AnnualReportAttachmentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        $dossierB = CovenantFactory::createOne([
            'organisation' => $organisation,
        ]);

        $attachmentB = CovenantAttachmentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $this->repository->findBySearchTerm(
            'foobar',
            10,
            $organisation->_real(),
            dossierType: DossierType::COVENANT,
        );

        self::assertCount(1, $result);
        self::assertEquals($attachmentB->_real()->getId(), $result[0]->getId());
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testGetPublishedAttachmentsIterable(): void
    {
        $iterable = $this->repository->getPublishedAttachmentsIterable();

        /** @var array<int,AbstractAttachment> $allAttachments */
        $allAttachments = iterator_to_array($iterable);

        /** @var non-empty-list<Proxy<AnnualReportAttachment>> $attachments */
        $attachments = WooIndexAnnualReportStory::getPool('attachments');

        $expectedAttachmentUuids = array_map(
            fn (Proxy $attachment): string => $attachment->_real()->getId()->toRfc4122(),
            $attachments,
        );

        $this->assertCount(3, $allAttachments);
        foreach ($allAttachments as $attachment) {
            $this->assertContains($attachment->getId()->toRfc4122(), $expectedAttachmentUuids);
        }
    }
}
