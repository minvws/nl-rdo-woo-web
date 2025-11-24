<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

readonly class Department
{
    public function __construct(
        public string $name,
        public ?string $feedbackContent,
        public ?string $responsibilityContent,
    ) {
    }
}
