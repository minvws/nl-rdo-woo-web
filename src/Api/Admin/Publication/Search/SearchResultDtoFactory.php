<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use App\Domain\Publication\Attachment\ViewModel\Attachment;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultEntry;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use App\Service\DossierWizard\WizardStatusFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SearchResultDtoFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private WizardStatusFactory $wizardStatusFactory,
        private DossierRepository $dossierRepository,
    ) {
    }

    public function make(object $entry): SearchResultDto
    {
        return match (true) {
            $entry instanceof DossierSearchResultEntry => $this->fromDossierSearchResult($entry),
            $entry instanceof SubTypeSearchResultEntry => $this->fromSubTypeSearchResult($entry),
            default => throw new \InvalidArgumentException(sprintf('Unsupported search result entry given: "%s"', $entry::class)),
        };
    }

    private function fromSubTypeSearchResult(SubTypeSearchResultEntry $entry): SearchResultDto
    {
        if (in_array($entry->getType(), ElasticDocumentType::getMainDocumentTypes(), true)) {
            return $this->fromMainDocumentEntry($entry);
        }

        return match ($entry->getType()) {
            ElasticDocumentType::WOO_DECISION_DOCUMENT => $this->fromDocumentEntry($entry),
            ElasticDocumentType::ATTACHMENT => $this->fromAttachmentEntry($entry),
            default => throw new \InvalidArgumentException(sprintf('Unsupported subtype search result given: "%s"', $entry::class)),
        };
    }

    /**
     * @param array<array-key,object> $entities
     *
     * @return list<SearchResultDto>
     */
    public function makeCollection(array $entities): array
    {
        return array_values(array_map(
            fn (object $entity): SearchResultDto => $this->make($entity),
            $entities,
        ));
    }

    private function fromDossierSearchResult(DossierSearchResultEntry $entry): SearchResultDto
    {
        $dossier = $entry->getDossier();

        return new SearchResultDto(
            id: $dossier->id->toRfc4122(),
            type: SearchResultType::DOSSIER,
            title: $dossier->title ?? $dossier->dossierNr,
            link: $this->urlGenerator->generate(
                'app_admin_dossier',
                ['prefix' => $dossier->documentPrefix, 'dossierId' => $dossier->dossierNr],
            ),
            number: $dossier->dossierNr,
        );
    }

    private function fromDocumentEntry(SubTypeSearchResultEntry $entry): SearchResultDto
    {
        $dossier = $entry->getDossiers()[0];
        /** @var DocumentViewModel $document */
        $document = $entry->getViewModel();

        return new SearchResultDto(
            id: $document->documentNr,
            type: SearchResultType::DOCUMENT,
            title: $document->fileInfo->getName() ?? '',
            link: $this->urlGenerator->generate(
                'app_admin_dossier_woodecision_document',
                [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'documentId' => $document->documentNr,
                ],
            ),
            number: $document->documentNr,
        );
    }

    private function fromAttachmentEntry(SubTypeSearchResultEntry $entity): SearchResultDto
    {
        $dossier = $entity->getDossiers()[0];
        /** @var Attachment $attachment */
        $attachment = $entity->getViewModel();

        return new SearchResultDto(
            id: $attachment->id,
            type: SearchResultType::ATTACHMENT,
            title: $attachment->name ?? '',
            link: $this->getMainDocumentAndAttachmentUrl($dossier),
        );
    }

    private function fromMainDocumentEntry(SubTypeSearchResultEntry $entity): SearchResultDto
    {
        $dossier = $entity->getDossiers()[0];
        /** @var MainDocument $mainDocument */
        $mainDocument = $entity->getViewModel();

        return new SearchResultDto(
            id: $mainDocument->id,
            type: SearchResultType::MAIN_DOCUMENT,
            title: $mainDocument->name ?? '',
            link: $this->getMainDocumentAndAttachmentUrl($dossier),
        );
    }

    private function getMainDocumentAndAttachmentUrl(DossierReference $dossierReference): string
    {
        $dossier = $this->dossierRepository->findOneByPrefixAndDossierNr(
            $dossierReference->getDocumentPrefix(),
            $dossierReference->getDossierNr(),
        );

        return $this->urlGenerator->generate(
            $this->wizardStatusFactory->getWizardStatus($dossier)->getAttachmentStep()->getRouteName(),
            ['prefix' => $dossierReference->getDocumentPrefix(), 'dossierId' => $dossierReference->getDossierNr()],
        );
    }
}
