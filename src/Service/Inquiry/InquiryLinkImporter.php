<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Exception;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Exception\Import\ImportException;
use Shared\Exception\Import\InquiryDocumentNotFoundException;
use Shared\Exception\Import\InquiryInvalidInquiryNumbersException;
use Shared\Exception\Import\SpreadsheetCannotBeOpenedException;
use Shared\Service\Import\ImportError;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\InvalidArgumentException;

readonly class InquiryLinkImporter
{
    public function __construct(
        private InquiryService $inquiryService,
        private DocumentRepository $documentRepository,
        private InquiryLinkImportParser $parser,
    ) {
    }

    public function import(
        Organisation $activeOrganisation,
        UploadedFile $uploadedFile,
    ): InquiryLinkImportResult {
        $inquiryChangeset = new InquiryChangeset($activeOrganisation);

        $result = new InquiryLinkImportResult($inquiryChangeset);
        $result->setHasMatter($this->parser->hasMatterColumn($uploadedFile));

        try {
            $this->processUploadedFile(
                $uploadedFile,
                $activeOrganisation->getPrefix()->toString(),
                $inquiryChangeset,
                $result,
            );

            $this->inquiryService->applyChangesetAsync($inquiryChangeset);
        } catch (Exception $exception) {
            if (! $exception instanceof ImportException) {
                $exception = new SpreadsheetCannotBeOpenedException($exception);
            }

            $result->addGenericError(ImportError::fromException($exception));
        }

        return $result;
    }

    private function processUploadedFile(
        UploadedFile $uploadedFile,
        string $prefix,
        InquiryChangeset $inquiryChangeset,
        InquiryLinkImportResult $result,
    ): void {
        $rowNr = 0;
        foreach ($this->parser->parse($uploadedFile, $prefix) as $documentNumberValue => $inquiryNumberValues) {
            $rowNr++;
            try {
                $documentNumber = DocumentNumber::fromUnnormalizedString($documentNumberValue);
                $documentInquiryNumbers = $this->documentRepository->getDocumentInquiryNumbers($documentNumber);
                if ($documentInquiryNumbers->isDocumentNotFound()) {
                    throw new InquiryDocumentNotFoundException($documentNumber->toString());
                }

                try {
                    $inquiryNumbers = new InquiryNumbers($inquiryNumberValues);
                } catch (InvalidArgumentException) {
                    throw new InquiryInvalidInquiryNumbersException($rowNr, $inquiryNumberValues);
                }

                $inquiryChangeset->updateInquiryNumbersForDocument($documentInquiryNumbers, $inquiryNumbers);
            } catch (ImportException $importException) {
                $result->addRowError($rowNr, ImportError::fromException($importException));
            }
        }
    }
}
