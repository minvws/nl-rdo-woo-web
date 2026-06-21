<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use Doctrine\Common\Collections\Collection;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\WooDecisionUploadDocumentResource;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;

use function array_map;
use function array_values;

readonly class WooDecisionDocumentResponseDtoFactory
{
    public function __construct(
        private DossierPathHelper $dossierPathHelper,
        private DocumentUploadStatusService $documentUploadStatusService,
        private PublicUrlGenerator $publicUrlGenerator,
        private WooDecisionRelatedDocumentResponseDtoFactory $wooDecisionRelatedDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @return list<WooDecisionDocumentResponseDto>
     */
    public function fromWooDecision(WooDecision $wooDecision): array
    {
        return array_values(array_map(
            function (Document $document) use ($wooDecision): WooDecisionDocumentResponseDto {
                return $this->fromEntity($document, $wooDecision);
            },
            $wooDecision->getDocuments()->toArray(),
        ));
    }

    private function fromEntity(Document $document, WooDecision $wooDecision): WooDecisionDocumentResponseDto
    {
        return new WooDecisionDocumentResponseDto(
            $this->getInquiryNumbers($document->getInquiries()),
            $document->getDocumentDate(),
            $document->getDocumentId(),
            $document->getDocumentNr(),
            $document->getExternalId(),
            $document->getFamilyId(),
            $document->getFileInfo()->getName(),
            $document->getGrounds(),
            $document->isSuspended(),
            $document->isUploaded(),
            $document->isWithdrawn(),
            $document->getJudgement(),
            $document->getLinks(),
            $this->wooDecisionRelatedDocumentResponseDtoFactory->fromEntities($document->getRefersTo()->toArray()),
            $document->getRemark(),
            $document->getThreadId(),
            $this->documentUploadStatusService->getUploadStatus($document),
            $this->getHalLinks($document, $wooDecision),
        );
    }

    /**
     * @param Collection<array-key,Inquiry> $inquiries
     *
     * @return list<string>
     */
    private function getInquiryNumbers(Collection $inquiries): array
    {
        return array_values(array_map(
            static function (Inquiry $inquiry): string {
                return $inquiry->getInquiryNumber();
            },
            $inquiries->toArray(),
        ));
    }

    private function getHalLinks(Document $document, WooDecision $wooDecision): LinkCollection
    {
        $linkCollection = new LinkCollection();

        if ($document->shouldBeUploaded()) {
            $linkCollection->set(
                LinkCollection::UPLOAD,
                new Link($this->publicUrlGenerator->buildUrlFromRoute(WooDecisionUploadDocumentResource::ROUTE_NAME_UPLOAD, [
                    'organisationId' => $wooDecision->getOrganisation()->getId(),
                    'dossierExternalId' => $wooDecision->getExternalId(),
                    'documentExternalId' => $document->getExternalId(),
                ])),
            );
        }

        if ($wooDecision->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($wooDecision))));
            $linkCollection->set(
                LinkCollection::FILE,
                new Link($this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
                    'prefix' => $wooDecision->getDocumentPrefix(),
                    'dossierId' => $wooDecision->getDossierNr(),
                    'type' => DossierFileType::DOCUMENT->value,
                    'id' => $document->getId(),
                ])),
            );
        }

        return $linkCollection;
    }
}
