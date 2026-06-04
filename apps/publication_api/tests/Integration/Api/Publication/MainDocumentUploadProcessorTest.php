<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use PublicationApi\Api\Publication\MainDocumentUploadProcessor;
use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
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

use function hash;

class MainDocumentUploadProcessorTest extends PublicationApiTestCase
{
    public function testProcess(): void
    {
        $wooDecision = WooDecisionFactory::createOne();
        $stream = Utils::streamFor(self::getFaker()->word());
        $mainDocument = WooDecisionMainDocumentFactory::createOne();

        $mainDocumentUploadStatusService = self::fromContainer(MainDocumentUploadStatusService::class);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
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

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
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

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload');

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->andReturn(new Envelope(new stdClass(), [new HandledStamp(true, self::getFaker()->word())]));

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
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

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload')
            ->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->never();

        $mainDocumentUploadProcessor = new MainDocumentUploadProcessor(
            $mainDocumentUploadStatusService,
            $uploadService,
            $messageBus,
        );

        $mainDocumentUploadProcessor->process($wooDecision, $mainDocument, $stream);
    }
}
