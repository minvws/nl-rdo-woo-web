<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Step;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\ViewModel\DossierViewParamsBuilder;
use Shared\Service\DossierWizard\DossierWizardStatus;
use Shared\Service\DossierWizard\StepStatus;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;

class StepActionHelperTest extends UnitTestCase
{
    private RouterInterface&MockInterface $router;
    private StepActionHelper $helper;
    private WizardStatusFactory&MockInterface $wizardStatusFactory;
    private AbstractDossier&MockInterface $dossier;
    private DossierWizardStatus&MockInterface $wizardStatus;
    private DossierViewParamsBuilder&MockInterface $paramsBuilder;
    private PaginatorInterface&MockInterface $paginator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wizardStatusFactory = Mockery::mock(WizardStatusFactory::class);

        $this->dossier = Mockery::mock(AbstractDossier::class);
        $this->wizardStatus = Mockery::mock(DossierWizardStatus::class);
        $this->paramsBuilder = Mockery::mock(DossierViewParamsBuilder::class);
        $this->paginator = Mockery::mock(PaginatorInterface::class);
        $this->router = Mockery::mock(RouterInterface::class);
        $this->helper = new StepActionHelper(
            $this->router,
            $this->wizardStatusFactory,
            $this->paginator,
            $this->paramsBuilder,
        );
    }

    public function testRedirectToNextStep(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->andReturn($this->dossier);

        $nextStep = Mockery::mock(StepStatus::class);
        $nextStep->expects('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->expects('getNextStep')->andReturn($nextStep);

        $this->router->expects('generate')->with(
            'dummy_route',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectToNextStep($this->wizardStatus);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectToCurrentStep(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->andReturn($this->dossier);

        $currentStep = Mockery::mock(StepStatus::class);
        $currentStep->expects('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->expects('getCurrentStep')->andReturn($currentStep);

        $this->router->expects('generate')->with(
            'dummy_route',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectToCurrentStep($this->wizardStatus);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectToFirstOpenStep(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->andReturn($this->dossier);

        $openStep = Mockery::mock(StepStatus::class);
        $openStep->expects('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->expects('getFirstOpenStep')->andReturn($openStep);

        $this->router->expects('generate')->with(
            'dummy_route',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectToFirstOpenStep($this->wizardStatus);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectToFirstOpenStepRedirectsToDossierIfItIsAlreadyPublished(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');

        $wizardStatus = Mockery::mock(DossierWizardStatus::class);
        $wizardStatus->expects('getFirstOpenStep')->andReturnNull();
        $wizardStatus->expects('getDossier')->andReturn($this->dossier);

        $this->router->expects('generate')->with(
            'app_admin_dossier',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectToFirstOpenStep($wizardStatus);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectAfterFormSubmitUsesNextStepIfNextIsClickedForAConceptDossier(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->times(3)->andReturn($this->dossier);

        $currentStep = Mockery::mock(StepStatus::class);
        $currentStep->expects('getStepName')->andReturn(StepName::DETAILS);

        $nextStep = Mockery::mock(StepStatus::class);
        $nextStep->expects('getRouteName')->andReturn('dummy_route');

        $form = Mockery::mock(FormInterface::class);
        $form->expects('has')->with('next')->andReturnTrue();
        $form->expects('get->isClicked')->andReturnTrue();

        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->wizardStatus->expects('getFirstOpenStep')->andReturn($nextStep);
        $this->wizardStatus->expects('getCurrentStep')->andReturn($currentStep);

        $this->wizardStatusFactory->expects('getWizardStatus')->andReturn($this->wizardStatus);

        $this->router->expects('generate')->with(
            'dummy_route',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectAfterFormSubmit($this->wizardStatus, $form);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectAfterFormSubmitUsesCurrentStepIfSubmitIsClickedForAConceptDossier(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->times(3)->andReturn($this->dossier);

        $currentStep = Mockery::mock(StepStatus::class);
        $currentStep->expects('getRouteName')->andReturn('dummy_route');
        $currentStep->expects('getStepName')->andReturn(StepName::DETAILS);

        $form = Mockery::mock(FormInterface::class);
        $form->expects('has')->with('next')->andReturnTrue();
        $form->expects('get->isClicked')->andReturnFalse();

        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->wizardStatus->expects('getCurrentStep')->times(2)->andReturn($currentStep);

        $this->wizardStatusFactory->expects('getWizardStatus')->andReturn($this->wizardStatus);

        $this->router->expects('generate')->with(
            'dummy_route',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectAfterFormSubmit($this->wizardStatus, $form);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testRedirectAfterFormSubmitRedirectsToDossierForAPublishedDossier(): void
    {
        $this->dossier->expects('getDocumentPrefix')->andReturn('foo');
        $this->dossier->expects('getDossierNr')->andReturn('bar');
        $this->wizardStatus->expects('getDossier')->times(3)->andReturn($this->dossier);

        $form = Mockery::mock(FormInterface::class);

        $this->dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $currentStep = Mockery::mock(StepStatus::class);
        $currentStep->expects('getStepName')->andReturn(StepName::DETAILS);

        $this->wizardStatus->expects('getCurrentStep')->andReturn($currentStep);

        $this->wizardStatusFactory->expects('getWizardStatus')->andReturn($this->wizardStatus);

        $this->router->expects('generate')->with(
            'app_admin_dossier',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectAfterFormSubmit($this->wizardStatus, $form);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testAddDossierToBreadCrumbs(): void
    {
        $this->dossier->expects('getDocumentPrefix')->times(2)->andReturn('foo');
        $this->dossier->expects('getDossierNr')->times(2)->andReturn('bar');

        $breadCrumbs = Mockery::mock(Breadcrumbs::class);
        $item = 'foo bar';
        $dossierTitle = DossierTitle::create('llama');

        $this->dossier->expects('getTitle')->andReturn($dossierTitle);

        $breadCrumbs->expects('addRouteItem')->with(
            (string) $dossierTitle,
            'app_admin_dossier',
            [
                'prefix' => $this->dossier->getDocumentPrefix(),
                'dossierId' => $this->dossier->getDossierNr(),
            ],
        );

        $breadCrumbs->expects('addItem')->with($item);

        $this->helper->addDossierToBreadcrumbs($breadCrumbs, $this->dossier, $item);
    }

    public function testRedirectToPublicationConfirmation(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getDocumentPrefix')->andReturn('foo');
        $dossier->expects('getDossierNr')->andReturn('bar');

        $this->router->expects('generate')->with(
            'app_admin_dossier_publication_confirmation',
            [
                'prefix' => 'foo',
                'dossierId' => 'bar',
            ],
        )->andReturn('dummy-url');

        $response = $this->helper->redirectToPublicationConfirmation($dossier);

        $this->assertEquals('dummy-url', $response->getTargetUrl());
    }

    public function testGetParamsBuilder(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $expectedResult = Mockery::mock(DossierViewParamsBuilder::class);

        $this->paramsBuilder->expects('forDossier')->with($dossier)->andReturn($expectedResult);

        self::assertSame(
            $expectedResult,
            $this->helper->getParamsBuilder($dossier),
        );
    }

    public function testIsFormCancelledReturnsFalseWhenFormIsNotSubmitted(): void
    {
        $form = Mockery::mock(FormInterface::class);
        $form->expects('isSubmitted')->andReturnFalse();

        self::assertFalse($this->helper->isFormCancelled($form));
    }

    public function testIsFormCancelledReturnsFalseWhenFormIsSubmittedButCancelButtonNotClicked(): void
    {
        $button = Mockery::mock(Button::class);
        $button->expects('isClicked')->andReturnFalse();

        $form = Mockery::mock(FormInterface::class);
        $form->expects('isSubmitted')->andReturnTrue();
        $form->expects('has')->with('cancel')->andReturnTrue();
        $form->expects('get')->with('cancel')->andReturn($button);

        self::assertFalse($this->helper->isFormCancelled($form));
    }

    public function testIsFormCancelledReturnsFalseWhenFormIsSubmittedButHasNoCancelButton(): void
    {
        $form = Mockery::mock(FormInterface::class);
        $form->expects('isSubmitted')->andReturnTrue();
        $form->expects('has')->with('cancel')->andReturnFalse();

        self::assertFalse($this->helper->isFormCancelled($form));
    }

    public function testIsFormCancelledReturnsTrueWhenFormIsSubmittedAndCancelButtonClicked(): void
    {
        $button = Mockery::mock(Button::class);
        $button->expects('isClicked')->andReturnTrue();

        $form = Mockery::mock(FormInterface::class);
        $form->expects('isSubmitted')->andReturnTrue();
        $form->expects('has')->with('cancel')->andReturnTrue();
        $form->expects('get')->with('cancel')->andReturn($button);

        self::assertTrue($this->helper->isFormCancelled($form));
    }

    public function testGetPaginator(): void
    {
        $pagination = Mockery::mock(PaginationInterface::class);

        $this->paginator->expects('paginate')->with(
            $target = 'foo',
            $page = 12,
            $limit = 0,
        )->andReturn($pagination);

        self::assertSame(
            $pagination,
            $this->helper->getPaginator(
                $target,
                $page,
                $limit,
            ),
        );
    }
}
