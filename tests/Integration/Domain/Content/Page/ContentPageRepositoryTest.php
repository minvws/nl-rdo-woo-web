<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Content\Page;

use App\Domain\Content\Page\ContentPageRepository;
use App\Tests\Factory\Content\Page\ContentPageFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContentPageRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private ContentPageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(ContentPageRepository::class);
    }

    public function testSave(): void
    {
        $contentPage = ContentPageFactory::createOne(['slug' => 'foo'])->_real();

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
