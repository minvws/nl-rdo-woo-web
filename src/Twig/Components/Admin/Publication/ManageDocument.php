<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin\Publication;

use Shared\Controller\Admin\Upload\MainDocumentUploadController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ManageDocument
{
    public string $dossierId;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getUploadEndpoint(): string
    {
        return $this->urlGenerator->generate(MainDocumentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $this->dossierId]);
    }
}
