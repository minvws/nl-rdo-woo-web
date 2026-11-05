<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Shared\Command\RecalculateFileHashes;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

use function hash;

final class RecalculateFileHashesTest extends SharedWebTestCase
{
    public function testUpdatesMissingAndOutdatedHashes(): void
    {
        self::bootKernel();

        $mainDocumentContent = $this->getFaker()->text();
        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => null]),
        ]);

        $attachmentContent = $this->getFaker()->text();
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => $this->getFaker()->sha256()]),
        ]);

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('retrieveResourceEntity')
            ->with(Mockery::on(static function (EntityWithFileInfo $entity) use ($mainDocument): bool {
                return $entity->getId()->equals($mainDocument->getId());
            }))
            ->andReturn(Utils::streamFor($mainDocumentContent)->detach());
        $entityStorageService->expects('retrieveResourceEntity')
            ->with(Mockery::on(static function (EntityWithFileInfo $entity) use ($attachment): bool {
                return $entity->getId()->equals($attachment->getId());
            }))
            ->andReturn(Utils::streamFor($attachmentContent)->detach());
        self::getContainer()->set(EntityStorageService::class, $entityStorageService);

        $kernel = self::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $commandTester = new CommandTester($application->find(RecalculateFileHashes::COMMAND_NAME));
        $commandTester->execute([], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->clear();

        $updatedMainDocument = $entityManager->find(DraftDecisionMainDocument::class, $mainDocument->getId());
        self::assertInstanceOf(DraftDecisionMainDocument::class, $updatedMainDocument);
        self::assertSame(hash('sha256', $mainDocumentContent), $updatedMainDocument->getFileInfo()->getHash());

        $updatedAttachment = $entityManager->find(DraftDecisionAttachment::class, $attachment->getId());
        self::assertInstanceOf(DraftDecisionAttachment::class, $updatedAttachment);
        self::assertSame(hash('sha256', $attachmentContent), $updatedAttachment->getFileInfo()->getHash());
    }
}
