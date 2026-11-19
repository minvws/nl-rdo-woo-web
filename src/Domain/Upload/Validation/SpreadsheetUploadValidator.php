<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Validation;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\EntityWithSpreadsheetTable;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableFactory;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\FileType\FileExtension;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

use function is_a;
use function pathinfo;
use function sprintf;
use function strtolower;

use const PATHINFO_EXTENSION;

readonly class SpreadsheetUploadValidator
{
    private const string CONTEXT_DOSSIER_ID = 'dossierId';

    public function __construct(
        private DossierRepository $dossierRepository,
        private ClamAvFileScanner $clamAvFileScanner,
        private SpreadsheetTableFactory $spreadsheetTableFactory,
        #[Autowire(param: 'working_copy_path')]
        private string $workingCopyPath,
    ) {
    }

    /**
     * @throws UploadValidationException
     */
    public function validate(UploadEntity $uploadEntity): void
    {
        $size = $uploadEntity->getSize();
        Assert::notNull($size);

        $fileSizeLimit = $this->clamAvFileScanner->getFileSizeLimit();
        if ($size > $fileSizeLimit) {
            throw UploadValidationException::forSpreadsheetTooLarge();
        }

        $filename = $uploadEntity->getFilename();
        Assert::string($filename);

        $fileExtension = FileExtension::tryFrom(strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
        Assert::notNull($fileExtension);

        try {
            $this->spreadsheetTableFactory->create(
                sprintf('%s/%s', $this->workingCopyPath, $uploadEntity->getUploadId()),
                $fileExtension,
            );
        } catch (SpreadsheetTableException $spreadsheetTableException) {
            throw UploadValidationException::forInvalidSpreadsheet($spreadsheetTableException);
        }
    }

    public function supports(UploadEntity $uploadEntity): bool
    {
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::MAIN_DOCUMENTS) {
            return false;
        }

        $filename = $uploadEntity->getFilename();
        Assert::string($filename);

        $fileExtension = FileExtension::tryFrom(strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
        if ($fileExtension === null || ! $this->spreadsheetTableFactory->supports($fileExtension)) {
            return false;
        }

        $context = $uploadEntity->getContext();
        if (! $context->has(self::CONTEXT_DOSSIER_ID)) {
            return false;
        }

        $dossier = $this->dossierRepository->find($context->getString(self::CONTEXT_DOSSIER_ID));

        if (! $dossier instanceof EntityWithMainDocument) {
            return false;
        }

        return is_a($dossier->getMainDocumentEntityClass(), EntityWithSpreadsheetTable::class, true);
    }
}
