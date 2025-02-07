<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin\Dossier\WooDecision;

use App\Controller\Admin\Dossier\WooDecision\DocumentsStepHelper;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Form\Dossier\WooDecision\InventoryType;
use App\Form\Dossier\WooDecision\TranslatableFormErrorMapper;
use App\Tests\Unit\UnitTestCase;
use App\ValueObject\ProductionReportStatus;
use Mockery\MockInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class DocumentsStepHelperTest extends UnitTestCase
{
    private TranslatableFormErrorMapper&MockInterface $formErrorMapper;
    private Environment&MockInterface $twig;
    private FormFactoryInterface&MockInterface $formFactory;
    private DocumentsStepHelper $helper;

    public function setUp(): void
    {
        parent::setUp();

        $this->formErrorMapper = \Mockery::mock(TranslatableFormErrorMapper::class);
        $this->twig = \Mockery::mock(Environment::class);
        $this->formFactory = \Mockery::mock(FormFactoryInterface::class);

        $this->helper = new DocumentsStepHelper(
            $this->formErrorMapper,
            $this->twig,
            $this->formFactory
        );
    }

    public function testGetProductionReportProcessResponse(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $formView = \Mockery::mock(FormView::class);
        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('createView')->andReturn($formView);

        $this->formFactory->expects('create')->with(InventoryType::class, $dossier)->andReturn($form);

        $processRun = \Mockery::mock(ProductionReportProcessRun::class);
        $processRun->shouldReceive('isFailed')->andReturnTrue();
        $processRun->shouldReceive('hasErrors')->andReturnTrue();
        $processRun->shouldReceive('isPending')->andReturnFalse();
        $processRun->shouldReceive('isConfirmed')->andReturnFalse();
        $processRun->shouldReceive('isComparing')->andReturnFalse();
        $processRun->shouldReceive('isUpdating')->andReturnFalse();
        $processRun->shouldReceive('isNotFinal')->andReturnFalse();
        $processRun->shouldReceive('isRejected')->andReturnFalse();
        $processRun->shouldReceive('needsConfirmation')->andReturnFalse();

        $dossier->shouldReceive('getProcessRun')->andReturn($processRun);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossier->shouldReceive('getProductionReport')->andReturnNull();

        $this->formErrorMapper->expects('mapRunErrorsToForm')->with($processRun, $form);

        $this->twig->expects('render')
            ->with(
                'admin/dossier/woo-decision/documents/processrun.html.twig',
                [
                    'dossier' => $dossier,
                    'processRun' => $processRun,
                    'inventoryForm' => $formView,
                    'inventoryStatus' => new ProductionReportStatus($dossier),
                    'ajax' => true,
                ]
            )
            ->andReturn('foo');

        $this->assertMatchesJsonSnapshot(
            $this->helper->getProductionReportProcessResponse($dossier)->getContent() ?: '',
        );
    }
}
