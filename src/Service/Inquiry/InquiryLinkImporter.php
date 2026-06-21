<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Exception;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Exception\InquiryLinkImportException;
use Shared\Exception\InventoryReaderException;
use Shared\Exception\TranslatableException;
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
        DocumentPrefix $prefix,
    ): InquiryLinkImportResult {
        $inquiryChangeset = new InquiryChangeset($activeOrganisation);
        $result = new InquiryLinkImportResult($inquiryChangeset);

        try {
            if ($activeOrganisation !== $prefix->getOrganisation()) {
                throw InquiryLinkImportException::forOrganisationMismatch();
            }

            $this->processUploadedFile($uploadedFile, $prefix, $inquiryChangeset, $result);

            $this->inquiryService->applyChangesetAsync($inquiryChangeset);
        } catch (Exception $exception) {
            if (! $exception instanceof TranslatableException) {
                $exception = InventoryReaderException::forOpenSpreadsheetException($exception);
            }

            $result->addGenericException($exception);
        }

        return $result;
    }

    private function processUploadedFile(
        UploadedFile $uploadedFile,
        DocumentPrefix $prefix,
        InquiryChangeset $inquiryChangeset,
        InquiryLinkImportResult $result,
    ): void {
        $rowNr = 0;
        foreach ($this->parser->parse($uploadedFile, $prefix) as $documentNr => $inquiryNumberValues) {
            $rowNr++;
            try {
                $documentInquiryNumbers = $this->documentRepository->getDocumentInquiryNumbers($documentNr);
                if ($documentInquiryNumbers->isDocumentNotFound()) {
                    throw InquiryLinkImportException::forMissingDocument($documentNr);
                }

                try {
                    $inquiryNumbers = new InquiryNumbers($inquiryNumberValues);
                } catch (InvalidArgumentException) {
                    throw InquiryLinkImportException::forInvalidInquiryNumber($rowNr, $inquiryNumberValues);
                }

                $inquiryChangeset->updateInquiryNumbersForDocument($documentInquiryNumbers, $inquiryNumbers);
            } catch (TranslatableException $exception) {
                $result->addRowException($rowNr, $exception);
            }
        }
    }
}
