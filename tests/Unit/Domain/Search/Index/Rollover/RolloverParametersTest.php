<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use PHPUnit\Framework\TestCase;
use Shared\Domain\Search\Index\Rollover\RolloverParameters;

class RolloverParametersTest extends TestCase
{
    public function testRolloverParameters(): void
    {
        $params = new RolloverParameters(13);

        $this->assertEquals(13, $params->getMappingVersion());

        $params->setMappingVersion(5);

        $this->assertEquals(5, $params->getMappingVersion());
    }
}
