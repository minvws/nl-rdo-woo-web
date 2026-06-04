<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use PublicationApi\Api\Uploads\Attachment\AttachmentUploadProcessor;
use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use PublicationApi\Domain\Upload\UploadValidationService;
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
use Symfony\Component\Validator\ConstraintViolation;

use function hash;

class AttachmentUploadProcessorTest extends PublicationApiTestCase
{
    public function testProcess(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $attachment = WooDecisionAttachmentFactory::createOne();

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadValidationService,
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

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadValidationService,
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

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadValidationService,
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

        $uploadValidationService = Mockery::mock(UploadValidationService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload')
            ->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->never();

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }

    public function testProcessThrowsValidationExceptionWhenUploadValidationFails(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $attachment = WooDecisionAttachmentFactory::createOne();

        $attachmentUploadStatusService = self::fromContainer(AttachmentUploadStatusService::class);

        $violation = new ConstraintViolation('Validation failed', '', [], null, '', null);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([$violation]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $attachmentUploadProcessor = new AttachmentUploadProcessor(
            $attachmentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $this->expectException(ValidationException::class);

        $attachmentUploadProcessor->process($wooDecision, $attachment, $stream);
    }
}
