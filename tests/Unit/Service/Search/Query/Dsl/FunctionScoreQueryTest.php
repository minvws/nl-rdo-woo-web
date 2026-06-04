<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
use Shared\Service\Search\Query\Dsl\FunctionScoreQuery;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

final class FunctionScoreQueryTest extends UnitTestCase
{
    public function testBuildWrapsBaseQueryAndFunctions(): void
    {
        $query = new class implements QueryInterface {
            /**
             * @return array<string, mixed>
             */
            public function build(): array
            {
                return ['match_all' => new stdClass()];
            }
        };

        $functionScoreQuery = new FunctionScoreQuery(
            query: $query,
            functions: [
                [
                    'filter' => ['term' => ['decision' => 'public']],
                    'weight' => 1.0,
                ],
            ],
        );

        $built = $functionScoreQuery->build();

        $functionScore = $built['function_score'];
        self::assertIsArray($functionScore);
        self::assertSame('multiply', $functionScore['score_mode']);
        self::assertSame('multiply', $functionScore['boost_mode']);
        self::assertSame(
            [
                [
                    'filter' => ['term' => ['decision' => 'public']],
                    'weight' => 1.0,
                ],
            ],
            $functionScore['functions'],
        );
        self::assertArrayHasKey('query', $functionScore);
        self::assertIsArray($functionScore['query']);
        self::assertArrayHasKey('match_all', $functionScore['query']);
    }
}
