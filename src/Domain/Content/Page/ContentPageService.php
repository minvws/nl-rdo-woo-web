<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Page;

use Doctrine\Common\Collections\ArrayCollection;

readonly class ContentPageService
{
    public function __construct(
        private ContentPageRepository $contentPageRepository,
    ) {
    }

    public function createMissingPages(): void
    {
        $existingPages = new ArrayCollection($this->contentPageRepository->findAll());
        $existingSlugs = $existingPages->map(fn (ContentPage $page) => $page->getSlug());

        foreach (ContentPageType::cases() as $type) {
            if (! $existingSlugs->contains($type->getSlug())) {
                $contentPage = new ContentPage(
                    $type->getSlug(),
                    $type->getDefaultTitle(),
                    '',
                );

                $this->contentPageRepository->save($contentPage, true);
            }
        }
    }

    public function getViewModel(ContentPageType $type): ContentPageViewModel
    {
        $contentPage = $this->contentPageRepository->find($type->getSlug());
        if ($contentPage === null) {
            throw ContentPageException::forMissing($type);
        }

        return new ContentPageViewModel(
            $type,
            $contentPage->getTitle(),
            $contentPage->getContent(),
        );
    }
}
