<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Theme\ViewModel;

readonly class Theme
{
    public function __construct(
        public string $urlName,
        public string $menuName,
        public string $pageTitle,
        public string $pageText,
    ) {
    }
}
