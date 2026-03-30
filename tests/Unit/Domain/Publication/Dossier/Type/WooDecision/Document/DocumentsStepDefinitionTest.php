<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepException;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentsStepDefinition;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DocumentsStepDefinitionTest extends UnitTestCase
{
    private AbstractDossier&MockInterface $dossier;
    private ValidatorInterface&MockInterface $validator;
    private DocumentsStepDefinition $step;

    protected function setUp(): void
    {
        $this->dossier = Mockery::mock(WooDecision::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        $this->step = new DocumentsStepDefinition(
            StepName::DOCUMENTS,
            DossierType::WOO_DECISION,
        );

        parent::setUp();
    }

    private function isStepCompleted(?AbstractDossier $dossier = null): bool
    {
        return $this->step->isCompleted($dossier ?? $this->dossier, $this->validator);
    }

    public function testIsCompletedThrowsExceptionForUnsupportedDossierType(): void
    {
        $this->expectException(StepException::class);
        $this->isStepCompleted(Mockery::mock(Covenant::class));
    }

    public function testIsCompletedReturnsTrueWhenNoInventoryAndDocumentsCanBeProvided(): void
    {
        $this->dossier->expects('canProvideInventory')->andReturnFalse();

        self::assertTrue($this->isStepCompleted());
    }

    public function testIsCompletedReturnsTrueWhenUploadsAreComplete(): void
    {
        $this->dossier->expects('canProvideInventory')->andReturnTrue();
        $this->dossier->expects('hasAllExpectedUploads')->andReturnTrue();

        self::assertTrue($this->isStepCompleted());
    }

    public function testIsCompletedReturnsFalseWhenUploadsAreIncomplete(): void
    {
        $this->dossier->expects('canProvideInventory')->andReturnTrue();
        $this->dossier->expects('hasAllExpectedUploads')->andReturnFalse();
        $this->dossier->expects('hasProductionReport')->andReturnTrue();

        self::assertFalse($this->isStepCompleted());
    }

    #[DataProvider('dossierStatusData')]
    public function testIsCompletedForOptionalInventoryAndDossierStatus(DossierStatus $status, bool $expectedIsCompleted): void
    {
        $this->dossier->expects('canProvideInventory')->andReturnTrue();
        $this->dossier->expects('hasAllExpectedUploads')->andReturnFalse();
        $this->dossier->expects('hasProductionReport')->andReturnFalse();
        $this->dossier->expects('isInventoryOptional')->andReturnTrue();
        $this->dossier->expects('getStatus')->andReturn($status);

        self::assertEquals($expectedIsCompleted, $this->isStepCompleted());
    }

    /**
     * @return array<array{DossierStatus,bool}>
     */
    public static function dossierStatusData(): array
    {
        return [
            [DossierStatus::PUBLISHED, true],
            [DossierStatus::PREVIEW, true],
            [DossierStatus::SCHEDULED, true],

            [DossierStatus::CONCEPT, false],
            [DossierStatus::NEW, false],
        ];
    }
}
