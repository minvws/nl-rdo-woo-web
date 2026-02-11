<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\SubType\Attachment;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticHighlights;
use Shared\Domain\Search\Result\HighlightMapperTrait;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

use function is_null;

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
