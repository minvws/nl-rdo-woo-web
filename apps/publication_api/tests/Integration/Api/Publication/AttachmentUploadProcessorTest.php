<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use PublicationApi\Api\Publication\AttachmentUploadProcessor;
use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

use function hash;

class AttachmentUploadProcessorTest extends PublicationApiTestCase
{
    public function testProcess(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $attachment = WooDecisionAttachmentFactory::createOne();

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadService,
            $messageBus,
        );

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }

    public function testProcessWhenAttachmentWithOtherHashExists(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $attachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => self::getFaker()->sha256(),
            ]),
        ]);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadService,
            $messageBus,
        );

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }

    public function testProcessWhenAttachmentWithSameHashExistsButNotYetProcessed(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $content = self::getFaker()->word();
        $attachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => hash(FileHashService::HASH_ALGORITHM, $content),
                'uploaded' => false,
            ]),
        ]);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadService,
            $messageBus,
        );

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }

    public function testProcessWhenAttachmentWithSameHashExistsAndProcessed(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $content = self::getFaker()->word();
        $stream = Utils::streamFor($content);
        $attachment = WooDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => hash(FileHashService::HASH_ALGORITHM, $content),
            ]),
        ]);

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload')
            ->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->never();

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadService,
            $messageBus,
        );

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }
}
