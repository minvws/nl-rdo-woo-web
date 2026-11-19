<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin\Publication\WooDecision;

use Shared\Controller\Admin\Upload\WooDecisionDocumentUploadController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class UploadDocuments
{
    public Uuid $dossierId;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getUploadEndpoint(): string
    {
        return $this->urlGenerator->generate(WooDecisionDocumentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $this->dossierId]);
    }
}
