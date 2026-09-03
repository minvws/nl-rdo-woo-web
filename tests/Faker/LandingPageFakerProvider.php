<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Internet;
use Shared\Domain\Publication\Subject\LandingPageSlug;

final class LandingPageFakerProvider extends Internet
{
    public function landingPageSlug(): LandingPageSlug
    {
        return LandingPageSlug::create(static::slug(2));
    }
}
