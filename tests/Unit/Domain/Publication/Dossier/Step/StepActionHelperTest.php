<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Step;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\ViewModel\DossierViewParamsBuilder;
use App\Service\DossierWizard\DossierWizardHelper;
use App\Service\DossierWizard\DossierWizardStatus;
use App\Service\DossierWizard\StepStatus;
use Knp\Component\Pager\PaginatorInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class StepActionHelperTest extends MockeryTestCase
{
    private RouterInterface&MockInterface $router;
    private StepActionHelper $helper;
    private DossierWizardHelper&MockInterface $dossierWizardHelper;
    private AbstractDossier&MockInterface $dossier;
    private DossierWizardStatus&MockInterface $wizardStatus;
    private DossierViewParamsBuilder&MockInterface $paramsBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->dossierWizardHelper = \Mockery::mock(DossierWizardHelper::class);

        $this->dossier = \Mockery::mock(AbstractDossier::class);
        $this->dossier->shouldReceive('getDocumentPrefix')->andReturn('foo');
        $this->dossier->shouldReceive('getDossierNr')->andReturn('bar');

        $this->wizardStatus = \Mockery::mock(DossierWizardStatus::class);
        $this->wizardStatus->shouldReceive('getDossier')->andReturn($this->dossier);

        $this->paramsBuilder = \Mockery::mock(DossierViewParamsBuilder::class);

        $this->router = \Mockery::mock(RouterInterface::class);
        $this->helper = new StepActionHelper(
            $this->router,
            $this->dossierWizardHelper,
            \Mockery::mock(PaginatorInterface::class),
            $this->paramsBuilder,
        );
    }

    public function testRedirectToNextStep(): void
    {
        $nextStep = \Mockery::mock(StepStatus::class);
        $nextStep->shouldReceive('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->shouldReceive('getNextStep')->andReturn($nextStep);

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
        $currentStep = \Mockery::mock(StepStatus::class);
        $currentStep->shouldReceive('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->shouldReceive('getCurrentStep')->andReturn($currentStep);

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
        $openStep = \Mockery::mock(StepStatus::class);
        $openStep->shouldReceive('getRouteName')->andReturn('dummy_route');

        $this->wizardStatus->shouldReceive('getFirstOpenStep')->andReturn($openStep);

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
        $wizardStatus = \Mockery::mock(DossierWizardStatus::class);
        $wizardStatus->shouldReceive('getFirstOpenStep')->andReturnNull();
        $wizardStatus->shouldReceive('getDossier')->andReturn($this->dossier);

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
        $currentStep = \Mockery::mock(StepStatus::class);
        $currentStep->shouldReceive('getStepName')->andReturn(StepName::DETAILS);

        $nextStep = \Mockery::mock(StepStatus::class);
        $nextStep->shouldReceive('getRouteName')->andReturn('dummy_route');

        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('has')->with('next')->andReturnTrue();
        $form->shouldReceive('get->isClicked')->andReturnTrue();

        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->wizardStatus->shouldReceive('getFirstOpenStep')->andReturn($nextStep);
        $this->wizardStatus->shouldReceive('getCurrentStep')->andReturn($currentStep);

        $this->dossierWizardHelper->expects('getStatus')->andReturn($this->wizardStatus);

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
        $currentStep = \Mockery::mock(StepStatus::class);
        $currentStep->shouldReceive('getRouteName')->andReturn('dummy_route');
        $currentStep->shouldReceive('getStepName')->andReturn(StepName::DETAILS);

        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('has')->with('next')->andReturnTrue();
        $form->shouldReceive('get->isClicked')->andReturnFalse();

        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->wizardStatus->shouldReceive('getCurrentStep')->andReturn($currentStep);

        $this->dossierWizardHelper->expects('getStatus')->andReturn($this->wizardStatus);

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
        $form = \Mockery::mock(FormInterface::class);

        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $currentStep = \Mockery::mock(StepStatus::class);
        $currentStep->shouldReceive('getStepName')->andReturn(StepName::DETAILS);

        $this->wizardStatus->shouldReceive('getCurrentStep')->andReturn($currentStep);

        $this->dossierWizardHelper->expects('getStatus')->andReturn($this->wizardStatus);

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
        $breadCrumbs = \Mockery::mock(Breadcrumbs::class);
        $item = 'foo bar';
        $dossierTitle = 'llama';

        $this->dossier->expects('getTitle')->andReturn($dossierTitle);

        $breadCrumbs->expects('addRouteItem')->with(
            $dossierTitle,
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
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('foo');
        $dossier->shouldReceive('getDossierNr')->andReturn('bar');

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
        $dossier = \Mockery::mock(AbstractDossier::class);
        $expectedResult = \Mockery::mock(DossierViewParamsBuilder::class);

        $this->paramsBuilder->expects('forDossier')->with($dossier)->andReturn($expectedResult);

        self::assertSame(
            $expectedResult,
            $this->helper->getParamsBuilder($dossier),
        );
    }
}
