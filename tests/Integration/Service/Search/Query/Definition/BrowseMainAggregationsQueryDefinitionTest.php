<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Search\Query\Definition;

use App\Service\Search\Query\Definition\BrowseMainAggregationsQueryDefinition;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('search')]
final class BrowseMainAggregationsQueryDefinitionTest extends KernelTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $this->matchDefinitionToSnapshot(BrowseMainAggregationsQueryDefinition::class);
    }
}
