<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use PublicationApi\Api\Uploads\MainDocument\MainDocumentUploadProcessor;
use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadValidationService;
use PublicationApi\Tests\Integration\PublicationApiTestCase;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\FileHashService;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Validator\ConstraintViolation;

use function hash;

class MainDocumentUploadProcessorTest extends PublicationApiTestCase
{
    public function testProcess(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $mainDocument = WooDecisionMainDocumentFactory::createOne();

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }

    public function testProcessWhenMainDocumentWithOtherHashExists(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => self::getFaker()->sha256(),
            ]),
        ]);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }

    public function testProcessWhenMainDocumentWithOtherHashExistsButNotYetProcessed(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $content = self::getFaker()->word();
        $stream = Utils::streamFor($content);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => hash(FileHashService::HASH_ALGORITHM, $content),
                'uploaded' => false,
            ]),
        ]);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }

    public function testProcessWhenMainDocumentWithSameHashExistsAndProcessed(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $content = self::getFaker()->word();
        $stream = Utils::streamFor($content);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'hash' => hash(FileHashService::HASH_ALGORITHM, $content),
            ]),
        ]);

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload')
            ->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->never();

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }

    public function testProcessThrowsValidationExceptionWhenUploadValidationFails(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $mainDocument = WooDecisionMainDocumentFactory::createOne();

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $violation = new ConstraintViolation('Validation failed', '', [], null, '', null);

        $uploadValidationService = Mockery::mock(UploadValidationService::class);
        $uploadValidationService->expects('getValidationErrorsForUpload')->andReturn([$violation]);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadValidationService,
            $uploadService,
            $messageBus,
        );

        $this->expectException(ValidationException::class);

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }
}
