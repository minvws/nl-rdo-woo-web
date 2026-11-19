<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Admin\Publication;

use Shared\Controller\Admin\Upload\AttachmentUploadController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ManageAttachments
{
    public string $dossierId;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getUploadEndpoint(): string
    {
        return $this->urlGenerator->generate(AttachmentUploadController::ROUTE_NAME_UPLOAD, ['dossierId' => $this->dossierId]);
    }
}
