<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Webmozart\Assert\Assert;

/**
 * Provides a way to access an entity's discriminator field in DQL queries.
 *
 * Based on: https://gist.github.com/jasonhofer/8420677
 */
class TypeFunction extends FunctionNode
{
    public string $dqlAlias;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $meta = $sqlWalker->getMetadataForDqlAlias($this->dqlAlias);
        $tableAlias = $sqlWalker->getSQLTableAlias($meta->getTableName(), $this->dqlAlias);

        if (! isset($meta->discriminatorColumn['name'])) {
            throw QueryException::semanticalError('TYPE() only supports entities with a discriminator column.');
        }

        $name = $meta->discriminatorColumn['name'];
        Assert::string($name);

        return sprintf('%s.%s', $tableAlias, $name);
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->dqlAlias = $parser->IdentificationVariable();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
