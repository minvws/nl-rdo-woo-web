<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\InquiryFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

final class InquiryRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private InquiryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(InquiryRepository::class);
        Assert::isInstanceOf($repository, InquiryRepository::class);

        $this->repository = $repository;
    }

    public function testGetDocCountsByDossier(): void
    {
        $organisation = OrganisationFactory::new()->create();
        $wooDecisions = WooDecisionFactory::new()
            ->sequence([
                ['decisionDate' => CarbonImmutable::createFromMutable($this->getFaker()->dateTimeBetween('-2 years', '-1 years'))],
                ['decisionDate' => CarbonImmutable::createFromMutable($this->getFaker()->dateTimeBetween('-6 months', '-3 months'))],
                ['decisionDate' => CarbonImmutable::createFromMutable($this->getFaker()->dateTimeBetween('-2 months', 'now'))],
            ])
            ->create([
                'organisation' => $organisation,
                'status' => $this->getFaker()->randomElement([DossierStatus::PREVIEW, DossierStatus::PUBLISHED]),
            ]);

        $documentsForWooDecisionOne = DocumentFactory::createMany(2, ['dossiers' => [$wooDecisions[0]]]);
        $documentsForWooDecisionTwo = DocumentFactory::createMany(2, ['dossiers' => [$wooDecisions[1]]]);
        $documentsForWooDecisionThree = DocumentFactory::createMany(2, ['dossiers' => [$wooDecisions[2]]]);

        $inquiry = InquiryFactory::new([
            'organisation' => $organisation,
            'dossiers' => $wooDecisions,
            'documents' => [
                ...$documentsForWooDecisionOne,
                ...$documentsForWooDecisionTwo,
                ...$documentsForWooDecisionThree,
            ],
        ])->create();

        $result = $this->repository->getDocCountsByDossier($inquiry->_real());

        self::assertCount(3, $result);
        self::assertSame($result[0]['dossierNr'], $wooDecisions[2]->_real()->getDossierNr());
        self::assertSame($result[1]['dossierNr'], $wooDecisions[1]->_real()->getDossierNr());
        self::assertSame($result[2]['dossierNr'], $wooDecisions[0]->_real()->getDossierNr());
    }

    public function testGetDocumentsForBatchDownload(): void
    {
        $wooDecisionA = WooDecisionFactory::createOne();
        $wooDecisionB = WooDecisionFactory::createOne();

        // Not uploaded, so should not be included
        $docA = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => false,
            ]),
        ]);

        $docB = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Suspended, so should not be included
        $docC = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Not public, so should not be included
        $docD = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        // Other dossier, so should not be included
        $docE = DocumentFactory::createone([
            'dossiers' => [$wooDecisionB],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$wooDecisionA->_real()],
            'documents' => [$docA, $docB, $docC, $docD, $docE],
        ]);

        $result = $this->repository
            ->getDocumentsForBatchDownload($inquiry->_real(), $wooDecisionA->_real())
            ->getQuery()
            ->getResult();

        $this->assertEquals([$docB->_real()], $result);
    }
}
