<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\Where;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class WhereTest extends UnitTestCase
{
    public function testExecuteWithInvalidUrl(): void
    {
        $url = $this->getFaker()->url();

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $documentRepository = Mockery::mock(DocumentRepository::class);
        $matcher = Mockery::mock(UrlMatcherInterface::class);

        $command = new Where($wooDecisionRepository, $documentRepository, $matcher);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'url' => $url,
        ]);

        self::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }
}
