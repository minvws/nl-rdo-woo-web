<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\MainDocument;

use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbstractMainDocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): AbstractMainDocumentRepository
    {
        return self::getContainer()->get(AbstractMainDocumentRepository::class);
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

        $mainDocumentA1 = ComplaintJudgementDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        ComplaintJudgementDocumentFactory::createOne([
            'dossier' => $dossierA,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Something Important 2022.pdf',
            ]),
        ]);

        // This dossier wont be found because it does not belong to the same organisation
        $dossierb = ComplaintJudgementFactory::createOne([
            'organisation' => $organisationTwo,
        ]);

        ComplaintJudgementDocumentFactory::createOne([
            'dossier' => $dossierb,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'Lorem fooBAR 2075.pdf',
            ]),
        ]);

        $result = $repository->findBySearchTerm('foobar', 10, $organisationOne->_real());

        self::assertCount(1, $result);
        self::assertEquals($mainDocumentA1->_real()->getId(), $result[0]->getId());
    }
}
