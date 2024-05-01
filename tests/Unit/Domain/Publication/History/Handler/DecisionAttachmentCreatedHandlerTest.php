<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History\Handler;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentCreatedEvent;
use App\Domain\Publication\History\Handler\DecisionAttachment\DecisionAttachmentCreatedHandler;
use App\Entity\DecisionAttachment;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;

final class DecisionAttachmentCreatedHandlerTest extends UnitTestCase
{
    public function testInvokeOnPublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $decisionAttachment = $this->getDecisionAttachment($fileInfo, $dossier);

        $event = new DecisionAttachmentCreatedEvent($decisionAttachment);

        $historyService = \Mockery::mock(HistoryService::class);
        $historyService
            ->shouldReceive('addDossierEntry')
            ->with(
                $dossier,
                'decision_attachment_created',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_BOTH,
            )
            ->once();

        $handler = new DecisionAttachmentCreatedHandler($historyService);
        $handler->__invoke($event);
    }

    public function testInvokeOnUnpublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $decisionAttachment = $this->getDecisionAttachment($fileInfo, $dossier);

        $event = new DecisionAttachmentCreatedEvent($decisionAttachment);

        $historyService = \Mockery::mock(HistoryService::class);
        $historyService
            ->shouldReceive('addDossierEntry')
            ->with(
                $dossier,
                'decision_attachment_created',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_PRIVATE,
            )
            ->once();

        $handler = new DecisionAttachmentCreatedHandler($historyService);
        $handler->__invoke($event);
    }

    private function getDossier(DossierStatus $status): Dossier
    {
        $dossier = \Mockery::mock(Dossier::class);
        $dossier->shouldReceive('getId')->andReturn('my-dossier-uuid');
        $dossier->shouldReceive('getStatus')->andReturn($status);

        return $dossier;
    }

    private function getFileInfo(string $name, string $type, int $size): FileInfo
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($name);
        $fileInfo->shouldReceive('getType')->andReturn($type);
        $fileInfo->shouldReceive('getSize')->andReturn($size);

        return $fileInfo;
    }

    private function getDecisionAttachment(FileInfo $fileInfo, Dossier $dossier): DecisionAttachment
    {
        $decisionAttachment = \Mockery::mock(DecisionAttachment::class);
        $decisionAttachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $decisionAttachment->shouldReceive('getDossier')->andReturn($dossier);

        return $decisionAttachment;
    }
}
