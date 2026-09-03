<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Shared\Command\OrganisationPrefixMigrationCommand;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class OrganisationPrefixMigrationCommandTest extends UnitTestCase
{
    public function testMigratesFirstPrefixAndArchivesOtherActivePrefixes(): void
    {
        $organisation = new Organisation();
        $organisation->setName('Organisation A');

        $selected = new DocumentPrefix('AAA-1');
        $other = new DocumentPrefix('BBB-1');
        $organisation->addDocumentPrefix($selected);
        $organisation->addDocumentPrefix($other);

        $organisations = Mockery::mock(OrganisationRepository::class);
        $organisations->expects('getAllSortedByName')->andReturn([$organisation]);

        $prefixes = Mockery::mock(DocumentPrefixRepository::class);
        $prefixes->expects('getAlphabeticallyFirstByOrganisation')
            ->with($organisation)
            ->andReturn($selected);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $tester = new CommandTester(
            new OrganisationPrefixMigrationCommand($organisations, $prefixes, $entityManager),
        );

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame('AAA-1', $organisation->getPrefix()?->toString());
        self::assertFalse($selected->isArchived());
        self::assertTrue($other->isArchived());
        self::assertStringContainsString('Migrated: 1', $tester->getDisplay());
        self::assertStringContainsString('Skipped: 0', $tester->getDisplay());
        self::assertStringContainsString('Failed: 0', $tester->getDisplay());
    }

    public function testSkipsOrganisationWithExistingPrefix(): void
    {
        $organisation = new Organisation();
        $organisation->setName('Organisation A');
        $organisation->setPrefix(OrganisationPrefix::create('PRE-1'));

        $organisations = Mockery::mock(OrganisationRepository::class);
        $organisations->expects('getAllSortedByName')->andReturn([$organisation]);

        $prefixes = Mockery::mock(DocumentPrefixRepository::class);
        $prefixes->expects('getAlphabeticallyFirstByOrganisation')->never();

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $tester = new CommandTester(
            new OrganisationPrefixMigrationCommand($organisations, $prefixes, $entityManager),
        );

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame('PRE-1', $organisation->getPrefix()?->toString());
        self::assertStringContainsString('Migrated: 0', $tester->getDisplay());
        self::assertStringContainsString('Skipped: 1', $tester->getDisplay());
        self::assertStringContainsString('Failed: 0', $tester->getDisplay());
    }

    public function testReportsMissingPrefixAfterMigratingOtherOrganisations(): void
    {
        $migratedOrganisation = new Organisation();
        $migratedOrganisation->setName('Organisation A');
        $selected = new DocumentPrefix('AAA-1');
        $migratedOrganisation->addDocumentPrefix($selected);

        $missingOrganisation = new Organisation();
        $missingOrganisation->setName('Organisation B');

        $organisations = Mockery::mock(OrganisationRepository::class);
        $organisations->expects('getAllSortedByName')
            ->andReturn([$migratedOrganisation, $missingOrganisation]);

        $prefixes = Mockery::mock(DocumentPrefixRepository::class);
        $prefixes->expects('getAlphabeticallyFirstByOrganisation')
            ->with($migratedOrganisation)
            ->andReturn($selected);
        $prefixes->expects('getAlphabeticallyFirstByOrganisation')
            ->with($missingOrganisation)
            ->andReturn(null);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $tester = new CommandTester(
            new OrganisationPrefixMigrationCommand($organisations, $prefixes, $entityManager),
        );

        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertSame('AAA-1', $migratedOrganisation->getPrefix()?->toString());
        self::assertNull($missingOrganisation->getPrefix());
        self::assertStringContainsString('Organisation B', $tester->getDisplay());
        self::assertStringContainsString('Migrated: 1', $tester->getDisplay());
        self::assertStringContainsString('Skipped: 0', $tester->getDisplay());
        self::assertStringContainsString('Failed: 1', $tester->getDisplay());
    }

    public function testReportsInvalidLegacyPrefix(): void
    {
        $organisation = new Organisation();
        $organisation->setName('Organisation A');
        $invalidPrefix = new DocumentPrefix('BAD');
        $organisation->addDocumentPrefix($invalidPrefix);

        $organisations = Mockery::mock(OrganisationRepository::class);
        $organisations->expects('getAllSortedByName')->andReturn([$organisation]);

        $prefixes = Mockery::mock(DocumentPrefixRepository::class);
        $prefixes->expects('getAlphabeticallyFirstByOrganisation')
            ->with($organisation)
            ->andReturn($invalidPrefix);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('flush');

        $tester = new CommandTester(
            new OrganisationPrefixMigrationCommand($organisations, $prefixes, $entityManager),
        );

        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertNull($organisation->getPrefix());
        self::assertStringContainsString('Organisation A', $tester->getDisplay());
        self::assertStringContainsString('organisation.prefix_too_short', $tester->getDisplay());
        self::assertStringContainsString('Migrated: 0', $tester->getDisplay());
        self::assertStringContainsString('Skipped: 0', $tester->getDisplay());
        self::assertStringContainsString('Failed: 1', $tester->getDisplay());
    }
}
