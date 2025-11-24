<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search\Query\Definition;

use PHPUnit\Framework\Attributes\Group;
use Shared\Service\Search\Query\Definition\BrowseMainAggregationsQueryDefinition;
use Shared\Tests\Integration\SharedWebTestCase;

#[Group('search')]
final class BrowseMainAggregationsQueryDefinitionTest extends SharedWebTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $this->matchDefinitionToSnapshot(BrowseMainAggregationsQueryDefinition::class);
    }
}
