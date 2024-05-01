<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Form\FormInterface;

class WithDrawDocumentCommandTest extends MockeryTestCase
{
    public function testFromForm(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $document = \Mockery::mock(Document::class);

        $reason = WithdrawReason::SUSPENDED_DOCUMENT;
        $reasonField = \Mockery::mock(FormInterface::class);
        $reasonField->shouldReceive('getData')->andReturn($reason);

        $explanation = 'foo';
        $explanationField = \Mockery::mock(FormInterface::class);
        $explanationField->shouldReceive('getData')->andReturn($explanation);

        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('get')->with('reason')->andReturn($reasonField);
        $form->shouldReceive('get')->with('explanation')->andReturn($explanationField);

        $command = WithDrawDocumentCommand::fromForm($dossier, $document, $form);

        self::assertEquals($command->dossier, $dossier);
        self::assertEquals($command->reason, $reason);
        self::assertEquals($command->explanation, $explanation);
    }
}
