<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbstractAttachmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AbstractAttachmentRepository
    {
        return self::getContainer()->get(AbstractAttachmentRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testFindBySearchTerm(): void
    {
        $repository = $this->getRepository();

        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        $dossierA = AnnualReportFactory::createOne([
            'organisation' => $organisationOne,
        ]);

        $attachmentA1 = AnnualReportAttachmentFactory::createOne([
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

        $result = $repository->findBySearchTerm('foobar', 10, $organisationOne->_real());

        self::assertCount(1, $result);
        self::assertEquals($attachmentA1->_real()->getId(), $result[0]->getId());
    }
}
