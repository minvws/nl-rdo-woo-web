<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\Attachment;

use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticHighlights;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class AttachmentSearchResultMapper implements SearchResultMapperInterface
{
    use HighlightMapperTrait;

    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private AttachmentViewFactory $viewFactory,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::ATTACHMENT;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        $id = $hit->getStringOrNull('[_id]');
        if (is_null($id)) {
            return null;
        }

        $attachment = $this->attachmentRepository->find($id);
        if (! $attachment) {
            return null;
        }

        $dossier = $attachment->getDossier();

        $highlightData = $this->getHighlightData($hit, ElasticHighlights::getPaths());

        return new SubTypeSearchResultEntry(
            $this->viewFactory->make($dossier, $attachment),
            [DossierReference::fromEntity($dossier)],
            $highlightData,
            ElasticDocumentType::ATTACHMENT,
        );
    }
}
