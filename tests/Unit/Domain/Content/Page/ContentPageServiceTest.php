<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Content\Page;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Content\Page\ContentPage;
use Shared\Domain\Content\Page\ContentPageException;
use Shared\Domain\Content\Page\ContentPageRepository;
use Shared\Domain\Content\Page\ContentPageService;
use Shared\Domain\Content\Page\ContentPageType;
use Shared\Tests\Unit\UnitTestCase;

class ContentPageServiceTest extends UnitTestCase
{
    private ContentPageService $service;
    private ContentPageRepository&MockInterface $contentPageRepository;

    protected function setUp(): void
    {
        $this->contentPageRepository = Mockery::mock(ContentPageRepository::class);
        $this->service = new ContentPageService($this->contentPageRepository);

        parent::setUp();
    }

    public function testCreateMissingPages(): void
    {
        $existingType = ContentPageType::CONTACT;
        $pages = [
            new ContentPage('other-slug', 'Other', ''),
            new ContentPage($existingType->getSlug(), $existingType->getDefaultTitle(), ''),
        ];

        $this->contentPageRepository->expects('findAll')->andReturn($pages);

        foreach (ContentPageType::cases() as $type) {
            if ($type === $existingType) {
                continue;
            }

            $this->contentPageRepository->expects('save')->with(
                Mockery::on(
                    static function (ContentPage $entity) use ($type): bool {
                        return $entity->getSlug() === $type->getSlug() && $entity->getTitle() === $type->getDefaultTitle();
                    }
                ),
                true,
            );
        }

        $this->service->createMissingPages();
    }

    public function testGetViewModelThrowsExceptionForMissingContentPage(): void
    {
        $type = ContentPageType::CONTACT;

        $this->contentPageRepository->expects('find')->with($type->getSlug())->andReturnNull();

        $this->expectExceptionObject(ContentPageException::forMissing($type));
        $this->service->getViewModel($type);
    }

    public function testGetViewModelSuccessfully(): void
    {
        $type = ContentPageType::CONTACT;
        $contentPage = new ContentPage($type->getSlug(), $expectedTitle = 'foo', $expectedContent = 'bar');

        $this->contentPageRepository->expects('find')->with($type->getSlug())->andReturn($contentPage);

        $viewModel = $this->service->getViewModel($type);

        self::assertEquals($type, $viewModel->type);
        self::assertEquals($expectedTitle, $viewModel->title);
        self::assertEquals($expectedContent, $viewModel->content);
    }
}
