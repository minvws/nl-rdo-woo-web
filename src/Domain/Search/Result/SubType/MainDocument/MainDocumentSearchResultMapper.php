<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\SubType\MainDocument;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticHighlights;
use Shared\Domain\Search\Result\HighlightMapperTrait;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

use function in_array;
use function is_null;

readonly class MainDocumentSearchResultMapper implements SearchResultMapperInterface
{
    use HighlightMapperTrait;

    public function __construct(
        private MainDocumentRepository $mainDocumentRepository,
        private MainDocumentViewFactory $viewFactory,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return in_array($type, ElasticDocumentType::getMainDocumentTypes());
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        $id = $hit->getStringOrNull('[_id]');
        if (is_null($id)) {
            return null;
        }

        /** @var AbstractMainDocument $mainDocument */
        $mainDocument = $this->mainDocumentRepository->find($id);
        if (! $mainDocument) {
            return null;
        }

        $dossier = $mainDocument->getDossier();

        $highlightData = $this->getHighlightData($hit, ElasticHighlights::getPaths());

        return new SubTypeSearchResultEntry(
            $this->viewFactory->make($dossier, $mainDocument),
            [DossierReference::fromEntity($dossier)],
            $highlightData,
            ElasticDocumentType::from($hit->getString('[fields][type][0]')),
        );
    }
}
