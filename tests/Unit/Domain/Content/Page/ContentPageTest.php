<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Content\Page;

use Shared\Domain\Content\Page\ContentPage;
use Shared\Tests\Unit\UnitTestCase;

class ContentPageTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $contentPage = new ContentPage(
            slug: $slug = 'foo',
            title: $title = 'bar',
            content: $content = 'baz',
        );

        $this->assertEquals($slug, $contentPage->getSlug());
        $this->assertEquals($title, $contentPage->getTitle());
        $this->assertEquals($content, $contentPage->getContent());
    }

    public function testSetters(): void
    {
        $contentPage = new ContentPage(
            slug: 'foo',
            title: 'bar',
            content: 'baz',
        );

        $contentPage->setTitle($newTitle = 'bar2');
        $contentPage->setContent($newContent = 'baz2');

        $this->assertEquals($newTitle, $contentPage->getTitle());
        $this->assertEquals($newContent, $contentPage->getContent());
    }
}
