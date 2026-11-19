<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Exception\Import\ImportException;
use Shared\Exception\Import\InquiryDocumentNotFoundException;
use Shared\Exception\Import\InquiryInvalidInquiryNumbersException;
use Shared\Exception\Import\InquiryUnexpectedProcessingException;
use Shared\Service\Import\ImportError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\InvalidArgumentException;

readonly class InquiryLinkImporter
{
    public function __construct(
        private InquiryService $inquiryService,
        private DocumentRepository $documentRepository,
        private InquiryLinkImportParser $parser,
        private LoggerInterface $logger,
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
                $exception = $this->toUnexpectedProcessingException($exception);
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
        foreach ($this->parser->parse($uploadedFile, $prefix) as $rowNr => $row) {
            try {
                $documentInquiryNumbers = $this->documentRepository->getDocumentInquiryNumbers($row->documentNumber);
                if ($documentInquiryNumbers->isDocumentNotFound()) {
                    throw new InquiryDocumentNotFoundException($row->documentNumber->toString());
                }

                try {
                    $inquiryNumbers = new InquiryNumbers($row->inquiryNumbers);
                } catch (InvalidArgumentException) {
                    throw new InquiryInvalidInquiryNumbersException($rowNr, $row->inquiryNumbers);
                }

                $inquiryChangeset->updateInquiryNumbersForDocument($documentInquiryNumbers, $inquiryNumbers);
            } catch (ImportException $importException) {
                $result->addRowError($rowNr, ImportError::fromException($importException));
            } catch (Exception $exception) {
                $result->addRowError($rowNr, ImportError::fromException($this->toUnexpectedProcessingException($exception, $rowNr)));
            }
        }
    }

    private function toUnexpectedProcessingException(Exception $exception, ?int $rowNr = null): InquiryUnexpectedProcessingException
    {
        $unexpectedException = new InquiryUnexpectedProcessingException($exception);

        $this->logger->error('Unexpected error during inquiry link import', [
            'rowNumber' => $rowNr,
            'exception' => $unexpectedException,
        ]);

        return $unexpectedException;
    }
}
