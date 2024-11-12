<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MainDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): MainDocumentRepository
    {
        /** @var MainDocumentRepository */
        return self::getContainer()->get(MainDocumentRepository::class);
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

        $dossierA = ComplaintJudgementFactory::createOne([
            'organisation' => $organisationOne,
        ]);

        $mainDocumentA = ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        // This dossier wont be found because it does not belong to the same organisation
        $dossierB = ComplaintJudgementFactory::createOne([
            'organisation' => $organisationTwo,
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $repository->findBySearchTerm('foobar', 10, $organisationOne->_real());

        self::assertCount(1, $result);
        self::assertEquals($mainDocumentA->_real()->getId(), $result[0]->getId());
    }

    public function testFindBySearchTermFilteredByUuid(): void
    {
        $repository = $this->getRepository();

        $organisation = OrganisationFactory::createOne();

        $dossierA = ComplaintJudgementFactory::createOne([
            'organisation' => $organisation,
        ]);

        $mainDocumentA = ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        $dossierB = ComplaintJudgementFactory::createOne([
            'organisation' => $organisation,
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $repository->findBySearchTerm(
            'foobar',
            10,
            $organisation->_real(),
            dossierId: $dossierA->_real()->getId(),
        );

        self::assertCount(1, $result);
        self::assertEquals($mainDocumentA->_real()->getId(), $result[0]->getId());
    }

    public function testFindBySearchTermFilteredByType(): void
    {
        $repository = $this->getRepository();

        $organisation = OrganisationFactory::createOne();

        $dossierA = ComplaintJudgementFactory::createOne([
            'organisation' => $organisation,
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        $dossierB = AnnualReportFactory::createOne([
            'organisation' => $organisation,
        ]);

        $mainDocumentB = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossierB,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $repository->findBySearchTerm(
            'foobar',
            10,
            $organisation->_real(),
            dossierType: DossierType::ANNUAL_REPORT,
        );

        self::assertCount(1, $result);
        self::assertEquals($mainDocumentB->_real()->getId(), $result[0]->getId());
    }
}
