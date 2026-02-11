<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Webmozart\Assert\Assert;

final class DocumentFileUpdateRepositoryTest extends SharedWebTestCase
{
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
        $documentFileSet = DocumentFileSetFactory::createOne();
        $document = DocumentFactory::createOne();

        self::assertFalse(
            $this->repository->hasUpdateForFileSetAndDocument($documentFileSet, $document),
        );

        $documentFileUpdate = DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => $document,
        ]);

        $this->repository->save($documentFileUpdate, true);

        self::assertTrue(
            $this->repository->hasUpdateForFileSetAndDocument($documentFileSet, $document),
        );
    }
}
