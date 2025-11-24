<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Step;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\ViewModel\DossierViewParamsBuilder;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

readonly class StepActionHelper
{
    public function __construct(
        private RouterInterface $router,
        private WizardStatusFactory $wizardStatusFactory,
        private PaginatorInterface $paginator,
        private DossierViewParamsBuilder $paramsBuilder,
    ) {
    }

    public function redirectToNextStep(DossierWizardStatus $wizardStatus): RedirectResponse
    {
        return $this->redirectToRouteWithDossierParams(
            $wizardStatus->getNextStep()->getRouteName(),
            $wizardStatus->getDossier(),
        );
    }

    public function redirectToCurrentStep(DossierWizardStatus $wizardStatus): RedirectResponse
    {
        return $this->redirectToRouteWithDossierParams(
            $wizardStatus->getCurrentStep()->getRouteName(),
            $wizardStatus->getDossier(),
        );
    }

    public function redirectToFirstOpenStep(DossierWizardStatus $wizardStatus): RedirectResponse
    {
        $firstOpenStep = $wizardStatus->getFirstOpenStep();
        if ($firstOpenStep === null) {
            return $this->redirectToDossier($wizardStatus->getDossier());
        }

        return $this->redirectToRouteWithDossierParams(
            $firstOpenStep->getRouteName(),
            $wizardStatus->getDossier(),
        );
    }

    public function redirectToDossier(AbstractDossier $dossier): RedirectResponse
    {
        return $this->redirectToRouteWithDossierParams('app_admin_dossier', $dossier);
    }

    public function redirectToPublicationConfirmation(AbstractDossier $dossier): RedirectResponse
    {
        return $this->redirectToRouteWithDossierParams('app_admin_dossier_publication_confirmation', $dossier);
    }

    public function redirectAfterFormSubmit(
        DossierWizardStatus $wizardStatus,
        FormInterface $form,
    ): RedirectResponse {
        $wizardStatus = $this->refreshWizardStatus($wizardStatus);
        if ($wizardStatus->getDossier()->getStatus()->isNewOrConcept()) {
            if ($this->isButtonClicked($form, 'next')) {
                return $this->redirectToFirstOpenStep($wizardStatus);
            }

            return $this->redirectToCurrentStep($wizardStatus);
        }

        return $this->redirectToDossier($wizardStatus->getDossier());
    }

    public function isFormCancelled(FormInterface $form): bool
    {
        return $form->isSubmitted() && $this->isButtonClicked($form, 'cancel');
    }

    public function getWizardStatus(AbstractDossier $dossier, StepName $stepName): DossierWizardStatus
    {
        return $this->wizardStatusFactory->getWizardStatus($dossier, $stepName);
    }

    private function redirectToRouteWithDossierParams(string $name, AbstractDossier $dossier): RedirectResponse
    {
        $url = $this->router->generate(
            $name,
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
        );

        return new RedirectResponse($url);
    }

    private function isButtonClicked(FormInterface $form, string $name): bool
    {
        if (! $form->has($name)) {
            return false;
        }

        /** @var SubmitButton $button */
        $button = $form->get($name);

        return $button->isClicked();
    }

    public function addDossierToBreadcrumbs(Breadcrumbs $breadcrumbs, AbstractDossier $dossier, ?string $item = null): void
    {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );

        if ($item !== null) {
            $breadcrumbs->addItem($item);
        }
    }

    /**
     * @return PaginationInterface<int, mixed>
     */
    public function getPaginator(mixed $target, int $page = 1, int $limit = 20): PaginationInterface
    {
        return $this->paginator->paginate(
            $target,
            $page,
            $limit,
        );
    }

    public function refreshWizardStatus(DossierWizardStatus $wizardStatus): DossierWizardStatus
    {
        return $this->getWizardStatus(
            $wizardStatus->getDossier(),
            $wizardStatus->getCurrentStep()->getStepName(),
        );
    }

    public function getParamsBuilder(AbstractDossier $dossier): DossierViewParamsBuilder
    {
        return $this->paramsBuilder->forDossier($dossier);
    }
}
