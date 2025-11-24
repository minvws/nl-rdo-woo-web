<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use Shared\Service\Search\Model\FacetKey;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final readonly class WooDecisionViewFactory
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DepartmentViewFactory $departmentViewFactory,
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
        private MainDocumentViewFactory $mainDocumentViewFactory,
        private RouterInterface $router,
    ) {
    }

    public function make(WooDecisionEntity $dossier): WooDecision
    {
        $decisionDate = $dossier->getDecisionDate();
        Assert::notNull($decisionDate);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $decision = $dossier->getDecision();
        Assert::notNull($decision);

        $publicationReason = $dossier->getPublicationReason();
        Assert::notNull($publicationReason);

        $departments = $this->departmentViewFactory->makeCollection($dossier->getDepartments());

        return new WooDecision(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            counts: $this->wooDecisionRepository->getDossierCounts($dossier),
            departments: $departments,
            isInventoryRequired: $dossier->isInventoryRequired(),
            isInventoryOptional: $dossier->isInventoryOptional(),
            canProvideInventory: $dossier->canProvideInventory(),
            decision: $decision,
            decisionDate: $decisionDate,
            mainDocument: $this->mainDocumentViewFactory->make($dossier, $mainDocument),
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            publicationReason: $publicationReason,
            documentSearchUrl: $this->router->generate(
                'app_search',
                [
                    FacetKey::PREFIXED_DOSSIER_NR->getParamName() => [PrefixedDossierNr::forDossier($dossier)],
                ]
            ),
        );
    }
}
