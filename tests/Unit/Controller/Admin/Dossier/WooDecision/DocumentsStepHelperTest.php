<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin\Dossier\WooDecision;

use App\Controller\Admin\Dossier\WooDecision\DocumentsStepHelper;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\ProductionReportProcessRun;
use App\Form\Dossier\WooDecision\InventoryType;
use App\Form\Dossier\WooDecision\TranslatableFormErrorMapper;
use App\ValueObject\InventoryStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class DocumentsStepHelperTest extends MockeryTestCase
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

    public function testGetInventoryProcessResponse(): void
    {
        $formView = \Mockery::mock(FormView::class);
        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('createView')->andReturn($formView);

        $this->formFactory->expects('create')->with(InventoryType::class)->andReturn($form);

        $processRun = \Mockery::mock(ProductionReportProcessRun::class);
        $processRun->shouldReceive('isFailed')->andReturnTrue();
        $processRun->shouldReceive('hasErrors')->andReturnTrue();
        $processRun->shouldReceive('isPending')->andReturnFalse();
        $processRun->shouldReceive('isConfirmed')->andReturnFalse();
        $processRun->shouldReceive('isComparing')->andReturnFalse();
        $processRun->shouldReceive('isUpdating')->andReturnFalse();
        $processRun->shouldReceive('isNotFinal')->andReturnFalse();

        $dossier = \Mockery::mock(WooDecision::class);
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
                    'inventoryStatus' => new InventoryStatus($dossier),
                    'ajax' => true,
                ]
            )
            ->andReturn('foo');

        $this->assertJsonStringEqualsJsonString(
            '{"content":"foo","inventoryStatus":{"canUpload":true,"hasErrors":true,"isQueued":false,"isRunning":false,"needsUpdate":false}}',
            $this->helper->getInventoryProcessResponse($dossier)->getContent() ?: '',
        );
    }
}
