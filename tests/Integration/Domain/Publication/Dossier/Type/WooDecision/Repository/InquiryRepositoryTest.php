<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\InquiryRepository;
use App\Tests\Factory\DocumentFactory;
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

        $documents = DocumentFactory::new()->createMany(8);

        $wooDecisions[0]->_real()->addDocument($documents[0]->_real());
        $wooDecisions[0]->_real()->addDocument($documents[1]->_real());

        $wooDecisions[1]->_real()->addDocument($documents[2]->_real());
        $wooDecisions[1]->_real()->addDocument($documents[3]->_real());
        $wooDecisions[1]->_real()->addDocument($documents[4]->_real());

        $wooDecisions[2]->_real()->addDocument($documents[5]->_real());
        $wooDecisions[2]->_real()->addDocument($documents[6]->_real());

        $inquiry = InquiryFactory::new([
            'organisation' => $organisation,
            'dossiers' => $wooDecisions,
            'documents' => $documents,
        ])->create();

        $result = $this->repository->getDocCountsByDossier($inquiry->_real());

        self::assertCount(3, $result);
        self::assertSame($result[0]['dossierNr'], $wooDecisions[2]->_real()->getDossierNr());
        self::assertSame($result[1]['dossierNr'], $wooDecisions[1]->_real()->getDossierNr());
        self::assertSame($result[2]['dossierNr'], $wooDecisions[0]->_real()->getDossierNr());
    }
}
