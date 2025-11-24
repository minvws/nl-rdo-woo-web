<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search;

use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Tests\Integration\SharedWebTestCase;

#[Group('search')]
final class ConfigFactoryTest extends SharedWebTestCase
{
    public function testItCanBeInitialized(): void
    {
        self::assertInstanceOf(
            SearchParametersFactory::class,
            self::getContainer()->get(SearchParametersFactory::class)
        );
    }
}
