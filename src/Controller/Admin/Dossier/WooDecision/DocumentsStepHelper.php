<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\ProductionReportProcessRun;
use App\Form\Dossier\WooDecision\InventoryType;
use App\Form\Dossier\WooDecision\TranslatableFormErrorMapper;
use App\ValueObject\InventoryStatus;
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

    public function getInventoryProcessResponse(WooDecision $dossier): JsonResponse
    {
        $inventoryForm = $this->formFactory->create(InventoryType::class);

        $processRun = $this->mapProcessRunToForm($dossier, $inventoryForm);
        $inventoryStatus = new InventoryStatus($dossier);

        return new JsonResponse([
            'content' => $this->twig->render('admin/dossier/woo-decision/documents/processrun.html.twig', [
                'dossier' => $dossier,
                'processRun' => $processRun,
                'inventoryForm' => $inventoryForm->createView(),
                'inventoryStatus' => $inventoryStatus,
                'ajax' => true,
            ]),
            'inventoryStatus' => [
                'canUpload' => $inventoryStatus->canUpload(),
                'hasErrors' => $inventoryStatus->hasErrors(),
                'isQueued' => $inventoryStatus->isQueued(),
                'isRunning' => $inventoryStatus->isRunning(),
                'needsUpdate' => $inventoryStatus->needsUpdate(),
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
