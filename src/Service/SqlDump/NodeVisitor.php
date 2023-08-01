<?php

declare(strict_types=1);

namespace App\Service\SqlDump;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use Symfony\Component\Console\Output\OutputInterface;

class NodeVisitor extends NodeVisitorAbstract
{
    protected OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function enterNode(Node $node)
    {
        // Check if we're in the up method, and process it
        if ($node instanceof ClassMethod && $node->name == 'up') {
            $this->processUpMethod($node);
        }

        return null;
    }

    public function processUpMethod(ClassMethod $root): void
    {
        foreach ($root->getStmts() ?? [] as $statement) {
            if ($statement != null && $statement->getType() != 'Stmt_Expression') {
                throw new \Exception('Found a non-addSql statement');
            }

            /** @var Expression $statement */
            if (! $statement->expr instanceof Node\Expr\MethodCall || $statement->expr->name != 'addSql') {
                throw new \Exception('Found a non-addSql statement');
            }

            $arg = $statement->expr->args[0]->value ?? null;
            if (! $arg || ! $arg instanceof Node\Scalar\String_) {
                throw new \Exception('Found a non-string addSql statement');
            }

            // Write the SQL output of the statement (first argument) and terminating ;
            $this->output->writeln($arg->value . ';');
        }
    }
}
