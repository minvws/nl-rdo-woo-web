<?php

declare(strict_types=1);

namespace Shared\Twig\Components\Public;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class DownloadFilesForm
{
    public string $action;
    public bool $canDownload;
    public string $searchUrl;
}
