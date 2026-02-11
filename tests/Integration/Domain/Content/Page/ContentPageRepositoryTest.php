<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Content\Page;

use Shared\Domain\Content\Page\ContentPageRepository;
use Shared\Tests\Factory\Content\Page\ContentPageFactory;
use Shared\Tests\Integration\SharedWebTestCase;

class ContentPageRepositoryTest extends SharedWebTestCase
{
    private ContentPageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(ContentPageRepository::class);
    }

    public function testSave(): void
    {
        $contentPage = ContentPageFactory::createOne(['slug' => 'foo']);

        $this->repository->save($contentPage, true);

        $result = $this->repository->find('foo');
        self::assertEquals($contentPage, $result);
    }

    public function testFindAllSortedBySlug(): void
    {
        ContentPageFactory::createSequence([
            ['slug' => 'D'],
            ['slug' => 'B'],
            ['slug' => 'A'],
            ['slug' => 'C'],
            ['slug' => 'E'],
        ]);

        $result = $this->repository->findAllSortedBySlug();
        self::assertCount(5, $result);

        self::assertEquals('A', $result[0]->getSlug());
        self::assertEquals('B', $result[1]->getSlug());
        self::assertEquals('C', $result[2]->getSlug());
        self::assertEquals('D', $result[3]->getSlug());
        self::assertEquals('E', $result[4]->getSlug());
    }
}
