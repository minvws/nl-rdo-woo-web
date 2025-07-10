<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\MainDocument;

use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticHighlights;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use App\Service\Security\ApplicationMode\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

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
