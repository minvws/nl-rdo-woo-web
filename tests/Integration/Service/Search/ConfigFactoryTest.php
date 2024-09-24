<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Search;

use App\Domain\Search\Query\SearchParametersFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('search')]
final class ConfigFactoryTest extends KernelTestCase
{
    public function testItCanBeInitialized(): void
    {
        self::assertInstanceOf(
            SearchParametersFactory::class,
            self::getContainer()->get(SearchParametersFactory::class)
        );
    }
}
