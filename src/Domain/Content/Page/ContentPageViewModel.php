<?php

declare(strict_types=1);

namespace App\Domain\Content\Page;

class ContentPageViewModel
{
    public function __construct(
        public ContentPageType $type,
        public string $title,
        public string $content,
    ) {
    }
}
