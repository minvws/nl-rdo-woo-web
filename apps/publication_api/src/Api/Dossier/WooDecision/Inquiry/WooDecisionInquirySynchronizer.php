<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Inquiry;

use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Domain\Inquiry\InquiryService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryNumbers;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_unique;
use function array_values;

readonly class WooDecisionInquirySynchronizer
{
    public function __construct(
        private InquiryService $inquiryService,
    ) {
    }

    /**
     * @return Collection<string,DocumentInquiryNumbers>
     *
     * @throws ApiPlatformValidationException
     */
    public function snapshot(WooDecision $wooDecision): Collection
    {
        $documentInquiryNumbers = $wooDecision->getDocuments()
            ->reduce(static function (Collection $carry, Document $document): Collection {
                $externalId = $document->getExternalId()?->toString();

                if ($externalId === null) {
                    // A better solution for this issue should be implemented. See #6214:
                    $message = 'Dossier has Document(s) without external ID(s). This is likely because this Dossier was updated through the UI.';
                    throw new ApiPlatformValidationException(ConstraintViolationList::createFromMessage($message));
                }

                $carry->set($externalId, DocumentInquiryNumbers::fromDocumentEntity($document));

                return $carry;
            }, new ArrayCollection());
        Assert::allIsInstanceOf($documentInquiryNumbers, DocumentInquiryNumbers::class);

        return $documentInquiryNumbers;
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $requestDocuments
     * @param Collection<string,DocumentInquiryNumbers> $previousDocumentInquiryNumbers
     *
     * @throws ApiPlatformValidationException
     */
    public function apply(
        WooDecision $wooDecision,
        array $requestDocuments,
        Collection $previousDocumentInquiryNumbers = new ArrayCollection(),
    ): void {
        $currentDocumentInquiryNumbers = $this->snapshot($wooDecision);

        $allExternalIds = array_values(array_unique(array_merge(
            $previousDocumentInquiryNumbers->getKeys(),
            $currentDocumentInquiryNumbers->getKeys(),
        )));

        $targetDocumentInquiryNumbers = new ArrayCollection($requestDocuments)
            ->reduce(static function (ArrayCollection $carry, WooDecisionDocumentRequestDto $documentRequestDto): ArrayCollection {
                $carry->set($documentRequestDto->externalId->toString(), new InquiryNumbers($documentRequestDto->inquiryNumbers));

                return $carry;
            }, new ArrayCollection());

        $inquiryChangeset = new InquiryChangeset($wooDecision->getOrganisation());
        foreach ($allExternalIds as $externalId) {
            $inquiryChangeset->updateInquiryNumbersForDocument(
                $this->resolveDocumentInquiryNumbers($previousDocumentInquiryNumbers, $currentDocumentInquiryNumbers, $externalId),
                $this->resolveInquiryNumbers($targetDocumentInquiryNumbers, $externalId),
            );
        }

        $this->inquiryService->applyChangesetSync($inquiryChangeset);
    }

    /**
     * @param Collection<string,DocumentInquiryNumbers> $previousDocumentInquiryNumbers
     * @param Collection<string,DocumentInquiryNumbers> $currentDocumentInquiryNumbers
     */
    private function resolveDocumentInquiryNumbers(
        Collection $previousDocumentInquiryNumbers,
        Collection $currentDocumentInquiryNumbers,
        string $externalId,
    ): DocumentInquiryNumbers {
        $documentInquiryNumbers = $previousDocumentInquiryNumbers->get($externalId);
        $documentInquiryNumbers ??= $currentDocumentInquiryNumbers->get($externalId);

        Assert::isInstanceOf($documentInquiryNumbers, DocumentInquiryNumbers::class);

        return $documentInquiryNumbers;
    }

    /**
     * @param Collection<string,InquiryNumbers> $targetDocumentInquiryNumbers
     */
    private function resolveInquiryNumbers(Collection $targetDocumentInquiryNumbers, string $externalId): InquiryNumbers
    {
        $inquiryNumbers = $targetDocumentInquiryNumbers->get($externalId);
        if ($inquiryNumbers === null) {
            return InquiryNumbers::empty();
        }

        Assert::isInstanceOf($inquiryNumbers, InquiryNumbers::class);

        return $inquiryNumbers;
    }
}
