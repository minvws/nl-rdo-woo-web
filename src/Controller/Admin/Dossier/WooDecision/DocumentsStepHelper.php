<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\ProductionReportStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Form\Dossier\WooDecision\InventoryType;
use App\Form\Dossier\WooDecision\TranslatableFormErrorMapper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;

readonly class DocumentsStepHelper
{
    public function __construct(
        private TranslatableFormErrorMapper $formErrorMapper,
        private Environment $twig,
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function getProductionReportProcessResponse(WooDecision $dossier): JsonResponse
    {
        $inventoryForm = $this->formFactory->create(InventoryType::class, $dossier);

        $processRun = $this->mapProcessRunToForm($dossier, $inventoryForm);
        $inventoryStatus = new ProductionReportStatus($dossier);

        return new JsonResponse([
            'content' => $this->twig->render('admin/dossier/woo-decision/documents/processrun.html.twig', [
                'dossier' => $dossier,
                'processRun' => $processRun,
                'inventoryForm' => $inventoryForm->createView(),
                'inventoryStatus' => $inventoryStatus,
                'ajax' => true,
            ]),
            'inventoryStatus' => [
                'hasErrors' => $inventoryStatus->hasErrors(),
                'isQueued' => $inventoryStatus->isQueued(),
                'isRunning' => $inventoryStatus->isRunning(),
                'needsUpdate' => $inventoryStatus->needsUpdate(),
                'needsConfirmation' => $inventoryStatus->needsConfirmation(),
            ],
        ]);
    }

    public function mapProcessRunToForm(WooDecision $dossier, FormInterface $form): ?ProductionReportProcessRun
    {
        $processRun = $dossier->getProcessRun();
        if ($processRun instanceof ProductionReportProcessRun && $processRun->isFailed()) {
            $this->formErrorMapper->mapRunErrorsToForm($processRun, $form);
        }

        return $processRun;
    }
}
