<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

final class DocumentFileUpdateRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private DocumentFileUpdateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(DocumentFileUpdateRepository::class);
        Assert::isInstanceOf($repository, DocumentFileUpdateRepository::class);

        $this->repository = $repository;
    }

    public function testHasUpdateForFileSetAndDocument(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne()->_real();
        $document = DocumentFactory::createOne()->_real();

        self::assertFalse(
            $this->repository->hasUpdateForFileSetAndDocument($documentFileSet, $document),
        );

        $documentFileUpdate = DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => $document,
        ])->_real();

        $this->repository->save($documentFileUpdate, true);

        self::assertTrue(
            $this->repository->hasUpdateForFileSetAndDocument($documentFileSet, $document),
        );
    }
}
