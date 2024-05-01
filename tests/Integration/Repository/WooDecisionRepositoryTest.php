<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Judgement;
use App\Repository\WooDecisionRepository;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WooDecisionRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testGetDossierCounts(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createone([
            'pageCount' => 5,
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        DocumentFactory::createone([
            'pageCount' => 2,
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        DocumentFactory::createone([
            'pageCount' => 7,
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        /** @var WooDecisionRepository $wooDecisionRepository */
        $wooDecisionRepository = self::getContainer()->get(WooDecisionRepository::class);

        $result = $wooDecisionRepository->getDossierCounts($wooDecision->object());

        $this->assertSame(3, $result->getDocumentCount());
        $this->assertTrue($result->hasDocuments());
        $this->assertSame(14, $result->getPageCount());
        $this->assertTrue($result->hasPages());
        $this->assertSame(3, $result->getUploadCount());
        $this->assertTrue($result->hasUploads());
    }
}
