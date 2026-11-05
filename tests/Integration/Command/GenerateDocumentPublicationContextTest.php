<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Command\GenerateDocumentPublicationContext;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

use function sprintf;
use function strtolower;
use function strtoupper;

final class GenerateDocumentPublicationContextTest extends SharedWebTestCase
{
    public function testExecute(): void
    {
        $uppercasePublicationContext = strtoupper($this->getFaker()->unique()->lexify('????????'));
        $uppercaseDocumentId = strtoupper($this->getFaker()->unique()->lexify('????????'));
        $uppercaseDocumentUuid = $this->createDocument(
            sprintf('%s-%s', $uppercasePublicationContext, $uppercaseDocumentId),
            $uppercaseDocumentId,
            $uppercasePublicationContext,
        );

        $missingPublicationContext = strtoupper($this->getFaker()->unique()->lexify('????????'));
        $missingDocumentId = strtoupper($this->getFaker()->unique()->lexify('????????'));
        $missingPublicationContextDocumentUuid = $this->createDocument(
            sprintf('%s-%s', $missingPublicationContext, $missingDocumentId),
            $missingDocumentId,
            null,
        );

        $mismatchPublicationContext = $this->getFaker()->unique()->lexify('????????');
        $mismatchDocumentNumberSuffix = $this->getFaker()->unique()->lexify('????????');
        $mismatchDocumentId = $this->getFaker()->unique()->lexify('????????');
        $mismatchDocumentUuid = $this->createDocument(
            sprintf('%s-%s', $mismatchPublicationContext, $mismatchDocumentNumberSuffix),
            $mismatchDocumentId,
            null,
        );

        $invalidPublicationContext = $this->getFaker()->unique()->lexify('???? ????');
        $invalidDocumentId = $this->getFaker()->unique()->lexify('????????');
        $invalidDocumentIdDocumentUuid = $this->createDocument(
            sprintf('%s-%s', $invalidPublicationContext, $invalidDocumentId),
            $invalidDocumentId,
            null,
        );

        $existingPublicationContext = $this->getFaker()->publicationContext();
        $existingDocument = DocumentFactory::createOne(['publicationContext' => $existingPublicationContext]);
        $existingDocumentNumber = $existingDocument->getDocumentNumber()->toString();
        $existingDocumentId = $existingDocument->getDocumentId()->toString();

        Assert::isInstanceOf(self::$kernel, KernelInterface::class);

        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        $commandTester = new CommandTester($application->find(GenerateDocumentPublicationContext::COMMAND_NAME));
        $commandTester->execute([], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        $display = $commandTester->getDisplay();
        self::assertStringContainsString('Lowercased 2 document numbers', $display);
        self::assertStringContainsString('Lowercased 1 publication contexts', $display);
        self::assertStringContainsString('Lowercased 2 document ids', $display);
        self::assertStringContainsString('Finished: processed 3, updated 1, skipped 2', $display);
        self::assertStringContainsString('PublicationContext failed for documentNumber', $display);

        $this->assertDocument(
            $uppercaseDocumentUuid,
            strtolower(sprintf('%s-%s', $uppercasePublicationContext, $uppercaseDocumentId)),
            strtolower($uppercaseDocumentId),
            strtolower($uppercasePublicationContext),
        );

        $this->assertDocument(
            $missingPublicationContextDocumentUuid,
            strtolower(sprintf('%s-%s', $missingPublicationContext, $missingDocumentId)),
            strtolower($missingDocumentId),
            strtolower($missingPublicationContext),
        );

        $this->assertDocument(
            $mismatchDocumentUuid,
            sprintf('%s-%s', $mismatchPublicationContext, $mismatchDocumentNumberSuffix),
            $mismatchDocumentId,
            null,
        );

        $this->assertDocument(
            $invalidDocumentIdDocumentUuid,
            sprintf('%s-%s', $invalidPublicationContext, $invalidDocumentId),
            $invalidDocumentId,
            null,
        );

        $this->assertDocument(
            $existingDocument->getId(),
            $existingDocumentNumber,
            $existingDocumentId,
            $existingPublicationContext->toString(),
        );
    }

    private function createDocument(string $documentNumber, string $documentId, ?string $publicationContext): Uuid
    {
        $uuid = DocumentFactory::createOne()->getId();

        self::fromContainer(EntityManagerInterface::class)->getConnection()->executeStatement(
            'UPDATE document
                SET document_number = :documentNumber, document_id = :documentId, publication_context = :publicationContext
                WHERE id = :id',
            [
                'documentNumber' => $documentNumber,
                'documentId' => $documentId,
                'publicationContext' => $publicationContext,
                'id' => (string) $uuid,
            ],
        );

        return $uuid;
    }

    private function assertDocument(Uuid $uuid, string $documentNumber, string $documentId, ?string $publicationContext): void
    {
        self::assertSame(
            [
                'document_number' => $documentNumber,
                'document_id' => $documentId,
                'publication_context' => $publicationContext,
            ],
            self::fromContainer(EntityManagerInterface::class)->getConnection()->fetchAssociative(
                'SELECT document_number, document_id, publication_context FROM document WHERE id = :id',
                ['id' => (string) $uuid],
            ),
        );
    }
}
