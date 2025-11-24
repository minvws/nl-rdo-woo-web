<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Shared\Tests\Factory\DepartmentFactory;
use Zenstruck\Foundry\Story;

final class DepartmentStory extends Story
{
    public function build(): void
    {
        foreach (DepartmentEnum::cases() as $department) {
            DepartmentFactory::new()->create([
                'name' => $department->value,
                'slug' => $department->getShortTag(),
                'shortTag' => $department->getShortTag(),
            ]);
        }
    }
}
