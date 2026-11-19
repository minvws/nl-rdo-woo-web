<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Utils;
use Mockery;
use Mockery\Matcher\Closure as EntityMatcher;
use Mockery\MockInterface;
use RuntimeException;
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
use Symfony\Component\Console\Output\OutputInterface;
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

        $entityStorageService = $this->mockEntityStorageService();
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($mainDocument))
            ->andReturn(new NoSeekStream(Utils::streamFor($mainDocumentContent)));
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($attachment))
            ->andReturn(Utils::streamFor($attachmentContent));

        $commandTester = $this->createCommandTester();
        $commandTester->execute([], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString('Checked 2 files, updated 2 hashes', $commandTester->getDisplay());

        self::assertSame(hash('sha256', $mainDocumentContent), $this->findMainDocumentHash($mainDocument));
        self::assertSame(hash('sha256', $attachmentContent), $this->findAttachmentHash($attachment));
    }

    public function testContinuesAndReportsWhenFileCannotBeRead(): void
    {
        self::bootKernel();

        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => null, 'name' => 'missing-file.pdf']),
        ]);

        $attachmentContent = $this->getFaker()->text();
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => null]),
        ]);

        $entityStorageService = $this->mockEntityStorageService();
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($mainDocument))
            ->andReturnNull();
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($attachment))
            ->andReturn(Utils::streamFor($attachmentContent));

        $commandTester = $this->createCommandTester();
        $commandTester->execute([], ['interactive' => false]);

        self::assertSame(RecalculateFileHashes::PARTIAL_FAILURE, $commandTester->getStatusCode());

        $display = $commandTester->getDisplay();
        self::assertStringContainsString('1 files could not be read from storage', $display);
        self::assertStringContainsString($mainDocument->getId()->toRfc4122(), $display);
        self::assertStringContainsString($mainDocument->getDossier()->getId()->toRfc4122(), $display);
        self::assertStringContainsString('missing-file.pdf', $display);
        self::assertStringContainsString('Could not read file from storage', $display);

        self::assertNull($this->findMainDocumentHash($mainDocument));
        self::assertSame(hash('sha256', $attachmentContent), $this->findAttachmentHash($attachment));
    }

    public function testContinuesAndReportsWhenProcessingThrows(): void
    {
        self::bootKernel();

        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => null]),
        ]);

        $attachmentContent = $this->getFaker()->text();
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => null]),
        ]);

        $entityStorageService = $this->mockEntityStorageService();
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($mainDocument))
            ->andThrow(new RuntimeException('Connection reset'));
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($attachment))
            ->andReturn(Utils::streamFor($attachmentContent));

        $commandTester = $this->createCommandTester();
        $commandTester->execute([], ['interactive' => false]);

        self::assertSame(RecalculateFileHashes::PARTIAL_FAILURE, $commandTester->getStatusCode());

        $display = $commandTester->getDisplay();
        self::assertStringContainsString('1 files failed with an error', $display);
        self::assertStringContainsString($mainDocument->getId()->toRfc4122(), $display);
        self::assertStringContainsString('Connection reset', $display);

        self::assertNull($this->findMainDocumentHash($mainDocument));
        self::assertSame(hash('sha256', $attachmentContent), $this->findAttachmentHash($attachment));
    }

    public function testDryRunListsChangedHashesWhenVerbose(): void
    {
        self::bootKernel();

        $oldHash = $this->getFaker()->sha256();
        $content = $this->getFaker()->text();
        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new(['hash' => $oldHash]),
        ]);

        $entityStorageService = $this->mockEntityStorageService();
        $entityStorageService->expects('retrieveResourceEntityAsStream')
            ->with(self::isEntity($mainDocument))
            ->andReturn(Utils::streamFor($content));

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['--dry-run' => true], [
            'interactive' => false,
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $display = $commandTester->getDisplay();
        self::assertStringContainsString('Hashes that would change', $display);
        self::assertStringContainsString($mainDocument->getId()->toRfc4122(), $display);
        self::assertStringContainsString($oldHash, $display);
        self::assertStringContainsString(hash('sha256', $content), $display);
        self::assertStringContainsString('would update 1 hashes', $display);

        self::assertSame($oldHash, $this->findMainDocumentHash($mainDocument));
    }

    private function mockEntityStorageService(): EntityStorageService&MockInterface
    {
        $entityStorageService = Mockery::mock(EntityStorageService::class);
        self::getContainer()->set(EntityStorageService::class, $entityStorageService);

        return $entityStorageService;
    }

    private static function isEntity(EntityWithFileInfo $expectedEntity): EntityMatcher
    {
        return Mockery::on(static fn (EntityWithFileInfo $entity): bool => $entity->getId()->equals($expectedEntity->getId()));
    }

    private function createCommandTester(): CommandTester
    {
        $kernel = self::$kernel;
        Assert::isInstanceOf($kernel, KernelInterface::class);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        return new CommandTester($application->find(RecalculateFileHashes::COMMAND_NAME));
    }

    private function findMainDocumentHash(DraftDecisionMainDocument $mainDocument): ?string
    {
        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->clear();

        $entity = $entityManager->find(DraftDecisionMainDocument::class, $mainDocument->getId());
        self::assertInstanceOf(DraftDecisionMainDocument::class, $entity);

        return $entity->getFileInfo()->getHash();
    }

    private function findAttachmentHash(DraftDecisionAttachment $attachment): ?string
    {
        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->clear();

        $entity = $entityManager->find(DraftDecisionAttachment::class, $attachment->getId());
        self::assertInstanceOf(DraftDecisionAttachment::class, $entity);

        return $entity->getFileInfo()->getHash();
    }
}
