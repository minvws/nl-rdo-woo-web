<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Exception\InquiryLinkImportException;
use App\Exception\InventoryReaderException;
use App\Exception\TranslatableException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        } catch (\Exception $exception) {
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
        foreach ($this->parser->parse($uploadedFile, $prefix) as $documentNr => $caseNrs) {
            $rowNr++;
            try {
                $documentCaseNrs = $this->documentRepository->getDocumentCaseNrs($documentNr);
                if ($documentCaseNrs->isDocumentNotFound()) {
                    throw InquiryLinkImportException::forMissingDocument($documentNr);
                }

                $inquiryChangeset->updateCaseNrsForDocument($documentCaseNrs, $caseNrs);
            } catch (TranslatableException $exception) {
                $result->addRowException($rowNr, $exception);
            }
        }
    }
}
