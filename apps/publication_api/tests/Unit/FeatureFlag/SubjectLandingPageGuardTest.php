<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\FeatureFlag\SubjectLandingPageGuard;
use Shared\Tests\Unit\UnitTestCase;

final class SubjectLandingPageGuardTest extends UnitTestCase
{
    public function testAssertEnabledThrowsWhenFeatureIsDisabled(): void
    {
        $guard = new SubjectLandingPageGuard(false);

        $this->expectException(ValidationException::class);

        $guard->assertEnabled();
    }
}
