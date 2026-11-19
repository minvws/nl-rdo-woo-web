<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Validation;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableFactory;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableResult;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\Validation\SpreadsheetUploadValidator;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;

use function sprintf;

final class SpreadsheetUploadValidatorTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $uploadId = $this->getFaker()->uuid();
        $fileName = sprintf('%s.xlsx', $this->getFaker()->word());
        $workingCopyPath = $this->getFaker()->word();
        $fileSizeLimit = $this->getFaker()->numberBetween(1000, 2000);
        $size = $this->getFaker()->numberBetween(1, 999);

        $uploadEntity = new UploadEntity($uploadId, UploadGroupId::MAIN_DOCUMENTS, null, new InputBag());
        $uploadEntity->finishUploading($fileName, $size);

        $clamAvFileScanner = Mockery::mock(ClamAvFileScanner::class);
        $clamAvFileScanner->expects('getFileSizeLimit')->andReturn($fileSizeLimit);

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('create')
            ->with(sprintf('%s/%s', $workingCopyPath, $uploadId), FileExtension::XLSX)
            ->andReturn(new SpreadsheetTableResult(new SpreadsheetTable([], []), false));

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            Mockery::mock(DossierRepository::class),
            $clamAvFileScanner,
            $spreadsheetTableFactory,
            $workingCopyPath,
        );
        $spreadsheetUploadValidator->validate($uploadEntity);
    }

    public function testValidateFailsForInvalidSpreadsheet(): void
    {
        $uploadId = $this->getFaker()->uuid();
        $fileName = sprintf('%s.xls', $this->getFaker()->word());
        $workingCopyPath = $this->getFaker()->word();
        $fileSizeLimit = $this->getFaker()->numberBetween(1000, 2000);
        $size = $this->getFaker()->numberBetween(1, 999);

        $uploadEntity = new UploadEntity($uploadId, UploadGroupId::MAIN_DOCUMENTS, null, new InputBag());
        $uploadEntity->finishUploading($fileName, $size);

        $clamAvFileScanner = Mockery::mock(ClamAvFileScanner::class);
        $clamAvFileScanner->expects('getFileSizeLimit')->andReturn($fileSizeLimit);

        $spreadsheetTableException = SpreadsheetTableException::forMissingHeaderRow();

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory
            ->expects('create')
            ->with(sprintf('%s/%s', $workingCopyPath, $uploadId), FileExtension::XLS)
            ->andThrow($spreadsheetTableException);

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            Mockery::mock(DossierRepository::class),
            $clamAvFileScanner,
            $spreadsheetTableFactory,
            $workingCopyPath,
        );

        $this->expectExceptionObject(UploadValidationException::forInvalidSpreadsheet($spreadsheetTableException));

        $spreadsheetUploadValidator->validate($uploadEntity);
    }

    public function testValidateFialsWhenTooLarge(): void
    {
        $filename = sprintf('%s.xlsx', $this->getFaker()->word());
        $size = $this->getFaker()->numberBetween(1000, 2000);

        $uploadEntity = new UploadEntity($this->getFaker()->uuid(), UploadGroupId::MAIN_DOCUMENTS, null, new InputBag());
        $uploadEntity->finishUploading($filename, $size);

        $clamAvFileScanner = Mockery::mock(ClamAvFileScanner::class);
        $clamAvFileScanner->expects('getFileSizeLimit')->andReturn($this->getFaker()->numberBetween(1, 999));

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            Mockery::mock(DossierRepository::class),
            $clamAvFileScanner,
            Mockery::mock(SpreadsheetTableFactory::class),
            $this->getFaker()->word(),
        );

        $this->expectExceptionObject(UploadValidationException::forSpreadsheetTooLarge());

        $spreadsheetUploadValidator->validate($uploadEntity);
    }

    public function testSupports(): void
    {
        $dossierId = $this->getFaker()->uuid();

        $uploadEntity = new UploadEntity(
            $this->getFaker()->uuid(),
            UploadGroupId::MAIN_DOCUMENTS,
            null,
            new InputBag(['dossierId' => $dossierId]),
        );
        $uploadEntity->finishUploading(sprintf('%s.xlsx', $this->getFaker()->word()), $this->getFaker()->randomNumber());

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getMainDocumentEntityClass')->andReturn(DispositionMainDocument::class);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')
            ->with($dossierId)
            ->andReturn($dossier);

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::XLSX)
            ->andReturnTrue();

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            $dossierRepository,
            Mockery::mock(ClamAvFileScanner::class),
            $spreadsheetTableFactory,
            $this->getFaker()->word(),
        );

        self::assertTrue($spreadsheetUploadValidator->supports($uploadEntity));
    }

    public function testSupportsForNonSpreadsheetDossier(): void
    {
        $dossierId = $this->getFaker()->uuid();
        $fileName = sprintf('%s.xlsx', $this->getFaker()->word());

        $uploadEntity = new UploadEntity(
            $this->getFaker()->uuid(),
            UploadGroupId::MAIN_DOCUMENTS,
            null,
            new InputBag(['dossierId' => $dossierId]),
        );
        $uploadEntity->finishUploading($fileName, $this->getFaker()->randomNumber());

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getMainDocumentEntityClass')->andReturn(CovenantMainDocument::class);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')
            ->with($dossierId)
            ->andReturn($dossier);

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::XLSX)
            ->andReturnTrue();

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            $dossierRepository,
            Mockery::mock(ClamAvFileScanner::class),
            $spreadsheetTableFactory,
            $this->getFaker()->word(),
        );

        self::assertFalse($spreadsheetUploadValidator->supports($uploadEntity));
    }

    public function testSupportsForUploadWithoutDossierId(): void
    {
        $fileName = sprintf('%s.xlsx', $this->getFaker()->word());

        $uploadEntity = new UploadEntity($this->getFaker()->uuid(), UploadGroupId::MAIN_DOCUMENTS, null, new InputBag());
        $uploadEntity->finishUploading($fileName, $this->getFaker()->randomNumber());

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::XLSX)
            ->andReturnTrue();

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            Mockery::mock(DossierRepository::class),
            Mockery::mock(ClamAvFileScanner::class),
            $spreadsheetTableFactory,
            $this->getFaker()->word(),
        );

        self::assertFalse($spreadsheetUploadValidator->supports($uploadEntity));
    }

    public function testSupportsWithNonSpreadsheetFile(): void
    {
        $fileName = sprintf('%s.pdf', $this->getFaker()->word());

        $uploadEntity = new UploadEntity(
            $this->getFaker()->uuid(),
            UploadGroupId::MAIN_DOCUMENTS,
            null,
            new InputBag(['dossierId' => $this->getFaker()->uuid()]),
        );
        $uploadEntity->finishUploading($fileName, $this->getFaker()->randomNumber());

        $spreadsheetTableFactory = Mockery::mock(SpreadsheetTableFactory::class);
        $spreadsheetTableFactory->expects('supports')
            ->with(FileExtension::PDF)
            ->andReturnFalse();

        $spreadsheetUploadValidator = new SpreadsheetUploadValidator(
            Mockery::mock(DossierRepository::class),
            Mockery::mock(ClamAvFileScanner::class),
            $spreadsheetTableFactory,
            $this->getFaker()->word(),
        );

        self::assertFalse($spreadsheetUploadValidator->supports($uploadEntity));
    }
}
