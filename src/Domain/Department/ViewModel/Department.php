<?php

declare(strict_types=1);

namespace App\Domain\Department\ViewModel;

readonly class Department
{
    public function __construct(
        public string $name,
        public string $tag,
        public string $url,
    ) {
    }
}
