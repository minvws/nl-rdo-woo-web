<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\SqlWalker;
use Webmozart\Assert\Assert;

class SortNullsLastWalker extends SqlWalker
{
    public function walkOrderByClause(OrderByClause $orderByClause): string
    {
        $sql = parent::walkOrderByClause($orderByClause);

        $platform = $this->getConnection()->getDatabasePlatform();
        if (! $platform instanceof PostgreSQLPlatform) {
            throw new \RuntimeException('Unsupported database platform: ' . $platform::class);
        }

        $sql = preg_replace('/([A-Z0-9_]+) (ASC|DESC)/i', '$1 $2 NULLS LAST', $sql);
        Assert::string($sql);

        return $sql;
    }
}
